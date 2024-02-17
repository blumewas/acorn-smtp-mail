<?php

namespace Blumewas\AcornSmtpMail\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;

class ConfigureSmtpMail
{

    private $log_errors;

    private $debug;
    private $host;
    private $port;

    private $secure;

    private $username;
    private $password;

    private $forcefrom;
    private $forcefromname;

    private $timeout = 120;

    public function __construct()
    {
        $this->loadConfig();

        // add_filter('phpmailer_init', [$this, 'configureSmtp'], PHP_INT_MAX);
        // add_filter('phpmailer_init', function ($mail) {
        //     $this->configureSmtp($mail);
        // }, PHP_INT_MAX);

        // add_action('wp_mail_failed', function ($wp_error) {
        //     $this->logMailErrors($wp_error);
        // }, PHP_INT_MAX);
    }

    /**
     * Configure the SMTP settings
     */
    public function configureSmtp(PHPMailer $mail)
    {
        $mail->isSMTP();

        $mail->SMTPDebug = $this->debug ? 2 : 0;

        $mail->Host = $this->host;
        $mail->Port = $this->port;

        if ($this->username && $this->password) {
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
        }

        if ($this->secure) {
            $mail->SMTPOptions = ['ssl' => true];
            $mail->SMTPSecure = $this->secure;

            $mail->SMTPAutoTLS = $this->secure === 'ssl' ? false : true;
        }

        $mail->Timeout = $this->timeout;

        if ($this->forcefrom && $this->forcefromname) {
            $mail->setFrom(
                $this->forcefrom,
                $this->forcefromname
            );
        }
    }

    /**
     * Load the configuration from the config file
     */
    protected function loadConfig()
    {
        $this->log_errors = config('smtp-mail.log_errors') || config('smtp-mail.debug');

        $this->debug = config('smtp-mail.debug');
        $this->host = config('smtp-mail.host');
        $this->port = config('smtp-mail.port');
        $this->username = config('smtp-mail.username');
        $this->password = config('smtp-mail.password');

        $this->secure = config('smtp-mail.secure');

        $this->forcefrom = config('smtp-mail.forcefrom');
        $this->forcefromname = config('smtp-mail.forcefromname');

        $this->timeout = config('smtp-mail.timeout');
    }

    /**
     * Log mail errors
     */
    public function logMailErrors($wp_error)
    {
        if (!$this->log_errors) {
            return;
        }

        Log::error(sprintf("%s Mailer Error: %s", date('Y-m-d H:i:s'), $wp_error->get_error_message()));
    }

    public function config(): array
    {
        return [
            'debug' => $this->debug,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'secure' => $this->secure,
            'forcefrom' => $this->forcefrom,
            'forcefromname' => $this->forcefromname,
            'timeout' => $this->timeout,
        ];
    }
}
