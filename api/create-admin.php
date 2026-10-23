<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php api/create-admin.php admin@example.com\n");
    exit(2);
}

fwrite(STDOUT, "New password (minimum 12 characters): ");
$password = trim((string)fgets(STDIN));
if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(2);
}

$statement = database()->prepare('INSERT INTO admins (email, password_hash) VALUES (:email, :password_hash)');
try {
    $statement->execute([
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "An admin with that email already exists.\n");
        exit(1);
    }
    throw $exception;
}

fwrite(STDOUT, "Admin account created for {$email}.\n");
