<?php
declare(strict_types=1);

// Uso (desde el host): scripts/reset_password.sh usuario
if (PHP_SAPI !== 'cli') {
    exit(1);
}
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/core/helpers.php';
require BASE_PATH . '/core/autoload.php';

$username = (string) ($argv[1] ?? '');
$password = rtrim((string) fgets(STDIN), "\r\n");

if (strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "La contraseña debe tener entre 8 y 72 caracteres.\n");
    exit(1);
}
$user = User::findByUsername($username);
if ($user === null) {
    fwrite(STDERR, "No existe el usuario «$username».\n");
    exit(1);
}
User::updateHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
Database::query('DELETE FROM login_attempts WHERE username = :u', ['u' => $username]);
echo "Contraseña restablecida para «$username».\n";
