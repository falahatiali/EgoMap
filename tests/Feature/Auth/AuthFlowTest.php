<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmail;
use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_register_sends_verification_code_and_redirects(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->call('register')
            ->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code);
        $this->assertNotNull($user->email_verification_expires_at);
        $this->assertTrue($user->hasRole('member'));

        Mail::assertQueued(EmailVerificationCodeMail::class, fn (EmailVerificationCodeMail $mail) => $mail->hasTo('newuser@example.com'));

        $this->assertSame($user->id, session('pending_verification_user_id'));
    }

    public function test_verify_code_logs_user_in(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('email', 'verify@example.com')
            ->set('password', 'password123')
            ->call('register');

        $user = User::query()->where('email', 'verify@example.com')->firstOrFail();

        $code = '1234';
        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(2),
        ])->save();

        session(['pending_verification_user_id' => $user->id]);

        Livewire::test(VerifyEmail::class)
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('profile'));

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_code);
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_login_with_unverified_account_redirects_to_verify(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'pending@example.com',
            'password' => 'password123',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'pending@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('verification.notice'));

        $this->assertFalse(Auth::check());
        $this->assertSame($user->id, session('pending_verification_user_id'));
        Mail::assertQueued(EmailVerificationCodeMail::class);
    }

    public function test_verified_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password123',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'member@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('profile'));

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_remaining_seconds_persist_from_server_expiry(): void
    {
        $user = User::factory()->unverified()->create([
            'email_verification_code' => Hash::make('9999'),
            'email_verification_expires_at' => now()->addSeconds(90),
        ]);

        session(['pending_verification_user_id' => $user->id]);

        $component = Livewire::test(VerifyEmail::class);

        $this->assertGreaterThanOrEqual(88, $component->get('remainingSeconds'));
        $this->assertLessThanOrEqual(90, $component->get('remainingSeconds'));
    }

    public function test_expired_code_can_be_resent(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email_verification_code' => Hash::make('1111'),
            'email_verification_expires_at' => now()->subMinute(),
        ]);

        session(['pending_verification_user_id' => $user->id]);

        Livewire::test(VerifyEmail::class)
            ->call('resendCode');

        Mail::assertQueued(EmailVerificationCodeMail::class);

        $user->refresh();

        $this->assertTrue(app(EmailVerificationService::class)->remainingSeconds($user) > 0);
    }

    public function test_guest_can_view_register_and_login_pages(): void
    {
        $this->get(route('register', ['locale' => 'en']))->assertOk();
        $this->get(route('login', ['locale' => 'en']))->assertOk();
    }
}
