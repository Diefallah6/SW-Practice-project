<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_a_hashed_password(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'password' => 'password123',
            'phone_number' => '0912345678',
            'role' => 'user',
        ])->assertCreated()
            ->assertJsonPath('email', 'ali@example.com')
            ->assertJsonPath('phone_number', '0912345678')
            ->assertJsonPath('role', 'user')
            ->assertJsonMissingPath('password');

        $this->assertTrue(Hash::check('password123', User::firstOrFail()->password));
    }

    public function test_create_rejects_missing_fields_and_duplicate_email(): void
    {
        $this->postJson('/api/users', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone_number', 'role']);

        $user = User::factory()->create();

        $this->postJson('/api/users', [
            'name' => 'Ali',
            'email' => $user->email,
            'password' => 'password123',
            'phone_number' => '0912345678',
            'role' => 'user',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
