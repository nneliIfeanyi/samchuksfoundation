<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$method = request_method('GET', 'POST', 'DELETE');
start_admin_session();

if ($method === 'GET') {
    $adminId = $_SESSION['admin_id'] ?? null;
    if (!is_int($adminId) || $adminId < 1) {
        json_response(['authenticated' => false]);
    }
    json_response([
        'authenticated' => true,
        'csrfToken' => $_SESSION['csrf_token'] ?? '',
        'email' => $_SESSION['admin_email'] ?? '',
    ]);
}

if ($method === 'POST') {
    $input = request_json();
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        json_response(['error' => 'Email or password is incorrect.'], 401);
    }

    $statement = database()->prepare('SELECT id, email, password_hash FROM admins WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $admin = $statement->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        json_response(['error' => 'Email or password is incorrect.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    json_response(['authenticated' => true, 'csrfToken' => $_SESSION['csrf_token'], 'email' => $admin['email']]);
}

require_admin(true);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Strict',
    ]);
}
session_destroy();
json_response(['authenticated' => false]);
