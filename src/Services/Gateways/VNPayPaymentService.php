<?php

namespace Binjuhor\VNPay\Services\Gateways;

use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentStatusEnum;
use Carbon\Carbon;

class VNPayPaymentService
{
    public function makePayment(array $data)
    {
        $vnp_TmnCode = setting('payment_vnpay_tmncode');
        $vnp_HashSecret = setting('payment_vnpay_secret');
        $vnp_Url = setting('payment_vnpay_client_url');
        $vnp_Returnurl = route('payments.vnpay.callback');
        $vnp_TxnRef = $data['orders'][0]->code;
        $vnp_Amount = $data['amount'];
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        $date = Carbon::now()->setTimezone(config('app.timezone'));

        $expire = $date
            ->addMinutes(15)
            ->format('YmdHis');

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount* 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => 'vn', // or en
            "vnp_OrderInfo" => "Thanh toan GD: ".  $vnp_TxnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate"=> $expire
        );

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;

        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }

        return $vnp_Url;
    }

    public function afterMakePayment(array $data): string|null
    {
        $chargeId = $data['vnp_TransactionNo'];
        $status = PaymentStatusEnum::FAILED;
        $order = Order::where('code', $data['vnp_TxnRef'])->first();
        $customer = $order->user;

        if ($this->getSecureHash() === $data['vnp_SecureHash']) {
            $status = $data['vnp_ResponseCode'] === '00' ? PaymentStatusEnum::COMPLETED : PaymentStatusEnum::PENDING;
        }

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount' => $data['vnp_Amount'],
            'currency' => 'VND',
            'charge_id' => $chargeId,
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'customer_type' => get_class($customer),
            'payment_channel' => VNPAY_PAYMENT_METHOD_NAME,
            'status' => $status,
        ]);

        return $chargeId;
    }

    public function getSecureHash()
    {
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (str_starts_with($key, "vnp_")) {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        return hash_hmac('sha512', $hashData, setting('payment_vnpay_secret'));
    }

    public function getPaymentStatus($request)
    {
        if ($this->getSecureHash() ===  $request['vnp_SecureHash']) {
            return $_GET['vnp_ResponseCode'] === '00' ? PaymentStatusEnum::COMPLETED : PaymentStatusEnum::PENDING;
        }
        return false;
    }

    public function supportedCurrencyCodes(): array
    {
        return ['VND'];
    }
}
