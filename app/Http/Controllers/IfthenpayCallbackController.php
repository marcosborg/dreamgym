<?php

namespace App\Http\Controllers;

use App\Services\Payments\IfthenpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class IfthenpayCallbackController extends Controller
{
    public function __invoke(Request $request, IfthenpayPaymentService $payments): Response
    {
        try {
            $payment = $payments->handleCallback($request->query());
        } catch (Throwable $exception) {
            Log::warning('Ifthenpay callback rejected or fulfillment failed.', [
                'exception_type' => get_class($exception),
                'order_id' => is_string($request->query('oid')) ? substr($request->query('oid'), 0, 40) : null,
            ]);

            return response('ERROR', 422);
        }

        return response($payment ? 'OK' : 'IGNORED');
    }
}
