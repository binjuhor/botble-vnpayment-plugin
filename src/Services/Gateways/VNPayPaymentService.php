<?php

namespace Binjuhor\VNPay\Services\Gateways;

use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;

class VNPayPaymentService
{
    public function makePayment(array $data)
    {
        $vnp_TmnCode = setting('payment_vnpay_tmncode');
        $vnp_HashSecret = setting('payment_vnpay_secret');
        $vnp_Url = setting('payment_vnpay_client_url');
        $vnp_Returnurl = route('payments.vnpay.callback');

        $vnp_TxnRef = $data['orders'][0]->id; //Mã giao dịch thanh toán tham chiếu của merchant
        $vnp_Amount = $data['amount']; // Số tiền thanh toán
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR']; //IP Khách hàng thanh toán

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

    public function afterMakePayment(array $data): string
    {
        $chargeId = $data['vnp_TransactionNo'];
        $status = PaymentStatusEnum::FAILED;
        $order = Order::find($data['vnp_TxnRef']);

        if($order !== NULL) {
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
        }

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

    public function getToken(array $data)
    {
        $order = Order::find($data['vnp_TxnRef']);
        return $order->token;
    }

    public function supportedCurrencyCodes(): array
    {
        return ['VND'];
    }

    /**
     * This function run on production for IPN
     * Check more here: https://sandbox.vnpayment.vn/apis/docs/huong-dan-tich-hop/#code-ipn-url
     *
     * @param array $data
     * @return array
     */
    public function storeData($data)
    {
        $vnp_HashSecret = setting('payment_vnpay_secret');
        $chargeId = $data['vnp_TransactionNo'];
        $inputData = array();
        $returnData = array();

        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $hashData = http_build_query($inputData);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        $vnpTranId = $inputData['vnp_TransactionNo']; //Mã giao dịch tại VNPAY
        $vnp_BankCode = $inputData['vnp_BankCode']; //Ngân hàng thanh toán
        $vnp_Amount = $inputData['vnp_Amount']/100; // Số tiền thanh toán VNPAY phản hồi

        $status = new PaymentStatusEnum; // Là trạng thái thanh toán của giao dịch chưa có IPN lưu tại hệ thống của merchant chiều khởi tạo URL thanh toán.
        $orderId = $inputData['vnp_TxnRef'];

        try {
            //Kiểm tra checksum của dữ liệu
            if ($secureHash == $vnp_SecureHash) {
                $order = Order::find($orderId);

                if ($order != NULL) {
                    if($order->amount == $vnp_Amount) {
                        $customer = $order->user;


                        if ($order->status !== NULL && $order->payment->status == $status) {
                            if ($inputData['vnp_ResponseCode'] == '00' || $inputData['vnp_TransactionStatus'] == '00') {
                                $status = PaymentStatusEnum::COMPLETED;
                            } else {
                                $status = PaymentStatusEnum::FAILED;
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

                            //Trả kết quả về cho VNPAY: Website/APP TMĐT ghi nhận yêu cầu thành công
                            $returnData['RspCode'] = '00';
                            $returnData['Message'] = 'Confirm Success';
                        } else {
                            $returnData['RspCode'] = '02';
                            $returnData['Message'] = 'Order already confirmed';
                        }
                    }
                    else {
                        $returnData['RspCode'] = '04';
                        $returnData['Message'] = 'invalid amount';
                    }
                } else {
                    $returnData['RspCode'] = '01';
                    $returnData['Message'] = 'Order not found';
                }
            } else {
                $returnData['RspCode'] = '97';
                $returnData['Message'] = 'Invalid signature';
            }
        } catch (Exception $e) {
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknow error';
        }

        return $returnData;
    }
}
