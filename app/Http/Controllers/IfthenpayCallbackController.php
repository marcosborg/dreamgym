<?php

namespace App\Http\Controllers;

use App\Services\Payments\IfthenpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class IfthenpayCallbackController extends Controller
{
    public function __invoke(Request $request, IfthenpayPaymentService $payments): Response
    {
        try {
            $payment = $payments->handleCallback($request->query());
        } catch (Throwable) {
            return response('ERROR', 422);
        }

        return response($payment ? 'OK' : 'IGNORED');
    }
}
