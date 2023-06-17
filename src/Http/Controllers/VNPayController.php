<?php

namespace Binjuhor\VNPay\Http\Controllers;

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

        if (! $status) {
            return $response
                ->setError()
                ->setNextUrl(PaymentHelper::getCancelURL())
                ->withInput()
                ->setMessage(__('Payment failed!'));
        }

        $vnpayPaymentService->afterMakePayment($request->input());

        return $response
            ->setNextUrl(PaymentHelper::getRedirectURL())
            ->setMessage(__('Checkout successfully!'));
    }
}
