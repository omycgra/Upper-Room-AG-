<?php
require_once __DIR__ . '/BaseModel.php';

class Member extends BaseModel {
    protected $table = 'members';
    private static $hasCreatedAtColumn = null;

    public function getWithDetails($id) {
        $sql = "SELECT m.*, c.name as cluster_name, d.name as department_name,
                       d.name as primary_department_name
                FROM members m
                LEFT JOIN clusters c ON m.cluster_id = c.id
                LEFT JOIN departments d ON m.department_id = d.id
                WHERE m.id = ?";
        $member = $this->db->fetch($sql, [$id]);
        if (!$member) {
            return null;
        }

        $rows = $this->attachDepartmentAssignments([$member]);
        return $rows[0] ?? $member;
    }

    public function findByMemberCode($memberCode) {
        return $this->db->fetch(
            "SELECT * FROM members WHERE UPPER(member_code) = UPPER(?) LIMIT 1",
            [$memberCode]
        );
    }

    public function findByBioId($bioId) {
        return $this->db->fetch(
            "SELECT * FROM members WHERE UPPER(bio_id) = UPPER(?) LIMIT 1",
            [$bioId]
        );
    }

    public function findPotentialByPhoneFragment($digitsFragment, $limit = 20) {
        $digitsFragment = preg_replace('/[^0-9]/', '', (string)$digitsFragment);
        if ($digitsFragment === '') {
            return [];
        }
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 20;
        if ($limit > 200) $limit = 200;

        return $this->db->fetchAll(
            "SELECT * FROM members WHERE phone IS NOT NULL AND phone <> '' AND phone LIKE ? LIMIT $limit",
            ['%' . $digitsFragment . '%']
        );
    }

    public function findByNameDob($firstName, $lastName, $dob) {
        $firstName = trim((string)$firstName);
        $lastName = trim((string)$lastName);
        $dob = trim((string)$dob);
        if ($firstName === '' || $lastName === '' || $dob === '') {
            return null;
        }
        return $this->db->fetch(
            "SELECT * FROM members
             WHERE UPPER(first_name) = UPPER(?)
               AND UPPER(last_name) = UPPER(?)
               AND date_of_birth = ?
             LIMIT 1",
            [$firstName, $lastName, $dob]
        );
    }

    public function searchAndFilter($term = '', $deptId = '', $status = '', $sort = '', $added = '') {
        $sql = "SELECT m.*, c.name as cluster_name, d.name as department_name, d.name as primary_department_name
                FROM members m
                LEFT JOIN clusters c ON m.cluster_id = c.id
                LEFT JOIN departments d ON m.department_id = d.id
                WHERE 1=1";
        $params = [];

        $term = trim((string)$term);
        if ($term !== '') {
            $isPg = $this->db->isPgsql();
            $likeOp = $isPg ? 'ILIKE' : 'LIKE';
            $phoneExpr = $isPg
                ? "REGEXP_REPLACE(COALESCE(m.phone, ''), '[^0-9]', '', 'g')"
                : "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(m.phone, ''), ' ', ''), '-', ''), '+', ''), '/', ''), '.', '')";

            $clean = preg_replace('/\s+/', ' ', $term);
            $tokens = array_values(array_filter(array_map('trim', preg_split('/\s+/', $clean) ?: []), fn($v) => $v !== ''));

            $digits = preg_replace('/\D+/', '', $clean);
            $phoneCandidates = [];
            if ($digits !== '' && strlen($digits) >= 4) {
                $phoneCandidates[] = $digits;
                if (strlen($digits) === 10 && $digits[0] === '0') {
                    $phoneCandidates[] = '233' . substr($digits, 1);
                }
                if (strlen($digits) === 12 && substr($digits, 0, 3) === '233') {
                    $phoneCandidates[] = '0' . substr($digits, 3);
                }
                $phoneCandidates = array_values(array_unique($phoneCandidates));
            }

            // Tokenized search: every token must match at least one field.
            // This fixes cases like searching "John Doe" where first_name/last_name are separate columns.
            $fields = [
                'm.first_name',
                'm.last_name',
                'm.member_code',
                "COALESCE(m.bio_id, '')",
                "COALESCE(m.phone, '')",
                "COALESCE(m.email, '')",
                "COALESCE(m.address, '')",
                "COALESCE(m.stays_at, '')",
                "COALESCE(c.name, '')",
                "COALESCE(d.name, '')",
            ];

            $tokenClauses = [];
            foreach ($tokens as $t) {
                $or = [];
                $likeTerm = '%' . $t . '%';
                foreach ($fields as $f) {
                    $or[] = "$f $likeOp ?";
                    $params[] = $likeTerm;
                }
                // Also match additional department assignments (member_departments) by department name.
                $or[] = "EXISTS (
                    SELECT 1
                    FROM member_departments md
                    INNER JOIN departments dd ON dd.id = md.department_id
                    WHERE md.member_id = m.id
                      AND dd.name $likeOp ?
                )";
                $params[] = $likeTerm;
                $tokenClauses[] = '(' . implode(' OR ', $or) . ')';
            }

            // Also include phone digit fragment search when term is numeric-ish.
            // This helps match "0256..." even if the stored phone contains separators/spaces.
            if (!empty($phoneCandidates)) {
                $or = [];
                foreach ($phoneCandidates as $pc) {
                    $or[] = "$phoneExpr $likeOp ?";
                    $params[] = '%' . $pc . '%';
                }
                $tokenClauses[] = '(' . implode(' OR ', $or) . ')';
            }

            if (!empty($tokenClauses)) {
                $sql .= ' AND ' . implode(' AND ', $tokenClauses);
            }
        }

