<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductSpecValue;
use App\Observers\ProductObserver;
use App\Observers\ProductSpecValueObserver;
use App\Services\Payment\LogPaymentGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\RazorpayPaymentGateway;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\Msg91SmsGateway;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

class KrishiJunctionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, function () {
            return match (config('kj.sms.driver')) {
                'msg91' => new Msg91SmsGateway(
                    (string) config('kj.sms.msg91.auth_key'),
                    (string) config('kj.sms.msg91.sender_id'),
                ),
                default => new LogSmsGateway,
            };
        });

        $this->app->singleton(SmsManager::class, fn ($app) => new SmsManager($app->make(SmsGateway::class)));

        $this->app->bind(PaymentGateway::class, function () {
            $razorpay = config('kj.payments.razorpay');

            // Without live keys the log driver still exercises the whole flow.
            return config('kj.payments.driver') === 'razorpay' && filled($razorpay['key_secret'])
                ? new RazorpayPaymentGateway((string) $razorpay['key_id'], (string) $razorpay['key_secret'])
                : new LogPaymentGateway;
        });
    }

    public function boot(): void
    {
        // Keeps the denormalised filter cache in step with the EAV.
        Product::observe(ProductObserver::class);
        ProductSpecValue::observe(ProductSpecValueObserver::class);
    }
}
