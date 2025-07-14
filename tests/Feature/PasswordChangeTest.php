<?php
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\ExtendedRolePermissionSeeder::class);

    $this->user = User::factory()->create(['force_password_change' => true]);
});

test('redirect to change password on first login', function () {
    $this->actingAs($this->user)
        ->get('/admin')
        ->assertRedirect('/admin/change-password');
});
