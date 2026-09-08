<?php

namespace App\Providers;

use App\Domain\Content\Services\ContentService;
use App\Http\Middleware\HandleRedirects;
use App\Models\Banner;
use App\Models\Faq;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\ProductSpecValue;
use App\Models\Redirect;
use App\Models\Testimonial;
use App\Observers\ProductObserver;
use App\Observers\ProductSpecValueObserver;
use App\Services\Payment\LogPaymentGateway;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\RazorpayPaymentGateway;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\Msg91SmsGateway;
use App\Services\Sms\SmsGateway;
use App\Services\Sms\SmsManager;
use App\Support\DatabaseTranslationLoader;
use App\Support\LocalizedUrlGenerator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\ServiceProvider;

class KrishiJunctionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         | route() has to know which language the reader is in. Swapping the
         | generator here — rather than prefixing at registration — keeps route
         | caching intact and leaves every existing route() call untouched.
         */
        $this->app->extend('url', function (UrlGenerator $url, $app) {
            $localised = new LocalizedUrlGenerator(
                $app['router']->getRoutes(),
                $app->rebinding('request', fn ($app, $request) => $app['url']->setRequest($request)),
                $app['config']['app.asset_url'],
            );

            $localised->setSessionResolver(fn () => $app['session'] ?? null);
            $localised->setKeyResolver(fn () => $app['config']->get('app.key'));

            $app['router']->getRoutes()->refreshNameLookups();

            return $localised;
        });

        /*
         | Editors can override any interface string from the admin panel.
         | extend(), not singleton(): Laravel's own TranslationServiceProvider
         | registers after this one and would overwrite a plain binding.
         */
        $this->app->extend('translation.loader', function ($loader, $app) {
            return new DatabaseTranslationLoader($app['files'], $app['path.lang']);
        });

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

        // Editor writes invalidate what the public pages cache.
        foreach ([Banner::class, Faq::class, Menu::class, MenuItem::class, Testimonial::class] as $model) {
            $model::saved(fn () => ContentService::flush());
            $model::deleted(fn () => ContentService::flush());
        }

        Redirect::saved(fn () => HandleRedirects::flushCache());
        Redirect::deleted(fn () => HandleRedirects::flushCache());
    }
}
