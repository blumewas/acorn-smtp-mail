<?php

namespace Blumewas\AcornSmtpMail\Providers;

use Illuminate\Support\ServiceProvider;
use Blumewas\AcornSmtpMail\Mail\ConfigureSmtpMail;
use Blumewas\AcornSmtpMail\Mail\VerifySmtpMail;

class AcornSmtpMailProvider extends ServiceProvider
{
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/smtp-mail.php' => $this->app->configPath('smtp-mail.php'),
        ], 'config');

        $config = new ConfigureSmtpMail();

        new VerifySmtpMail($config);
    }

}
