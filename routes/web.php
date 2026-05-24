<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/locale/{locale}', function (Request $request, string $locale) {
    if (! in_array($locale, ['en', 'fa'], true)) {
        abort(404);
    }

    session(['locale' => $locale]);

    if ($request->expectsJson() || $request->ajax()) {
        return response()->noContent();
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/', fn () => view('home'))->name('home');
