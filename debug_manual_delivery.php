<?php

use App\Models\Purchase;
use App\Models\ServiceAccount;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Manual Delivery Debug ---\n";

$allProcessing = Purchase::where('status', 'processing')->count();
echo "Total purchases in 'processing' status: {$allProcessing}\n";

$manualProcessing = Purchase::where('status', 'processing')
    ->whereHas('serviceAccount', function($q) {
        $q->where('delivery_type', 'manual');
    })
    ->count();
echo "Purchases in 'processing' with delivery_type 'manual': {$manualProcessing}\n";

$automaticProcessing = Purchase::where('status', 'processing')
    ->whereHas('serviceAccount', function($q) {
        $q->where('delivery_type', 'automatic');
    })
    ->count();
echo "Purchases in 'processing' with delivery_type 'automatic': {$automaticProcessing}\n";

$noAccountProcessing = Purchase::where('status', 'processing')
    ->whereDoesntHave('serviceAccount')
    ->count();
echo "Purchases in 'processing' with NO serviceAccount: {$noAccountProcessing}\n";

echo "\n--- Sample Processing Purchase ---\n";
$sample = Purchase::where('status', 'processing')->first();
if ($sample) {
    echo "ID: {$sample->id}, Order: {$sample->order_number}\n";
    echo "Product ID: {$sample->service_account_id}\n";
    if ($sample->serviceAccount) {
        echo "Product Title: {$sample->serviceAccount->title}\n";
        echo "Product Delivery Type: {$sample->serviceAccount->delivery_type}\n";
    } else {
        echo "Product Relationship: NULL\n";
    }
} else {
    echo "No processing purchases found.\n";
}

echo "\n--- Cache Status ---\n";
$cachedCount = Cache::get('manual_delivery_pending_count');
echo "Cached count (manual_delivery_pending_count): " . ($cachedCount ?? 'NULL') . "\n";
