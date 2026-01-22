<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeoGenerateSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:generate-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating slugs for Categories...');
        $categories = \App\Models\Category::whereNull('slug')->get();
        foreach ($categories as $category) {
            $name = $category->name ?? $category->admin_name ?? 'category-' . $category->id;
            $slug = \Illuminate\Support\Str::slug($name);
            $originalSlug = $slug;
            $count = 1;
            
            while (\App\Models\Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $category->slug = $slug;
            $category->save();
            $this->info("Category ID {$category->id}: {$slug}");
        }

        $this->info('Generating slugs for Products (ServiceAccounts)...');
        $products = \App\Models\ServiceAccount::whereNull('slug')->get();
        foreach ($products as $product) {
            $title = $product->title ?? 'product-' . $product->id;
            $slug = \Illuminate\Support\Str::slug($title);
            $originalSlug = $slug;
            $count = 1;
            
            while (\App\Models\ServiceAccount::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            
            $product->slug = $slug;
            $product->save();
            $this->info("Product ID {$product->id}: {$slug}");
        }

        $this->info('Done!');
    }
}
