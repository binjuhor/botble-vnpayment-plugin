<?php

namespace Binjuhor\VNPay\Providers;

use Botble\Payment\Enums\PaymentMethodEnum;
use Binjuhor\VNPay\Services\Gateways\VNPayPaymentService;
use Collective\Html\HtmlFacade as Html;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Botble\Payment\Facades\PaymentMethods;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, [$this, 'registerVNPayMethod'], 2, 2);

        $this->app->booted(function () {
            add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, [$this, 'checkoutWithVNPay'], 2, 2);
        });

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, [$this, 'addPaymentSettings'], 2);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['VNPAY'] = VNPAY_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 2, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == VNPAY_PAYMENT_METHOD_NAME) {
                $value = 'VNPay';
            }

            return $value;
        }, 2, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == VNPAY_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )
                    ->toHtml();
            }

            return $value;
        }, 2, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == VNPAY_PAYMENT_METHOD_NAME) {
                $data = VNPayPaymentService::class;
            }

            return $data;
        }, 2, 2);
    }

    public function addPaymentSettings(?string $settings): string
    {
        return $settings . view('plugins/vnpay::settings')->render();
    }

    public function registerVNPayMethod(?string $html, array $data): string
    {
        PaymentMethods::method(VNPAY_PAYMENT_METHOD_NAME, [
            'html' => view('plugins/vnpay::methods', $data)->render(),
        ]);

        return $html;
    }

    public function checkoutWithVNPay(array $data, Request $request): array
    {
        if ($request->input('payment_method') == VNPAY_PAYMENT_METHOD_NAME) {
            $currentCurrency = get_application_currency();

            $currencyModel = $currentCurrency->replicate();

            $vnPayService = $this->app->make(VNPayPaymentService::class);

            $supportedCurrencies = $vnPayService->supportedCurrencyCodes();

            $currency = strtoupper($currentCurrency->title);

            $notSupportCurrency = false;

            if (! in_array($currency, $supportedCurrencies)) {
                $notSupportCurrency = true;

                if (! $currencyModel->where('title', 'VND')->exists()) {
                    $data['error'] = true;
                    $data['message'] = __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", [
                        'name' => 'VNPay',
                        'currency' => $currency,
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]);

                    return $data;
                }
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            if ($notSupportCurrency) {
                $usdCurrency = $currencyModel->where('title', 'VND')->first();

                $paymentData['currency'] = 'VND';
                if ($currentCurrency->is_default) {
                    $paymentData['amount'] = $paymentData['amount'] * $usdCurrency->exchange_rate;
                } else {
                    $paymentData['amount'] = format_price($paymentData['amount'], $currentCurrency, true);
                }
            }

            $paymentData['callback_url'] = route('payments.vnpay.callback');

            $checkoutUrl = $vnPayService->makePayment($paymentData);

            if ($checkoutUrl) {
                $data['checkoutUrl'] = $checkoutUrl;
            } else {
                $data['error'] = true;
                $data['message'] = __('Something went wrong. Please try again later.');
            }

            return $data;
        }

        return $data;
    }
}
