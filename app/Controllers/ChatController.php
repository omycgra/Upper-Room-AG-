<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Helpers/Env.php';

class ChatController extends BaseController {
    public function ask() {
        if (Auth::isAuditor()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $question = trim((string)($_POST['message'] ?? ''));
        if ($question === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Message is required.'], 400);
        }

        $system = "You are the help assistant for a church management system (Upper Room Assembly Mampong). "
            . "Answer concisely with step-by-step guidance. "
            . "Roles: Admin, Finance Staff, Head Of Finance, Department Head, Visitation Team, Auditor. "
            . "Finance approvals: only Head Of Finance approves change requests and department expense requests. "
            . "Auditor is read-only and can only download reports. "
            . "Forms use BASE_URL and the UI uses pop-up modals. "
            . "When unsure, ask a clarifying question.";

        $geminiKey = (string)Env::get('GOOGLE_API_KEY', '');
        if ($geminiKey === '') {
            $geminiKey = (string)Env::get('GEMINI_API_KEY', '');
        }

        if (trim($geminiKey) !== '') {
            $model = (string)Env::get('GEMINI_MODEL', 'gemini-1.5-flash');
            $payload = [
                'system_instruction' => [
                    'parts' => [['text' => $system]]
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $question]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.2
                ]
            ];

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($geminiKey);
            $response = $this->jsonPostRequest($url, $payload);
            if (!$response['success']) {
                $this->jsonResponse(['success' => false, 'message' => $response['message']], 500);
            }

            $text = (string)($response['data']['candidates'][0]['content']['parts'][0]['text'] ?? '');
            $text = trim($text);
            if ($text === '') {
                $this->jsonResponse(['success' => false, 'message' => 'No response received.'], 500);
            }

            $this->jsonResponse(['success' => true, 'reply' => $text]);
        }

        $openAiKey = (string)Env::get('OPENAI_API_KEY', '');
        if (trim($openAiKey) === '') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'AI chatbot is not configured. Add GOOGLE_API_KEY (Gemini) or OPENAI_API_KEY to .env first.'
            ], 400);
        }

        $model = (string)Env::get('OPENAI_MODEL', 'gpt-4o-mini');
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $question]
            ],
            'temperature' => 0.2
        ];

        $response = $this->openAiRequest('https://api.openai.com/v1/chat/completions', $openAiKey, $payload);
        if (!$response['success']) {
            $this->jsonResponse(['success' => false, 'message' => $response['message']], 500);
        }

        $text = (string)($response['data']['choices'][0]['message']['content'] ?? '');
        $text = trim($text);
        if ($text === '') {
            $this->jsonResponse(['success' => false, 'message' => 'No response received.'], 500);
        }

        $this->jsonResponse(['success' => true, 'reply' => $text]);
    }

    public function threads() {
        if (Auth::isAuditor()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $db = Database::getInstance();
        $this->ensureChatSchema($db);

        $me = (int)Session::get('user_id');
        if ($me <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $isAdmin = Auth::isAdmin();
        $isPastor = Auth::isPastor();
        $isFinanceHead = Auth::isFinanceHead();
        $isDeptHead = Auth::isDepartmentHead();
        $meRole = $this->normalizeRole((string)Session::get('user_role'));

        if ($isAdmin || $isPastor) {
            $roleFilterSql = '';
            if ($isPastor) {
                $roleFilterSql = " AND LOWER(COALESCE(u.role,'')) NOT IN ('auditor','audit')";
            }
            $rows = $db->fetchAll(
                "SELECT
                    u.id as user_id,
                    u.name,
                    u.photo_path,
                    t.id as thread_id,
                    t.last_message_at,
                    COALESCE((
                        SELECT COUNT(*)
                        FROM chat_messages um
                        WHERE um.thread_id = t.id
                          AND um.sender_id <> ?
                          AND um.read_at IS NULL
                    ), 0) AS unread_count,
                    COALESCE((
                        SELECT lm.id
                        FROM chat_messages lm
                        WHERE lm.thread_id = t.id
                        ORDER BY lm.id DESC
                        LIMIT 1
                    ), 0) AS last_message_id,
                    COALESCE((
                        SELECT lm.sender_id
                        FROM chat_messages lm
                        WHERE lm.thread_id = t.id
                        ORDER BY lm.id DESC
                        LIMIT 1
                    ), 0) AS last_sender_id,
                    COALESCE((
                        SELECT lm.message
                        FROM chat_messages lm
                        WHERE lm.thread_id = t.id
                        ORDER BY lm.id DESC
                        LIMIT 1
                    ), '') AS last_message
                 FROM users u
                 LEFT JOIN chat_threads t
                    ON (t.user_a = ? AND t.user_b = u.id) OR (t.user_a = u.id AND t.user_b = ?)
                 WHERE u.id <> ?
                   $roleFilterSql
                 ORDER BY COALESCE(t.last_message_at, t.created_at) DESC, u.name ASC",
                [$me, $me, $me, $me]
            ) ?: [];

            $threads = [];
            foreach ($rows as $r) {
                $threads[] = [
                    'thread_id' => (int)($r['thread_id'] ?? 0),
                    'user_id' => (int)($r['user_id'] ?? 0),
                    'name' => (string)($r['name'] ?? ''),
                    'photo_path' => (string)($r['photo_path'] ?? ''),
                    'last_message_at' => (string)($r['last_message_at'] ?? ''),
                    'unread_count' => (int)($r['unread_count'] ?? 0),
                    'last_message_id' => (int)($r['last_message_id'] ?? 0),
                    'last_sender_id' => (int)($r['last_sender_id'] ?? 0),
                    'last_message' => (string)($r['last_message'] ?? '')
                ];
            }

            $this->jsonResponse(['success' => true, 'threads' => $threads]);
        }

        if ($isFinanceHead) {
            $rows = $db->fetchAll(
                "SELECT
                    u.id as user_id,
                    u.name,
                    u.photo_path,
                    t.id as thread_id,
                    t.last_message_at,
                    COALESCE((
                        SELECT COUNT(*)
                        FROM chat_messages um
                        WHERE um.thread_id = t.id
                          AND um.sender_id <> ?
                          AND um.read_at IS NULL
                    ), 0) AS unread_count,
                    COALESCE((
                        SELECT lm.id
                        FROM chat_messages lm
                        WHERE lm.thread_id = t.id
                        ORDER BY lm.id DESC
                        LIMIT 1
                    ), 0) AS last_message_id,
                    COALESCE((
                        SELECT lm.sender_id
                        FROM chat_messages lm
                        WHERE lm.thread_id = t.id
                        ORDER BY lm.id DESC
                        LIMIT 1
                    ), 0) AS last_sender_id,
                    COALESCE((
                        SELECT lm.message
                        FROM chat_messages lm
                        WHERE lm.thread_id = t.id
                        ORDER BY lm.id DESC
                        LIMIT 1
                    ), '') AS last_message
                 FROM users u
                 LEFT JOIN chat_threads t
                    ON (t.user_a = ? AND t.user_b = u.id) OR (t.user_a = u.id AND t.user_b = ?)
                 WHERE u.id <> ?
                   AND LOWER(COALESCE(u.role, '')) IN (
                        'finance_staff','finance staff','finance',
                        'dept_head','department_head','department head','dept head','departmenthead',
                        'admin','administrator',
                        'pastor','reverend','rev','minister'
                   )
                 ORDER BY COALESCE(t.last_message_at, t.created_at) DESC, u.name ASC",
                [$me, $me, $me, $me]
            ) ?: [];

            $threads = [];
            foreach ($rows as $r) {
                $threads[] = [
                    'thread_id' => (int)($r['thread_id'] ?? 0),
                    'user_id' => (int)($r['user_id'] ?? 0),
                    'name' => (string)($r['name'] ?? ''),
                    'photo_path' => (string)($r['photo_path'] ?? ''),
                    'last_message_at' => (string)($r['last_message_at'] ?? ''),
                    'unread_count' => (int)($r['unread_count'] ?? 0),
                    'last_message_id' => (int)($r['last_message_id'] ?? 0),
                    'last_sender_id' => (int)($r['last_sender_id'] ?? 0),
                    'last_message' => (string)($r['last_message'] ?? '')
                ];
            }

            $this->jsonResponse(['success' => true, 'threads' => $threads]);
        }

        $adminRow = $db->fetch("SELECT id, name, photo_path FROM users WHERE LOWER(role) IN ('admin', 'administrator') ORDER BY id ASC LIMIT 1");
        $adminId = (int)($adminRow['id'] ?? 0);
        $pastorRow = $db->fetch("SELECT id, name, photo_path FROM users WHERE LOWER(COALESCE(role,'')) IN ('pastor','reverend','rev','minister') ORDER BY id ASC LIMIT 1");
        $pastorId = (int)($pastorRow['id'] ?? 0);

        $threads = [];
        if ($meRole === 'finance_staff' || $isDeptHead) {
            $financeHeadRow = $db->fetch(
                "SELECT id, name, photo_path
                 FROM users
                 WHERE LOWER(COALESCE(role,'')) IN ('finance_head','finance head','head_of_finance','head of finance')
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $financeHeadId = (int)($financeHeadRow['id'] ?? 0);
            if ($financeHeadId > 0 && $financeHeadId !== $me) {
                $t = $this->getOrCreateThread($db, $me, $financeHeadId);
                $threadId = (int)($t['id'] ?? 0);
                $meta = $threadId > 0 ? $this->getThreadMeta($db, $threadId, $me) : $this->emptyThreadMeta();
                $threads[] = [
                    'thread_id' => $threadId,
                    'user_id' => $financeHeadId,
                    'name' => (string)($financeHeadRow['name'] ?? 'Head Of Finance'),
                    'photo_path' => (string)($financeHeadRow['photo_path'] ?? ''),
                    'last_message_at' => (string)($t['last_message_at'] ?? ''),
                    'unread_count' => (int)$meta['unread_count'],
                    'last_message_id' => (int)$meta['last_message_id'],
                    'last_sender_id' => (int)$meta['last_sender_id'],
                    'last_message' => (string)$meta['last_message']
                ];
            }
        }

        if ($adminId > 0 && $adminId !== $me) {
            $t = $this->getOrCreateThread($db, $me, $adminId);
            $threadId = (int)($t['id'] ?? 0);
            $meta = $threadId > 0 ? $this->getThreadMeta($db, $threadId, $me) : $this->emptyThreadMeta();
            $threads[] = [
                'thread_id' => $threadId,
                'user_id' => $adminId,
                'name' => (string)($adminRow['name'] ?? 'Admin'),
                'photo_path' => (string)($adminRow['photo_path'] ?? ''),
                'last_message_at' => (string)($t['last_message_at'] ?? ''),
                'unread_count' => (int)$meta['unread_count'],
                'last_message_id' => (int)$meta['last_message_id'],
                'last_sender_id' => (int)$meta['last_sender_id'],
                'last_message' => (string)$meta['last_message']
            ];
        }

        if ($pastorId > 0 && $pastorId !== $me) {
            $t = $this->getOrCreateThread($db, $me, $pastorId);
            $threadId = (int)($t['id'] ?? 0);
            $meta = $threadId > 0 ? $this->getThreadMeta($db, $threadId, $me) : $this->emptyThreadMeta();
            $threads[] = [
                'thread_id' => $threadId,
                'user_id' => $pastorId,
                'name' => (string)($pastorRow['name'] ?? 'Pastor'),
                'photo_path' => (string)($pastorRow['photo_path'] ?? ''),
                'last_message_at' => (string)($t['last_message_at'] ?? ''),
                'unread_count' => (int)$meta['unread_count'],
                'last_message_id' => (int)$meta['last_message_id'],
                'last_sender_id' => (int)$meta['last_sender_id'],
                'last_message' => (string)$meta['last_message']
            ];
        }

        $this->jsonResponse(['success' => true, 'threads' => $threads]);
    }

    public function messages() {
        if (Auth::isAuditor()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $db = Database::getInstance();
        $this->ensureChatSchema($db);

        $me = (int)Session::get('user_id');
        $threadId = (int)($_GET['thread_id'] ?? 0);
        if ($me <= 0 || $threadId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid thread.'], 400);
        }

        $thread = $db->fetch("SELECT * FROM chat_threads WHERE id = ? LIMIT 1", [$threadId]);
        if (!$thread) {
            $this->jsonResponse(['success' => false, 'message' => 'Thread not found.'], 404);
        }

        $a = (int)($thread['user_a'] ?? 0);
        $b = (int)($thread['user_b'] ?? 0);
        if ($me !== $a && $me !== $b && !Auth::isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $limit = max(10, min(120, (int)($_GET['limit'] ?? 60)));
        $sinceId = (int)($_GET['since_id'] ?? 0);

        if ($sinceId > 0) {
            $rows = $db->fetchAll(
                "SELECT
                    m.id,
                    m.thread_id,
                    m.sender_id,
                    m.message,
                    m.created_at,
                    u.name as sender_name,
                    u.photo_path as sender_photo
                 FROM chat_messages m
                 INNER JOIN users u ON u.id = m.sender_id
                 WHERE m.thread_id = ?
                   AND m.id > ?
                 ORDER BY m.id ASC
                 LIMIT $limit",
                [$threadId, $sinceId]
            ) ?: [];
        } else {
            $rows = $db->fetchAll(
                "SELECT
                    m.id,
                    m.thread_id,
                    m.sender_id,
                    m.message,
                    m.created_at,
                    u.name as sender_name,
                    u.photo_path as sender_photo
                 FROM chat_messages m
                 INNER JOIN users u ON u.id = m.sender_id
                 WHERE m.thread_id = ?
                 ORDER BY m.id DESC
                 LIMIT $limit",
                [$threadId]
            ) ?: [];
            $rows = array_reverse($rows);
        }

        try {
            $db->query(
                "UPDATE chat_messages
                 SET read_at = " . ($db->isPgsql() ? 'CURRENT_TIMESTAMP' : 'NOW()') . "
                 WHERE thread_id = ?
                   AND sender_id <> ?
                   AND read_at IS NULL",
                [$threadId, $me]
            );
        } catch (Throwable $e) {
        }

        $messages = [];
        $lastId = 0;
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id > $lastId) $lastId = $id;
            $messages[] = [
                'id' => $id,
                'thread_id' => (int)($r['thread_id'] ?? 0),
                'sender_id' => (int)($r['sender_id'] ?? 0),
                'sender_name' => (string)($r['sender_name'] ?? ''),
                'sender_photo' => (string)($r['sender_photo'] ?? ''),
                'message' => (string)($r['message'] ?? ''),
                'created_at' => (string)($r['created_at'] ?? '')
            ];
        }

        $this->jsonResponse(['success' => true, 'messages' => $messages, 'last_id' => $lastId]);
    }

    public function send() {
        if (Auth::isAuditor()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $db = Database::getInstance();
        $this->ensureChatSchema($db);

        $me = (int)Session::get('user_id');
        if ($me <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $text = trim((string)($_POST['message'] ?? ''));
        if ($text === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Message is required.'], 400);
        }
        if (mb_strlen($text) > 2000) {
            $this->jsonResponse(['success' => false, 'message' => 'Message is too long.'], 400);
        }

        $threadId = (int)($_POST['thread_id'] ?? 0);
        $toUserId = (int)($_POST['to_user_id'] ?? 0);

        if ($threadId <= 0 && $toUserId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Recipient missing.'], 400);
        }

        if ($threadId <= 0) {
            if ($toUserId === $me || $toUserId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid recipient.'], 400);
            }
            if (!Auth::isAdmin() && !$this->canChatWith($db, $me, $toUserId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            $thread = $this->getOrCreateThread($db, $me, $toUserId);
            $threadId = (int)($thread['id'] ?? 0);
        }

        $thread = $db->fetch("SELECT * FROM chat_threads WHERE id = ? LIMIT 1", [$threadId]);
        if (!$thread) {
            $this->jsonResponse(['success' => false, 'message' => 'Thread not found.'], 404);
        }

        $a = (int)($thread['user_a'] ?? 0);
        $b = (int)($thread['user_b'] ?? 0);
        if ($me !== $a && $me !== $b && !Auth::isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }
        if (!Auth::isAdmin()) {
            $otherId = ($me === $a) ? $b : (($me === $b) ? $a : 0);
            if ($otherId <= 0 || !$this->canChatWith($db, $me, $otherId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
        }

        $nowExpr = $db->isPgsql() ? 'CURRENT_TIMESTAMP' : 'NOW()';
        try {
            if ($db->isPgsql()) {
                $row = $db->fetch(
                    "INSERT INTO chat_messages (thread_id, sender_id, message, created_at)
                     VALUES (?, ?, ?, $nowExpr)
                     RETURNING id",
                    [$threadId, $me, $text]
                );
                $messageId = (int)($row['id'] ?? 0);
            } else {
                $db->query(
                    "INSERT INTO chat_messages (thread_id, sender_id, message, created_at)
                     VALUES (?, ?, ?, $nowExpr)",
                    [$threadId, $me, $text]
                );
                $messageId = (int)$db->getConnection()->lastInsertId();
            }

            $db->query(
                "UPDATE chat_threads
                 SET last_message_at = $nowExpr
                 WHERE id = ?",
                [$threadId]
            );
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Unable to send message.'], 500);
        }

        $this->jsonResponse(['success' => true, 'thread_id' => $threadId, 'message_id' => $messageId]);
    }

    private function ensureChatSchema($db) {
        SchemaState::once('chat_schema_v1', function () use ($db) {
            if (!$db->tableExists('chat_threads')) {
                if ($db->isPgsql()) {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS chat_threads (
                            id BIGSERIAL PRIMARY KEY,
                            user_a INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                            user_b INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            last_message_at TIMESTAMPTZ NULL
                        );
                        CREATE INDEX IF NOT EXISTS idx_chat_threads_user_a ON chat_threads (user_a);
                        CREATE INDEX IF NOT EXISTS idx_chat_threads_user_b ON chat_threads (user_b);
                        CREATE INDEX IF NOT EXISTS idx_chat_threads_last_message_at ON chat_threads (last_message_at);"
                    );
                } else {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS chat_threads (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_a INT NOT NULL,
                            user_b INT NOT NULL,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            last_message_at DATETIME NULL,
                            KEY idx_chat_threads_user_a (user_a),
                            KEY idx_chat_threads_user_b (user_b),
                            KEY idx_chat_threads_last_message_at (last_message_at),
                            CONSTRAINT fk_chat_threads_user_a FOREIGN KEY (user_a) REFERENCES users(id) ON DELETE CASCADE,
                            CONSTRAINT fk_chat_threads_user_b FOREIGN KEY (user_b) REFERENCES users(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                }
            }

            if (!$db->tableExists('chat_messages')) {
                if ($db->isPgsql()) {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS chat_messages (
                            id BIGSERIAL PRIMARY KEY,
                            thread_id BIGINT NOT NULL REFERENCES chat_threads(id) ON DELETE CASCADE,
                            sender_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                            message TEXT NOT NULL,
                            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            read_at TIMESTAMPTZ NULL
                        );
                        CREATE INDEX IF NOT EXISTS idx_chat_messages_thread_id ON chat_messages (thread_id);
                        CREATE INDEX IF NOT EXISTS idx_chat_messages_created_at ON chat_messages (created_at);"
                    );
                } else {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS chat_messages (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            thread_id INT NOT NULL,
                            sender_id INT NOT NULL,
                            message TEXT NOT NULL,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            read_at DATETIME NULL,
                            KEY idx_chat_messages_thread_id (thread_id),
                            KEY idx_chat_messages_created_at (created_at),
                            CONSTRAINT fk_chat_messages_thread FOREIGN KEY (thread_id) REFERENCES chat_threads(id) ON DELETE CASCADE,
                            CONSTRAINT fk_chat_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                }
            }
        });
    }

    private function getOrCreateThread($db, int $u1, int $u2) {
        $u1 = (int)$u1;
        $u2 = (int)$u2;
        if ($u1 <= 0 || $u2 <= 0) return null;

        $existing = $db->fetch(
            "SELECT *
             FROM chat_threads
             WHERE (user_a = ? AND user_b = ?) OR (user_a = ? AND user_b = ?)
             ORDER BY id DESC
             LIMIT 1",
            [$u1, $u2, $u2, $u1]
        );
        if ($existing) return $existing;

        $nowExpr = $db->isPgsql() ? 'CURRENT_TIMESTAMP' : 'NOW()';
        if ($db->isPgsql()) {
            $row = $db->fetch(
                "INSERT INTO chat_threads (user_a, user_b, created_at)
                 VALUES (?, ?, $nowExpr)
                 RETURNING id",
                [$u1, $u2]
            );
            $id = (int)($row['id'] ?? 0);
        } else {
            $db->query(
                "INSERT INTO chat_threads (user_a, user_b, created_at)
                 VALUES (?, ?, $nowExpr)",
                [$u1, $u2]
            );
            $id = (int)$db->getConnection()->lastInsertId();
        }

        return $db->fetch("SELECT * FROM chat_threads WHERE id = ? LIMIT 1", [$id]);
    }

    private function emptyThreadMeta(): array {
        return [
            'unread_count' => 0,
            'last_message_id' => 0,
            'last_sender_id' => 0,
            'last_message' => ''
        ];
    }

    private function getThreadMeta($db, int $threadId, int $me): array {
        $meta = $this->emptyThreadMeta();
        $threadId = (int)$threadId;
        $me = (int)$me;
        if ($threadId <= 0 || $me <= 0) return $meta;

        $metaRow = $db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN sender_id <> ? AND read_at IS NULL THEN 1 ELSE 0 END), 0) AS unread_count,
                COALESCE(MAX(id), 0) AS last_message_id
             FROM chat_messages
             WHERE thread_id = ?",
            [$me, $threadId]
        );
        $meta['unread_count'] = (int)($metaRow['unread_count'] ?? 0);
        $meta['last_message_id'] = (int)($metaRow['last_message_id'] ?? 0);
        if ($meta['last_message_id'] > 0) {
            $lastRow = $db->fetch(
                "SELECT sender_id, message
                 FROM chat_messages
                 WHERE id = ?
                 LIMIT 1",
                [$meta['last_message_id']]
            );
            $meta['last_sender_id'] = (int)($lastRow['sender_id'] ?? 0);
            $meta['last_message'] = (string)($lastRow['message'] ?? '');
        }
        return $meta;
    }

    private function normalizeRole(string $role): string {
        $r = strtolower(trim($role));
        if ($r === '') return '';
        if (in_array($r, ['dept_head', 'department_head', 'department head', 'dept head', 'departmenthead'], true)) return 'dept_head';
        if (in_array($r, ['admin', 'administrator'], true)) return 'admin';
        if (in_array($r, ['finance_staff', 'finance staff', 'finance'], true)) return 'finance_staff';
        if (in_array($r, ['finance_head', 'finance head', 'head_of_finance', 'head of finance'], true)) return 'finance_head';
        if (in_array($r, ['visitation_team', 'visitation team', 'visitation'], true)) return 'visitation_team';
        if (in_array($r, ['auditor', 'audit'], true)) return 'auditor';
        if (in_array($r, ['pastor', 'reverend', 'rev', 'minister'], true)) return 'pastor';
        return preg_replace('/\s+/', '_', $r);
    }

    private function canChatWith($db, int $meId, int $otherUserId): bool {
        $meId = (int)$meId;
        $otherUserId = (int)$otherUserId;
        if ($meId <= 0 || $otherUserId <= 0) return false;
        if ($meId === $otherUserId) return false;

        $meRole = $this->normalizeRole((string)Session::get('user_role'));
        if ($meRole === 'admin') return true;
        if ($meRole === 'pastor') {
            $otherRow = $db->fetch("SELECT role FROM users WHERE id = ? LIMIT 1", [$otherUserId]);
            $otherRole = $this->normalizeRole((string)($otherRow['role'] ?? ''));
            return $otherRole !== '' && $otherRole !== 'auditor';
        }

        $otherRow = $db->fetch("SELECT role FROM users WHERE id = ? LIMIT 1", [$otherUserId]);
        $otherRole = $this->normalizeRole((string)($otherRow['role'] ?? ''));
        if ($otherRole === '') return false;
        if ($otherRole === 'pastor') return $meRole !== 'auditor';

        if ($meRole === 'finance_head') {
            return in_array($otherRole, ['finance_staff', 'dept_head', 'admin', 'pastor'], true);
        }
        if ($meRole === 'finance_staff' || $meRole === 'dept_head') {
            return in_array($otherRole, ['finance_head', 'admin', 'pastor'], true);
        }
        if ($meRole === 'visitation_team') {
            return in_array($otherRole, ['admin', 'pastor'], true);
        }
        return in_array($otherRole, ['admin', 'pastor'], true);
    }

    private function openAiRequest(string $url, string $apiKey, array $payload) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['success' => false, 'message' => $err ?: 'Request failed.'];
        }

        $data = json_decode($raw, true);
        if ($status < 200 || $status >= 300) {
            $msg = (string)($data['error']['message'] ?? 'Request failed.');
            return ['success' => false, 'message' => $msg];
        }

        return ['success' => true, 'data' => is_array($data) ? $data : []];
    }

    private function jsonPostRequest(string $url, array $payload) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['success' => false, 'message' => $err ?: 'Request failed.'];
        }

        $data = json_decode($raw, true);
        if ($status < 200 || $status >= 300) {
            $msg = (string)($data['error']['message'] ?? 'Request failed.');
            return ['success' => false, 'message' => $msg];
        }

        return ['success' => true, 'data' => is_array($data) ? $data : []];
    }

    private function jsonResponse(array $payload, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
