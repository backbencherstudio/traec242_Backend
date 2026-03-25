<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keys = [
            'image_1' => '',
            'image_2' => '',
            'image_3' => '',
            'heading' => 'Welcome',
            'sub_heading' => 'Sub heading',
            'input_placeholder' => 'Email address',
            'button_text' => 'Submit'
        ];

        foreach ($keys as $key => $value) {
            Content::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
