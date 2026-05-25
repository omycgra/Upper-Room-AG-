<?php

class Auth {
    public static function check() {
        return Session::has('user_id');
    }

    public static function user() {
        if (!self::check()) return null;
        
        $userId = Session::get('user_id');
        return Database::getInstance()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }

    public static function login($email, $password, $loginType = null) {
        $db = Database::getInstance();
        self::ensureSchema($db);

        $login = $email;
        $user = $db->fetch("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1", [$login, $login]);
        
        if ($user && password_verify($password, $user['password'])) {
            $loginType = $loginType !== null ? strtolower(trim((string)$loginType)) : null;
            $role = strtolower(trim((string)($user['role'] ?? '')));
            $normalizedRole = $role;
            if (in_array($role, ['dept_head', 'department_head', 'department head', 'dept head', 'departmenthead'], true)) {
                $normalizedRole = 'dept_head';
            } elseif (in_array($role, ['admin', 'administrator'], true)) {
                $normalizedRole = 'admin';
            } elseif (in_array($role, ['visitation_team', 'visitation team', 'visitation'], true)) {
                $normalizedRole = 'visitation_team';
            } elseif (in_array($role, ['finance_staff', 'finance staff', 'finance'], true)) {
                $normalizedRole = 'finance_staff';
            } elseif (in_array($role, ['finance_head', 'finance head', 'head_of_finance', 'head of finance'], true)) {
                $normalizedRole = 'finance_head';
            } elseif (in_array($role, ['auditor', 'audit'], true)) {
                $normalizedRole = 'auditor';
            } elseif (in_array($role, ['pastor', 'reverend', 'rev', 'minister'], true)) {
                $normalizedRole = 'pastor';
            } elseif (in_array($role, ['staff', 'member', 'user'], true)) {
                $normalizedRole = 'finance_staff';
            } elseif ($role !== '') {
                $normalizedRole = preg_replace('/\s+/', '_', $role);
            }

            if ($loginType !== null && $loginType !== '') {
                $loginTypeAllowedRoles = [
                    'admin' => ['admin'],
                    'dept_head' => ['dept_head'],
                    'visitation_team' => ['visitation_team'],
                    'finance_staff' => ['finance_staff', 'finance_head'],
                    'finance_head' => ['finance_head'],
                    'auditor' => ['auditor'],
                    'pastor' => ['pastor'],
                ];

                if ($loginType === 'staff') {
                    $loginType = 'finance_staff';
                }
                $allowed = $loginTypeAllowedRoles[$loginType] ?? [$loginType];
                if (!in_array($normalizedRole, $allowed, true)) {
                    return ['success' => false, 'reason' => 'permission_mismatch'];
                }
            }

            Session::set('user_id', $user['id']);
            Session::set('user_role', $normalizedRole);
            Session::set('user_name', $user['name'] ?: ($user['username'] ?? 'Admin'));
            Session::set('user_photo', $user['photo_path'] ?? null);
            Session::set('user_department_id', $user['department_id'] ?? null);
            Session::set('just_logged_in', true);

            $rawRole = strtolower(trim((string)($user['role'] ?? '')));
            if (in_array($rawRole, ['staff', 'member', 'user'], true) && $normalizedRole === 'finance_staff') {
                try {
                    $db->query("UPDATE users SET role = 'finance_staff' WHERE id = ?", [$user['id']]);
                } catch (Throwable $e) {
                }
            }
            
            // Update last login
            $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            
            return ['success' => true];
        }
        
        return ['success' => false, 'reason' => 'invalid_credentials'];
    }

    public static function requestPasswordReset($login) {
        $db = Database::getInstance();
        self::ensureSchema($db);

        $login = trim((string)$login);
        if ($login === '') {
            return ['success' => false, 'reason' => 'missing_login'];
        }

        $user = $db->fetch("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1", [$login, $login]);

        if (!$user) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        try {
            $db->query("UPDATE users SET reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?", [(int)$user['id']]);
            $db->query(
                "INSERT INTO password_reset_requests (user_id, requested_login, status, requested_at)
                 VALUES (?, ?, 'pending', NOW())",
                [(int)$user['id'], $login]
            );
        } catch (Throwable $e) {
            return ['success' => false, 'reason' => 'request_failed'];
        }

        return ['success' => true, 'requires_admin_approval' => true];
    }

