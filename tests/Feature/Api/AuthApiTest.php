<?php

namespace Tests\Feature\Api;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\Auth\ApiVerificationSessionService;
use App\Services\Auth\EmailVerificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bootstrap_endpoint_returns_landing_payload(): void
    {
        $response = $this->getJson('/api/v1/bootstrap', [
            'Accept-Language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('locale', 'en')
            ->assertJsonStructure([
                'landing' => ['hero_title_1', 'steps', 'panel'],
                'quiz' => ['featured_slug'],
                'auth' => ['login_title'],
            ]);
    }

    public function test_register_returns_verification_token(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'mobile@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('verification_required', true)
            ->assertJsonPath('email', 'mobile@example.com')
            ->assertJsonStructure(['verification_token', 'remaining_seconds']);

        $user = User::query()->where('email', 'mobile@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('member'));

        Mail::assertQueued(EmailVerificationCodeMail::class);
    }

    public function test_verify_email_returns_api_token(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/register', [
            'email' => 'verify-mobile@example.com',
            'password' => 'password123',
        ]);

        $user = User::query()->where('email', 'verify-mobile@example.com')->firstOrFail();
        $verificationToken = app(ApiVerificationSessionService::class)->issue($user);

        $code = '4321';
        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(2),
        ])->save();

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'verification_token' => $verificationToken,
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonPath('verification_required', false)
            ->assertJsonPath('user.email', 'verify-mobile@example.com')
            ->assertJsonStructure(['token', 'user' => ['uuid']]);

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
    }

    public function test_login_with_unverified_account_returns_verification_payload(): void
    {
        Mail::fake();

        User::factory()->unverified()->create([
            'email' => 'pending-mobile@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pending-mobile@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('verification_required', true)
            ->assertJsonPath('email', 'pending-mobile@example.com')
            ->assertJsonStructure(['verification_token', 'remaining_seconds']);

        Mail::assertQueued(EmailVerificationCodeMail::class);
    }

    public function test_verified_user_can_login_and_access_me(): void
    {
        $user = User::factory()->create([
            'email' => 'member-mobile@example.com',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'member-mobile@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('user.email', 'member-mobile@example.com');

        $token = $login->json('token');

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('user.uuid', $user->uuid);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $plainTextToken = $user->createToken('mobile')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_expired_verification_code_can_be_resent(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email_verification_code' => Hash::make('1111'),
            'email_verification_expires_at' => now()->subMinute(),
        ]);

        $verificationToken = app(ApiVerificationSessionService::class)->issue($user);

        $response = $this->postJson('/api/v1/auth/resend-verification', [
            'verification_token' => $verificationToken,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['remaining_seconds']);

        Mail::assertQueued(EmailVerificationCodeMail::class);

        $user->refresh();

        $this->assertTrue(app(EmailVerificationService::class)->remainingSeconds($user) > 0);
    }
}
