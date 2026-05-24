<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Quiz\Result;
use App\Livewire\Quiz\Take;
use App\Models\Quiz;
use App\Support\LocaleConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/locale/{locale}', function (Request $request, string $locale) {
    if (! LocaleConfig::isSupported($locale)) {
        abort(404);
    }

    session(['locale' => $locale]);

    if ($request->expectsJson() || $request->ajax()) {
        return response()->noContent();
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/', function () {
    $featuredQuizzes = Quiz::query()
        ->where('is_active', true)
        ->withCount(['questions' => fn ($query) => $query->where('is_active', true)])
        ->orderBy('id')
        ->get();

    return view('home', compact('featuredQuizzes'));
})->name('home');

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

Route::livewire('/quiz/session/{uuid}/result', Result::class)->name('quiz.result');
Route::livewire('/quiz/session/{uuid}', Take::class)->name('quiz.session');
Route::livewire('/quiz/{slug}', Take::class)->name('quiz.start');