    public static function resetPasswordWithToken($token, $newPassword) {
        $db = Database::getInstance();
        self::ensureSchema($db);

        $token = trim((string)$token);
        $newPassword = (string)$newPassword;
        if ($token === '' || strlen($newPassword) < 6) {
            return ['success' => false, 'reason' => 'invalid_input'];
        }

        $tokenHash = hash('sha256', $token);
        $user = $db->fetch(
            "SELECT id
             FROM users
             WHERE reset_token_hash = ?
               AND reset_token_expires_at IS NOT NULL
               AND reset_token_expires_at > NOW()
             LIMIT 1",
            [$tokenHash]
        );

        if (!$user) {
            return ['success' => false, 'reason' => 'invalid_or_expired'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $db->query(
            "UPDATE users
             SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL
             WHERE id = ?",
            [$hash, $user['id']]
        );

        try {
            $tokenHash = hash('sha256', $token);
            $db->query(
                "UPDATE password_reset_requests
                 SET status = 'consumed', consumed_at = NOW()
                 WHERE user_id = ?
                   AND token_hash = ?
                   AND status = 'approved'",
                [(int)$user['id'], $tokenHash]
            );
        } catch (Throwable $e) {
        }

        return ['success' => true];
    }

    public static function logout() {
        Session::remove('user_id');
        Session::remove('user_role');
        Session::remove('user_name');
        Session::remove('user_department_id');
        Session::destroy();
    }

    public static function isAdmin() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['admin', 'administrator'], true);
    }

    public static function isDepartmentHead() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['dept_head', 'department_head', 'department head', 'dept head', 'departmenthead'], true);
    }

    public static function isStaff() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['finance_staff', 'finance_head'], true);
    }

    public static function isVisitationTeam() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['visitation_team', 'visitation team', 'visitation'], true);
    }

    public static function isFinance() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['finance_staff', 'finance_head'], true);
    }

    public static function isFinanceHead() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['finance_head'], true);
    }

    public static function isAuditor() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['auditor'], true);
    }

    public static function isPastor() {
        $role = strtolower(trim((string)Session::get('user_role')));
        return in_array($role, ['pastor'], true);
    }

    private static function ensureSchema($db) {
        SchemaState::once('users_schema', function () use ($db) {
            if (!$db->columnExists('users', 'username')) {
                $db->query("ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL");
            }

            if (!$db->columnExists('users', 'photo_path')) {
                $db->query("ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL");
            }

            if (!$db->columnExists('users', 'department_id')) {
                $db->query("ALTER TABLE users ADD COLUMN department_id INT NULL");
            }

            $roleType = $db->getColumnDataType('users', 'role');
            if ($db->isMysql() && $roleType === 'enum') {
                $db->query("ALTER TABLE users MODIFY COLUMN role VARCHAR(30) NULL DEFAULT 'finance_staff'");
            }

            if (!$db->columnExists('users', 'reset_token_hash')) {
                $db->query("ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(64) NULL");
            }

            if (!$db->columnExists('users', 'reset_token_expires_at')) {
                $db->query(
                    "ALTER TABLE users ADD COLUMN reset_token_expires_at " . ($db->isPgsql() ? 'TIMESTAMP' : 'DATETIME') . " NULL"
                );
            }
        });

        SchemaState::once('password_reset_requests_schema', function () use ($db) {
            if ($db->tableExists('password_reset_requests')) {
                return;
            }

            if ($db->isPgsql()) {
                $db->rawExec(
                    "CREATE TABLE IF NOT EXISTS public.password_reset_requests (
                        id integer generated by default as identity primary key,
                        user_id integer not null references public.users(id) on delete cascade,
                        requested_login varchar(100) null,
                        status varchar(20) not null default 'pending',
                        requested_at timestamptz not null default timezone('utc', now()),
                        approved_by integer null references public.users(id) on delete set null,
                        approved_at timestamptz null,
                        token_hash varchar(64) null,
                        token_expires_at timestamptz null,
                        consumed_at timestamptz null
                    )"
                );
                $db->rawExec("CREATE INDEX IF NOT EXISTS idx_prr_status ON public.password_reset_requests (status)");
                $db->rawExec("CREATE INDEX IF NOT EXISTS idx_prr_user ON public.password_reset_requests (user_id)");
                return;
            }

            $db->rawExec(
                "CREATE TABLE IF NOT EXISTS password_reset_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    requested_login VARCHAR(100) NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    approved_by INT NULL,
                    approved_at DATETIME NULL,
                    token_hash VARCHAR(64) NULL,
                    token_expires_at DATETIME NULL,
                    consumed_at DATETIME NULL,
                    INDEX idx_prr_status (status),
                    INDEX idx_prr_user (user_id)
                )"
            );
        });
    }
}
