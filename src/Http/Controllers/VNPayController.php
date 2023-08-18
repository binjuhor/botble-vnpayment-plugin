<?php

namespace Binjuhor\VNPay\Http\Controllers;

use Binjuhor\VNPay\Http\Requests\VNPayPaymentIPNRequest;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Binjuhor\VNPay\Http\Requests\VNPayPaymentCallbackRequest;
use Binjuhor\VNPay\Services\Gateways\VNPayPaymentService;
use Botble\Payment\Supports\PaymentHelper;
use Illuminate\Routing\Controller;

class VNPayController extends Controller
{
    public function getCallback(
        VNPayPaymentCallbackRequest $request,
        VNPayPaymentService $vnpayPaymentService,
        BaseHttpResponse $response
    ) {
        $status = $vnpayPaymentService->getPaymentStatus($request);
        $token = null;

        if (! $status) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->withInput()
                ->setMessage(__('Payment failed!'));
        }

        if(setting('payment_vnpay_mode') == 0) {
            $vnpayPaymentService->afterMakePayment($request->input());
        }

        if(setting('payment_vnpay_mode') == 1) {
            $token = $vnpayPaymentService->getToken($request->input());
        }

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL($token))
            ->setMessage(__('Checkout successfully!'));
    }
    public function getIPN(
        VNPayPaymentIPNRequest $request,
        VNPayPaymentService $vnpayPaymentService
    ) {
        if(setting('payment_vnpay_mode') == 0) {
            return response()->json([
                'RspCode' => '00',
                'Message' => 'Confirm Success'
            ]);
        }
        return response()->json($vnpayPaymentService->storeData($request->input()));
    }
}
