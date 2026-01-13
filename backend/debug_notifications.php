<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$notifications = App\Models\AdminNotification::whereIn('id', [9, 8, 7, 45, 44])->get();

foreach($notifications as $n) {
    echo "=== ID: {$n->id} ===\n";
    echo "Type (raw): [{$n->type}]\n";
    echo "Title (raw): [{$n->title}]\n";
    echo "Message (raw): [{$n->message}]\n";
    echo "Title (formatted): [{$n->formatted_title}]\n";
    echo "Message (formatted): [{$n->formatted_message}]\n";
    echo "Title lower no spaces: [" . mb_strtolower(str_replace(' ', '', $n->title)) . "]\n";
    echo "Has 'purcha': " . (strpos(mb_strtolower(str_replace(' ', '', $n->title)), 'purcha') !== false ? 'YES' : 'NO') . "\n";
    echo "\n";
}
