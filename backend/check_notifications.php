<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$notifications = App\Models\AdminNotification::whereIn('id', [9, 8, 7, 45, 44])->get(['id', 'type', 'title', 'message']);

foreach($notifications as $n) {
    echo "ID: {$n->id}\n";
    echo "Type: {$n->type}\n";
    echo "Title: {$n->title}\n";
    echo "Message: {$n->message}\n";
    echo "Formatted Title: {$n->formatted_title}\n";
    echo "Formatted Message: {$n->formatted_message}\n";
    echo "---\n";
}
