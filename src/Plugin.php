<?php

namespace Binjuhor\VNPay;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Models\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::query()
            ->whereIn('key', [
                'payment_vnpay_name',
                'payment_vnpay_description',
                'payment_vnpay_client_url',
                'payment_vnpay_tmncode',
                'payment_vnpay_secret',
                'payment_vnpay_mode',
            ])
            ->delete();
    }
}
