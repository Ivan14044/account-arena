<?php

/**
 * Сброс пароля существующего пользователя
 * Запустить: php backend/reset-user-password.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php();
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "════════════════════════════════════════════════════════\n";
echo "🔑 СБРОС ПАРОЛЯ ПОЛЬЗОВАТЕЛЯ\n";
echo "════════════════════════════════════════════════════════\n\n";

// Показываем список пользователей
$users = User::where('is_admin', false)->get();

if ($users->isEmpty()) {
    echo "❌ Не найдено ни одного пользователя\n\n";
    exit(1);
}

echo "📋 СПИСОК ПОЛЬЗОВАТЕЛЕЙ:\n\n";

foreach ($users as $index => $user) {
    echo "   " . ($index + 1) . ". ID: {$user->id} | {$user->email} | {$user->name}\n";
}

echo "\n";
echo "Введите номер пользователя (1-{$users->count()}): ";
$choice = trim(fgets(STDIN));

$selectedIndex = (int)$choice - 1;

if (!isset($users[$selectedIndex])) {
    echo "\n❌ Неверный выбор\n\n";
    exit(1);
}

$selectedUser = $users[$selectedIndex];

echo "\n✅ Выбран пользователь:\n";
echo "   ID: {$selectedUser->id}\n";
echo "   Email: {$selectedUser->email}\n";
echo "   Имя: {$selectedUser->name}\n\n";

echo "Введите новый пароль (или нажмите Enter для 'password'): ";
$newPassword = trim(fgets(STDIN));

if (empty($newPassword)) {
    $newPassword = 'password';
}

// Обновляем пароль
$selectedUser->password = Hash::make($newPassword);
$selectedUser->save();

echo "\n";
echo "═══════════════════════════════════════════\n";
echo "✅ ПАРОЛЬ УСПЕШНО ИЗМЕНЕН!\n";
echo "═══════════════════════════════════════════\n";
echo "ID: {$selectedUser->id}\n";
echo "Email: {$selectedUser->email}\n";
echo "Новый пароль: {$newPassword}\n";
echo "═══════════════════════════════════════════\n\n";

echo "💡 ПОПРОБУЙТЕ ВОЙТИ:\n\n";
echo "   URL: http://localhost:5173/login\n";
echo "   Email: {$selectedUser->email}\n";
echo "   Password: {$newPassword}\n\n";

echo "✅ Готово!\n\n";