        if (!empty($deptId)) {
            $sql .= " AND (
                m.department_id = ?
                OR EXISTS (
                    SELECT 1
                    FROM member_departments md
                    WHERE md.member_id = m.id
                      AND md.department_id = ?
                )
            )";
            $params[] = $deptId;
            $params[] = $deptId;
        }

        if (!empty($status)) {
            $sql .= " AND m.membership_status = ?";
            $params[] = $status;
        }

        $addedKey = strtolower(trim((string)$added));
        if ($addedKey === 'today') {
            if (self::$hasCreatedAtColumn === null) {
                $cache = Session::get('members_has_created_at_column');
                if ($cache !== null) {
                    self::$hasCreatedAtColumn = (bool)$cache;
                } else {
                    self::$hasCreatedAtColumn = $this->db->columnExists('members', 'created_at');
                    Session::set('members_has_created_at_column', self::$hasCreatedAtColumn);
                }
            }
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
            if (self::$hasCreatedAtColumn) {
                $sql .= " AND m.created_at >= ? AND m.created_at < ?";
                $params[] = $today;
                $params[] = $tomorrow;
            } else {
                $sql .= " AND m.join_date >= ? AND m.join_date < ?";
                $params[] = $today;
                $params[] = $tomorrow;
            }
        }

        $sortKey = strtolower(trim((string)$sort));
        $defaultSort = 'm.last_name ASC, m.first_name ASC';
        $allowedSort = [
            '' => $defaultSort,
            'name' => $defaultSort,
            'first_name' => 'm.first_name ASC, m.last_name ASC',
            'last_name' => 'm.last_name ASC, m.first_name ASC',
            'member_code' => 'm.member_code ASC, m.last_name ASC, m.first_name ASC',
            'bio_id' => "COALESCE(m.bio_id, '') ASC, m.last_name ASC, m.first_name ASC",
        ];
        if ($sortKey === 'newest') {
            if (self::$hasCreatedAtColumn === null) {
                $cache = Session::get('members_has_created_at_column');
                if ($cache !== null) {
                    self::$hasCreatedAtColumn = (bool)$cache;
                } else {
                    self::$hasCreatedAtColumn = $this->db->columnExists('members', 'created_at');
                    Session::set('members_has_created_at_column', self::$hasCreatedAtColumn);
                }
            }
            if (self::$hasCreatedAtColumn) {
                $sql .= " ORDER BY m.created_at DESC, m.id DESC";
            } else {
                $sql .= " ORDER BY m.join_date DESC, m.id DESC";
            }
        } else {
            $sql .= " ORDER BY " . ($allowedSort[$sortKey] ?? $defaultSort);
        }
        $rows = $this->db->fetchAll($sql, $params);
        return $this->attachDepartmentAssignments($rows);
    }

    public function getAllWithDetails($orderBy = 'm.first_name ASC, m.last_name ASC') {
        $sql = "SELECT m.*, c.name as cluster_name, d.name as department_name,
                       d.name as primary_department_name
                FROM members m
                LEFT JOIN clusters c ON m.cluster_id = c.id
                LEFT JOIN departments d ON m.department_id = d.id";
        if ($orderBy) {
            $sql .= " ORDER BY " . $orderBy;
        }

        $rows = $this->db->fetchAll($sql);
        return $this->attachDepartmentAssignments($rows);
    }

    public function syncAdditionalDepartments($memberId, array $departmentIds, $primaryDepartmentId = null) {
        $memberId = (int)$memberId;
        $primaryDepartmentId = !empty($primaryDepartmentId) ? (int)$primaryDepartmentId : null;
        $departmentIds = array_values(array_unique(array_filter(array_map('intval', $departmentIds))));
        if ($primaryDepartmentId) {
            $departmentIds = array_values(array_filter($departmentIds, function ($id) use ($primaryDepartmentId) {
                return (int)$id !== $primaryDepartmentId;
            }));
        }

        $this->db->query("DELETE FROM member_departments WHERE member_id = ?", [$memberId]);
        foreach ($departmentIds as $departmentId) {
            $this->db->query(
                "INSERT INTO member_departments (member_id, department_id) VALUES (?, ?)",
                [$memberId, $departmentId]
            );
        }
    }

    private function attachDepartmentAssignments(array $rows) {
        if (empty($rows)) {
            return $rows;
        }

        $memberIds = array_values(array_unique(array_map(function ($row) {
            return (int)($row['id'] ?? 0);
        }, $rows)));
        $memberIds = array_values(array_filter($memberIds));
        if (empty($memberIds)) {
            return $rows;
        }

        $placeholders = implode(', ', array_fill(0, count($memberIds), '?'));
        $assignmentSql = "SELECT assigned.member_id, assigned.department_id, d.name
                          FROM (
                              SELECT id AS member_id, department_id
                              FROM members
                              WHERE id IN ($placeholders)
                                AND department_id IS NOT NULL
                              UNION ALL
                              SELECT member_id, department_id
                              FROM member_departments
                              WHERE member_id IN ($placeholders)
                          ) assigned
                          INNER JOIN departments d ON d.id = assigned.department_id
                          ORDER BY d.name ASC";
        $assignmentRows = $this->db->fetchAll($assignmentSql, array_merge($memberIds, $memberIds));

        $assignmentMap = [];
        foreach ($assignmentRows as $assignment) {
            $memberId = (int)($assignment['member_id'] ?? 0);
            $departmentId = (int)($assignment['department_id'] ?? 0);
            $departmentName = (string)($assignment['name'] ?? '');
            if (!isset($assignmentMap[$memberId])) {
                $assignmentMap[$memberId] = [
                    'names' => [],
                    'all_ids' => [],
                ];
            }

            if (!in_array($departmentId, $assignmentMap[$memberId]['all_ids'], true)) {
                $assignmentMap[$memberId]['all_ids'][] = $departmentId;
                $assignmentMap[$memberId]['names'][] = $departmentName;
            }
        }

        foreach ($rows as &$row) {
            $memberId = (int)($row['id'] ?? 0);
            $primaryDepartmentId = (int)($row['department_id'] ?? 0);
            $names = $assignmentMap[$memberId]['names'] ?? [];
            $allIds = $assignmentMap[$memberId]['all_ids'] ?? [];
            $additionalIds = array_values(array_filter($allIds, function ($id) use ($primaryDepartmentId) {
                return (int)$id !== $primaryDepartmentId;
            }));

            $row['department_names'] = !empty($names) ? implode(', ', $names) : ($row['department_name'] ?? null);
            $row['all_department_ids'] = $allIds;
            $row['all_department_ids_csv'] = !empty($allIds) ? implode(',', $allIds) : '';
            $row['additional_department_ids'] = $additionalIds;
            $row['additional_department_ids_csv'] = !empty($additionalIds) ? implode(',', $additionalIds) : '';
        }
        unset($row);

        return $rows;
    }

    public function getStats() {
        $total = $this->db->fetch("SELECT COUNT(*) as count FROM members")['count'];
        $active = $this->db->fetch("SELECT COUNT(*) as count FROM members WHERE membership_status = 'Active'")['count'];

        if (self::$hasCreatedAtColumn === null) {
            $cache = Session::get('members_has_created_at_column');
            if ($cache !== null) {
                self::$hasCreatedAtColumn = (bool)$cache;
            } else {
                self::$hasCreatedAtColumn = $this->db->columnExists('members', 'created_at');
                Session::set('members_has_created_at_column', self::$hasCreatedAtColumn);
            }
        }

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
        if (self::$hasCreatedAtColumn) {
            $new = $this->db->fetch(
                "SELECT COUNT(*) as count FROM members WHERE created_at >= ? AND created_at < ?",
                [$today, $tomorrow]
            )['count'];
        } else {
            $new = $this->db->fetch(
                "SELECT COUNT(*) as count FROM members WHERE join_date >= ? AND join_date < ?",
                [$today, $tomorrow]
            )['count'];
        }
        
        return [
            'total' => $total,
            'active' => $active,
            'new' => $new
        ];
    }

    public function getUpcomingBirthdays($limit = 5) {
        if ($this->db->isPgsql()) {
            $sql = "SELECT *,
                    TO_CHAR(date_of_birth, 'FMMonth DD') as birthday_display,
                    EXTRACT(MONTH FROM date_of_birth) as birth_month,
                    EXTRACT(DAY FROM date_of_birth) as birth_day
                    FROM members
                    WHERE
                        (EXTRACT(MONTH FROM date_of_birth) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
                            AND EXTRACT(DAY FROM date_of_birth) >= EXTRACT(DAY FROM CURRENT_TIMESTAMP))
                        OR
                        (EXTRACT(MONTH FROM date_of_birth) = EXTRACT(MONTH FROM (CURRENT_TIMESTAMP + INTERVAL '1 month')))
                    ORDER BY birth_month ASC, birth_day ASC
                    LIMIT ?";
        } else {
            $sql = "SELECT *,
                    DATE_FORMAT(date_of_birth, '%M %d') as birthday_display,
                    MONTH(date_of_birth) as birth_month,
                    DAY(date_of_birth) as birth_day
                    FROM members
                    WHERE
                        (MONTH(date_of_birth) = MONTH(NOW()) AND DAY(date_of_birth) >= DAY(NOW()))
                        OR
                        (MONTH(date_of_birth) = MONTH(DATE_ADD(NOW(), INTERVAL 1 MONTH)))
                    ORDER BY birth_month ASC, birth_day ASC
                    LIMIT ?";
        }
        return $this->db->fetchAll($sql, [$limit]);
    }

    public function getGenderDistribution() {
        $sql = "SELECT gender, COUNT(*) as count FROM members GROUP BY gender";
        $results = $this->db->fetchAll($sql);
        
        $total = 0;
        $distribution = ['male' => 0, 'female' => 0, 'other' => 0];
        
        foreach ($results as $row) {
            $gender = strtolower($row['gender']);
            if (isset($distribution[$gender])) {
                $distribution[$gender] = (int)$row['count'];
                $total += (int)$row['count'];
            }
        }
        
        return [
            'data' => $distribution,
            'total' => $total
        ];
    }

    public function getBirthdayCountThisMonth() {
        $sql = $this->db->isPgsql()
            ? "SELECT COUNT(*) as count FROM members WHERE EXTRACT(MONTH FROM date_of_birth) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)"
            : "SELECT COUNT(*) as count FROM members WHERE MONTH(date_of_birth) = MONTH(NOW())";
        return $this->db->fetch($sql)['count'];
    }

    public function getBirthdaysThisMonth() {
        if ($this->db->isPgsql()) {
            $sql = "SELECT id, first_name, last_name, phone, photo_path, date_of_birth,
                    TO_CHAR(date_of_birth, 'FMMonth DD') as birthday_display,
                    EXTRACT(DAY FROM date_of_birth) as birth_day
                    FROM members
                    WHERE date_of_birth IS NOT NULL
                      AND EXTRACT(MONTH FROM date_of_birth) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
                    ORDER BY birth_day ASC, first_name ASC, last_name ASC";
        } else {
            $sql = "SELECT id, first_name, last_name, phone, photo_path, date_of_birth,
                    DATE_FORMAT(date_of_birth, '%M %d') as birthday_display,
                    DAY(date_of_birth) as birth_day
                    FROM members
                    WHERE date_of_birth IS NOT NULL
                      AND MONTH(date_of_birth) = MONTH(NOW())
                    ORDER BY birth_day ASC, first_name ASC, last_name ASC";
        }
        return $this->db->fetchAll($sql);
    }

    public function getTodaysBirthdays() {
        if ($this->db->isPgsql()) {
            $sql = "SELECT id, first_name, last_name, phone, date_of_birth
                    FROM members
                    WHERE date_of_birth IS NOT NULL
                      AND EXTRACT(MONTH FROM date_of_birth) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
                      AND EXTRACT(DAY FROM date_of_birth) = EXTRACT(DAY FROM CURRENT_TIMESTAMP)
                    ORDER BY first_name ASC, last_name ASC";
        } else {
            $sql = "SELECT id, first_name, last_name, phone, date_of_birth
                    FROM members
                    WHERE date_of_birth IS NOT NULL
                      AND MONTH(date_of_birth) = MONTH(NOW())
                      AND DAY(date_of_birth) = DAY(NOW())
                    ORDER BY first_name ASC, last_name ASC";
        }

        return $this->db->fetchAll($sql);
    }

    public function getAgeDistribution() {
        if ($this->db->isPgsql()) {
            $sql = "SELECT
                        SUM(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) < 18 THEN 1 ELSE 0 END) AS under_18,
                        SUM(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) BETWEEN 18 AND 35 THEN 1 ELSE 0 END) AS age_18_35,
                        SUM(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) BETWEEN 36 AND 60 THEN 1 ELSE 0 END) AS age_36_60,
                        SUM(CASE WHEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, date_of_birth)) > 60 THEN 1 ELSE 0 END) AS over_60,
                        SUM(CASE WHEN date_of_birth IS NULL THEN 1 ELSE 0 END) AS unknown_age,
                        COUNT(*) as total_members
                    FROM members";
        } else {
            $sql = "SELECT
                        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 1 ELSE 0 END) AS under_18,
                        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35 THEN 1 ELSE 0 END) AS age_18_35,
                        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 60 THEN 1 ELSE 0 END) AS age_36_60,
                        SUM(CASE WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 60 THEN 1 ELSE 0 END) AS over_60,
                        SUM(CASE WHEN date_of_birth IS NULL THEN 1 ELSE 0 END) AS unknown_age,
                        COUNT(*) as total_members
                    FROM members";
        }
        
        $row = $this->db->fetch($sql);
        $totalMembers = (int)($row['total_members'] ?? 0);
        $unknownAge = (int)($row['unknown_age'] ?? 0);
        
        return [
            'under_18' => (int)($row['under_18'] ?? 0),
            'age_18_35' => (int)($row['age_18_35'] ?? 0),
            'age_36_60' => (int)($row['age_36_60'] ?? 0),
            'over_60' => (int)($row['over_60'] ?? 0),
            'unknown_age' => $unknownAge,
            'known_total' => max(0, $totalMembers - $unknownAge),
            'total' => $totalMembers
        ];
    }
}
