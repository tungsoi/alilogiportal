<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use Illuminate\Console\Command;

class ChangeLinkProductImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $items = OrderItem::where('product_image', '!=', 'Không ảnh')
        ->where('product_image', 'not like', "https%")
        ->where('product_image', 'not like', "//%")
        ->where('product_image', 'not like', "images/%")
        ->get();

        foreach ($items as $item) {
            $link = $item->product_image;
            $item->product_image = 'images/'.$link;
            $item->save();
        }
        dd($items->count());
    }
}
