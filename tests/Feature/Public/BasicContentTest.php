<?php

use App\Models\Content;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public home_response returns content and statistics', function (): void {
    Content::create(['key' => 'hero_title', 'value' => 'Find Best Providers']);
    createClientUser();
    createProviderUser();

    $response = $this->getJson('/api/home_response');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.content.hero_title', 'Find Best Providers')
        ->assertJsonPath('data.other_data.total_user', 1)
        ->assertJsonPath('data.other_data.total_provider', 1);
});

test('public faq returns all faqs', function (): void {
    Faq::create([
        'question' => 'How does booking work?',
        'answer' => 'Choose a provider and select a time slot.',
    ]);

    $response = $this->getJson('/api/faq');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.question', 'How does booking work?');
});

test('public privacy returns privacy policy content', function (): void {
    PrivacyPolicy::create([
        'title' => 'Privacy Policy',
        'description' => 'Your privacy is important to us.',
    ]);

    $response = $this->getJson('/api/privacy');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Privacy Policy')
        ->assertJsonPath('data.description', 'Your privacy is important to us.');
});
