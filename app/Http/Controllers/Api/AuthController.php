<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\ApiVerificationSessionService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\UserRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(
        Request $request,
        UserRegistrationService $registration,
        EmailVerificationService $verificationService,
        ApiVerificationSessionService $verificationSessions,
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user = $registration->register($validated['email'], $validated['password']);

        $verificationService->generateAndSend($user);

        return response()->json([
            'verification_required' => true,
            'message' => __('auth.verification_sent'),
            'email' => $user->email,
            'verification_token' => $verificationSessions->issue($user),
            'remaining_seconds' => $verificationService->remainingSeconds($user->fresh()),
        ], 201);
    }

    public function login(
        Request $request,
        EmailVerificationService $verificationService,
        ApiVerificationSessionService $verificationSessions,
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureLoginIsNotRateLimited($validated['email']);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($this->loginThrottleKey($validated['email']), 60);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->loginThrottleKey($validated['email']));

        if ($user->email_verified_at === null) {
            if (! $verificationService->hasPendingCode($user)) {
                $verificationService->generateAndSend($user);
            }

            $user->refresh();

            return response()->json([
                'verification_required' => true,
                'message' => __('auth.verify_to_continue'),
                'email' => $user->email,
                'verification_token' => $verificationSessions->issue($user),
                'remaining_seconds' => $verificationService->remainingSeconds($user),
            ], 403);
        }

        return response()->json($this->authenticatedPayload($user));
    }

    public function verifyEmail(
        Request $request,
        EmailVerificationService $verificationService,
        ApiVerificationSessionService $verificationSessions,
    ): JsonResponse {
        $validated = $request->validate([
            'verification_token' => ['required', 'string', 'size:64'],
            'code' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ]);

        $user = $verificationSessions->userForToken($validated['verification_token']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'verification_token' => [__('auth.code_expired')],
            ]);
        }

        $verificationService->verify($user, $validated['code']);

        $verificationSessions->forget($validated['verification_token']);

        return response()->json([
            ...$this->authenticatedPayload($user->fresh()),
            'message' => __('auth.welcome_back'),
        ]);
    }

    public function resendVerification(
        Request $request,
        EmailVerificationService $verificationService,
        ApiVerificationSessionService $verificationSessions,
    ): JsonResponse {
        $validated = $request->validate([
            'verification_token' => ['required', 'string', 'size:64'],
        ]);

        $user = $verificationSessions->userForToken($validated['verification_token']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'verification_token' => [__('auth.code_expired')],
            ]);
        }

        if (! $verificationService->isExpired($user)) {
            return response()->json([
                'message' => __('auth.verification_sent'),
                'remaining_seconds' => $verificationService->remainingSeconds($user),
            ]);
        }

        $verificationService->resend($user);
        $user->refresh();

        return response()->json([
            'message' => __('auth.verification_sent'),
            'remaining_seconds' => $verificationService->remainingSeconds($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken !== null) {
            PersonalAccessToken::findToken($plainTextToken)?->delete();
        }

        return response()->json([
            'message' => __('auth.logout'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function authenticatedPayload(User $user): array
    {
        $token = $user->createToken('mobile')->plainTextToken;

        return [
            'verification_required' => false,
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ];
    }

    private function ensureLoginIsNotRateLimited(string $email): void
    {
        if (! RateLimiter::tooManyAttempts($this->loginThrottleKey($email), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->loginThrottleKey($email));

        throw ValidationException::withMessages([
            'email' => [__('auth.too_many_attempts', ['seconds' => $seconds])],
        ]);
    }

    private function loginThrottleKey(string $email): string
    {
        return 'api-login:'.strtolower($email).'|'.request()->ip();
    }
}
