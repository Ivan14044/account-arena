<?php

namespace Database\Seeders;

use App\Models\Option;
use Illuminate\Database\Seeder;

class OptionSeeder extends Seeder
{
    public function run(): void
    {
        Option::set('currency', 'USD');
        Option::set('telegram_support_link', 'https://t.me/support');
    }
}

