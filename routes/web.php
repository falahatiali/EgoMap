<?php

use App\Support\LocaleConfig;
use Illuminate\Http\Request;
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

Route::get('/', fn () => view('home'))->name('home');
