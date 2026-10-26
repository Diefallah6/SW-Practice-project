<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $this->deleteJson('/api/users/'.$user->id)->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->deleteJson('/api/users/'.$user->id)->assertNotFound();
    }

    public function test_user_can_be_updated_and_keep_their_own_email(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $this->patchJson('/api/users/'.$user->id, [
            'name' => 'Updated name',
            'email' => $user->email,
            'phone_number' => '+218912345678',
            'role' => 'admin',
        ])->assertOk()->assertJsonPath('name', 'Updated name')
            ->assertJsonPath('phone_number', '+218912345678')
            ->assertJsonPath('role', 'admin')
            ->assertJsonMissingPath('password');

        $this->assertSame($oldPassword, $user->fresh()->password);

        $this->putJson('/api/users/'.$user->id, [
            'password' => 'newpassword123',
        ])->assertOk();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_update_rejects_duplicate_email_and_short_password(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->patchJson('/api/users/'.$user->id, [
            'email' => $other->email,
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);

        $this->patchJson('/api/users/999', ['name' => 'Ali'])->assertNotFound();
    }

    public function test_users_can_be_listed_and_viewed_without_passwords(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/users')->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $user->id)
            ->assertJsonMissingPath('0.password');

        $this->getJson('/api/users/'.$user->id)->assertOk()
            ->assertJsonPath('email', $user->email)
            ->assertJsonMissingPath('password');

        $this->getJson('/api/users/999')->assertNotFound();
    }

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
