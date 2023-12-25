<?php

namespace Binjuhor\VNPay\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;

class VNPayServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (is_plugin_active('payment')) {
            $this->setNamespace('plugins/vnpay')
                ->loadHelpers()
                ->loadRoutes()
                ->loadAndPublishViews()
                ->loadAndPublishTranslations()
                ->publishAssets();

            $this->app->register(HookServiceProvider::class);
        }
    }
}
