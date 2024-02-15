<?php

namespace Blumewas\AcornSmtpMail\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;

class ConfigureSmtpMail
{

    public $debug;
    public $host;
    public $port;

    public $secure;

    public $username;
    public $password;

    public $forcefrom;
    public $forcefromname;

    public function __construct()
    {
        $this->loadConfig();

        add_filter('phpmailer_init', function ($mail) {
            $this->configureSmtp($mail);
        }, PHP_INT_MAX);

        add_action('wp_mail_failed', function ($wp_error) {
            $this->logMailErrors($wp_error);
        }, PHP_INT_MAX);
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

        $mail->Timeout = 10;

        if ($this->forcefrom && $this->forcefromname) {
            $mail->setFrom(
                $this->forcefrom,
                $this->forcefromname
            );
        }
    }

    protected function notice($message, $type = 'info', $dismissible = false)
    {
        add_action('admin_notices', function () use ($message, $type, $dismissible) {
            printf(
                '<div class="%1$s"><p>%2$s</p></div>',
                esc_attr("notice notice-{$type}" . ($dismissible ? ' is-dismissible' : '')),
                __($message, 'wp-smtp')
            );
        });
    }

    /**
     * Admin
     *
     * @return void
     */
    protected function admin()
    {
        if (! empty($_GET['verify-smtp']) && $_GET['verify-smtp'] === 'true' && wp_verify_nonce($_GET['_wpnonce'], 'verify-smtp')) {
            $this->verify();
        }

        if (! $this->username || ! $this->password || ! $this->host) {
            return $this->notice(
                'WP SMTP failed to find your SMTP credentials. Please define them in <code>.env</code> and <a href="'.wp_nonce_url(admin_url(add_query_arg('verify-smtp', 'true', 'index.php')), 'verify-smtp').'">click here</a> to test your configuration.',
                'error'
            );
        }

        if (! get_option('wp_mail_verify')) {
            return $this->notice(
                'WP SMTP credentials found. Please <a href="'.wp_nonce_url(admin_url(add_query_arg('verify-smtp', 'true', 'index.php')), 'verify-smtp').'">click here</a> to test your configuration.'
            );
        }

        // if (! $this->validate()) {
        //     return $this->notice(
        //         'WP SMTP has detected a change in your credentials. Please <a href="'.wp_nonce_url(admin_url(add_query_arg('verify-smtp', 'true', 'index.php')), 'verify-smtp').'">click here</a> to test your configuration.'
        //     );
        // }
    }

    /**
     * Verify SMTP Credentials
     *
     * @return void
     */
    protected function verify()
    {
        require_once(ABSPATH . WPINC . '/PHPMailer/Exception.php');
        require_once(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php');
        require_once(ABSPATH . WPINC . '/PHPMailer/SMTP.php');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPOptions = ['ssl' => true];
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPAutoTLS = false;

            $mail->Host = $this->host;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->Port = $this->port;
            $mail->Timeout = 10;

            $mail->setFrom('no-reply@'.preg_replace('/https?:\/\/(www\.)?/', '', get_bloginfo('url')), get_bloginfo('name'));
            $mail->AddAddress(wp_get_current_user()->user_email);

            if ($this->forcefrom && $this->forcefromname) {
                $mail->setFrom(
                    $this->forcefrom,
                    $this->forcefromname
                );
            }

            $mail->CharSet = get_bloginfo('charset');
            $mail->Subject = 'WP SMTP Validation';
            $mail->Body = 'Success.';

            $mail->Send();
            $mail->ClearAddresses();
            $mail->ClearAllRecipients();
        } catch (\PHPMailer\PHPMailer\Exception $error) {
            return $this->notice(
                $error->errorMessage(),
                'error',
                true
            );
        } catch (Exception $error) {
            return $this->notice(
                $error->getMessage(),
                'error',
                true
            );
        }

        if (update_option('wp_mail_verify', $this->hash())) {
            return $this->notice(
                'WP SMTP connection successful!',
                'success',
                true
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
}
