<?php

class AuthController {
    public function showLogin() {
        if (Auth::check()) {
            $role = strtolower(trim((string)Session::get('user_role')));
            if ($role === 'auditor') {
                header('Location: auditor');
            } elseif ($role === 'pastor') {
                header('Location: pastor');
            } else {
                header('Location: dashboard');
            }
            exit;
        }
        
        // Render login view without the main layout
        $viewPath = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewPath)) {
            $mode = 'login';
            require_once $viewPath;
        } else {
            die("Login view not found");
        }
    }

    public function login() {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $loginType = $_POST['login_type'] ?? null;
        
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $result = Auth::login($login, $password, $loginType);
        if (!empty($result['success'])) {
            $role = strtolower(trim((string)Session::get('user_role')));
            if ($role === 'auditor') {
                header("Location: $base/auditor");
            } elseif ($role === 'pastor') {
                header("Location: $base/pastor");
            } else {
                header("Location: $base/dashboard");
            }
        } else {
            if (($result['reason'] ?? '') === 'permission_mismatch') {
                Session::flash('error', 'Permission level does not match this account. Please choose the correct permission level.');
            } else {
                Session::flash('error', 'Invalid username/email or password');
            }
            header("Location: $base/login");
        }
        exit;
    }

    public function logout() {
        Auth::logout();
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/login");
        exit;
    }

    public function forgotPassword() {
        if (Auth::check()) {
            header('Location: dashboard');
            exit;
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $login = $_POST['login'] ?? '';
            $result = Auth::requestPasswordReset($login);
            if (!empty($result['success'])) {
                Session::flash('success', 'Reset request submitted. Please contact the admin to approve it.');
            } else {
                Session::flash('error', 'Account not found. Enter your username or email.');
            }
            header("Location: $base/forgot-password");
            exit;
        }

        $viewPath = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewPath)) {
            $mode = 'forgot';
            require_once $viewPath;
        } else {
            die("Login view not found");
        }
    }

    public function resetPassword() {
        if (Auth::check()) {
            header('Location: dashboard');
            exit;
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            if ($password === '' || strlen($password) < 6) {
                Session::flash('error', 'Password must be at least 6 characters.');
                header("Location: $base/reset-password?token=" . urlencode((string)$token));
                exit;
            }

            if ($password !== $confirm) {
                Session::flash('error', 'Passwords do not match.');
                header("Location: $base/reset-password?token=" . urlencode((string)$token));
                exit;
            }

            $result = Auth::resetPasswordWithToken($token, $password);
            if (!empty($result['success'])) {
                Session::flash('success', 'Password updated. You can login now.');
                header("Location: $base/login");
                exit;
            }

            Session::flash('error', 'Reset link is invalid or expired. Generate a new one.');
            header("Location: $base/forgot-password");
            exit;
        }

        $token = $_GET['token'] ?? '';
        $viewPath = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewPath)) {
            $mode = 'reset';
            require_once $viewPath;
        } else {
            die("Login view not found");
        }
    }
}
