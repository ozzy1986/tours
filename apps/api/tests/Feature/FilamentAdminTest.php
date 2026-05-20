<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves filament admin login', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('redirects admin root to login when guest', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('allows admin user to open tours list', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/tours')
        ->assertOk();
});

it('allows admin user to open llm settings', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/llm-settings')
        ->assertOk();
});
