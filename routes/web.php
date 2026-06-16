<?php

use App\Http\Controllers\AliController;
use App\Http\Controllers\BillingAppReturnController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Home\Protocol;
use App\Livewire\Missions\Catalog as MissionsCatalog;
use App\Livewire\Missions\Show as MissionShow;
use App\Livewire\Missions\Workspace as MissionWorkspace;
use App\Livewire\NoContact\Show as NoContactShow;
use App\Livewire\Pricing\Show as PricingShow;
use App\Livewire\Profile\ProgramShow;
use App\Livewire\Profile\Rewards as ProfileRewards;
use App\Livewire\Profile\Show;
use App\Livewire\Profile\TestShow;
use App\Livewire\Quiz\Result;
use App\Livewire\Quiz\Take;
use App\Livewire\Virtue\VirtueHabitPicker;
use App\Livewire\Virtue\VirtueHub;
use App\Livewire\Virtue\VirtueRoutineDetail;
use App\Support\LocaleConfig;
use App\Support\LocaleUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/billing/app-return', BillingAppReturnController::class)->name('billing.app-return');

Route::get('/', function () {
    $locale = session('locale');

    if (is_string($locale) && LocaleConfig::isSupported($locale)) {
        return redirect(LocaleUrl::home($locale));
    }

    return view('welcome.language');
})->name('welcome');

Route::match(['get', 'post'], '/locale/{locale}', function (Request $request, string $locale) {
    if (! LocaleConfig::isSupported($locale)) {
        abort(404);
    }

    session(['locale' => $locale]);

    if ($request->expectsJson() || $request->ajax()) {
        return response()->noContent();
    }

    return redirect(LocaleUrl::switchFromReferer($locale, $request->headers->get('referer')));
})->name('locale.switch');

Route::prefix('{locale}')
    ->where(['locale' => 'en|fa'])
    ->group(function (): void {
        Route::livewire('/', Protocol::class)->name('home');

        Route::middleware('guest')->group(function (): void {
            Route::livewire('/register', Register::class)->name('register');
            Route::livewire('/login', Login::class)->name('login');
            Route::livewire('/verify-email', VerifyEmail::class)->name('verification.notice');
        });

        Route::post('/logout', function () {
            Auth::logout();

            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('home');
        })->middleware('auth')->name('logout');

        Route::livewire('/start', Protocol::class)->name('onboarding');
        Route::livewire('/no-contact', NoContactShow::class)->name('no-contact');
        Route::livewire('/pricing', PricingShow::class)->name('pricing');

        Route::livewire('/profile', Show::class)->middleware('auth')->name('profile');
        Route::livewire('/profile/rewards', ProfileRewards::class)->middleware('auth')->name('profile.rewards');
        Route::livewire('/profile/tests/{uuid}', TestShow::class)->middleware('auth')->name('profile.test.show');
        Route::livewire('/profile/programs/{uuid}', ProgramShow::class)->middleware('auth')->name('profile.program.show');

        Route::middleware('auth')->prefix('missions')->name('missions.')->group(function (): void {
            Route::livewire('/', MissionsCatalog::class)->name('catalog');
            Route::livewire('/templates/{template}', MissionShow::class)->name('show');
            Route::livewire('/active/{enrollment}', MissionWorkspace::class)->name('workspace');
        });

        Route::middleware('auth')->prefix('virtue')->name('virtue.')->group(function (): void {
            Route::livewire('/', VirtueHub::class)->name('hub');
            Route::livewire('/habits', VirtueHabitPicker::class)->name('habits');
            Route::livewire('/routines/{routineId}', VirtueRoutineDetail::class)->name('routine');
        });

        Route::livewire('/quiz/session/{uuid}/result', Result::class)->name('quiz.result');
        Route::livewire('/quiz/session/{uuid}', Take::class)->name('quiz.session');
        Route::livewire('/quiz/{slug}', Take::class)->name('quiz.start');
    });

if (app()->environment('local')) {
    Route::get('/ali/test', [AliController::class, 'ali'])->name('ali.test');
}
