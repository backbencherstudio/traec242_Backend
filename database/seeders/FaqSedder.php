<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FaqSedder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What is Traec?',
                'answer' => 'Traec is a platform that connects service providers with customers, allowing them to easily find and book services online.'
            ],
            [
                'question' => 'How do I book a service?',
                'answer' => 'Simply browse through our categories, select a service provider that meets your needs, and follow the checkout process to schedule your appointment.'
            ],
            [
                'question' => 'Are the service providers on Traec verified?',
                'answer' => 'Yes, we conduct background checks and verify the credentials of all service providers before they are allowed to offer services on our platform.'
            ],
            [
                'question' => 'How can I become a service provider?',
                'answer' => 'You can click on the "Join as Provider" button in the menu, fill out your professional profile, and our team will review your application within 24-48 hours.'
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
