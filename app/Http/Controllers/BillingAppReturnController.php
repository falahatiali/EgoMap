<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BillingAppReturnController extends Controller
{
    public function __invoke(Request $request): View
    {
        $checkout = (string) $request->query('checkout', '');
        $sessionId = $request->query('session_id');

        return view('billing.app-return', [
            'success' => $checkout === 'success',
            'cancelled' => $checkout === 'cancelled',
            'sessionId' => is_string($sessionId) && $sessionId !== '' ? $sessionId : null,
        ]);
    }
}
