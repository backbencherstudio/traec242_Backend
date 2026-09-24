<?php

test('the custom maintenance page is displayed', function (): void {
    $this->artisan('down', ['--render' => 'errors::503'])->assertExitCode(0);

    try {
        $response = $this->get('/');

        $response
            ->assertStatus(503)
            ->assertSee("We'll be back before long.", false)
            ->assertSee('Maintenance Window Active')
            ->assertSee('Thank you for your patience.');
    } finally {
        $this->artisan('up')->assertExitCode(0);
    }
});
