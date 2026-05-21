<?php

declare(strict_types=1);

use App\Filament\Resources\CategoryResource\Pages\ManageCategories;
use App\Filament\Resources\TourResource\Pages\EditTour;
use App\Filament\Resources\TourResource\Pages\ListTours;
use App\Filament\Pages\LlmSettingsPage;
use App\Models\Category;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('serves filament admin login', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('redirects admin root to login when guest', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('denies non-admin user access to filament panel', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/tours')
        ->assertForbidden();
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

it('allows admin user to open categories', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/categories')
        ->assertOk();
});

it('renders tours list livewire table', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $tour = Tour::factory()->create();

    Livewire::actingAs($admin)
        ->test(ListTours::class)
        ->assertCanSeeTableRecords([$tour]);
});

it('renders tour edit form', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $tour = Tour::factory()->create();

    Livewire::actingAs($admin)
        ->test(EditTour::class, ['record' => $tour->getRouteKey()])
        ->assertOk()
        ->assertFormExists();
});

it('renders categories manage page livewire', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::query()->create([
        'slug' => 'test-cat',
        'name' => 'Test Category',
        'position' => 1,
    ]);

    Livewire::actingAs($admin)
        ->test(ManageCategories::class)
        ->assertCanSeeTableRecords([$category]);
});

it('renders llm settings livewire form', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(LlmSettingsPage::class)
        ->assertFormExists();
});
