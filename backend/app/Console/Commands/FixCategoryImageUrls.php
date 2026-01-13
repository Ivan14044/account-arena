<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixCategoryImageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:fix-image-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix category image URLs - convert relative paths to absolute URLs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing category image URLs...');

        $categories = Category::whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get();

        $fixed = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            $imageUrl = $category->image_url;

            // Если URL уже абсолютный, пропускаем
            if (str_starts_with($imageUrl, 'http')) {
                $skipped++;
                continue;
            }

            // Преобразуем относительный путь в абсолютный URL
            $absoluteUrl = url($imageUrl);
            
            $category->image_url = $absoluteUrl;
            $category->save();

            $this->line("Fixed category #{$category->id}: {$imageUrl} -> {$absoluteUrl}");
            $fixed++;
        }

        $this->info("Fixed {$fixed} category image URLs. Skipped {$skipped} (already absolute).");

        return 0;
    }
}
