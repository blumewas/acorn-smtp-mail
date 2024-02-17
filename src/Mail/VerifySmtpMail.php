<?php

namespace Blumewas\AcornSmtpMail\Mail;

use Illuminate\Support\Facades\Log;

class VerifySmtpMail
{

    public function __construct(
        private ConfigureSmtpMail $config,
    ) {
        $this->admin();
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
        $config = $this->config->config();

        if (! empty($_GET['verify-smtp']) && $_GET['verify-smtp'] === 'true' && wp_verify_nonce($_GET['_wpnonce'], 'verify-smtp')) {
            $this->verify();
        }

        if (! $config['username'] || ! $config['password'] || ! $config['host']) {
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

        $config = $this->config->config();

        try {
            $mail->isSMTP();

            $mail->SMTPDebug = $config['debug'] ? 2 : 0;

            $mail->Host = $config['host'];
            $mail->Port = $config['port'];

            if ($config['username'] && $config['password']) {
                $mail->SMTPAuth = true;
                $mail->Username = $config['username'];
                $mail->Password = $config['password'];
            }

            $mail->SMTPSecure = $config['secure'];

            $mail->Timeout = $config['timeout'] ?? 120;

            if ($config['forcefrom']) {
                $mail->setFrom(
                    $config['forcefrom'],
                    $config['forcefromname'] ?? get_bloginfo('name')
                );
            }

            $mail->addAddress(wp_get_current_user()->user_email);
            $mail->CharSet = get_bloginfo('charset');
            $mail->Subject = 'WP SMTP Validation';
            $mail->Body = 'Success.';

            $mail->send();
            $mail->clearAddresses();
            $mail->clearAllRecipients();
        } catch (\PHPMailer\PHPMailer\Exception $error) {
            Log::error($error->errorMessage());
            if ($config['debug'] ?? false) {
                print_r($config);
            }

            return $this->notice(
                $error->errorMessage(),
                'error',
                true
            );
        } catch (\Exception $error) {
            Log::error($error->errorMessage());
            if ($config['debug'] ?? false) {
                print_r($config);
            }

            return $this->notice(
                $error->getMessage(),
                'error',
                true
            );
        }

        // if (update_option('wp_mail_verify', $this->hash())) {
        //     return $this->notice(
        //         'WP SMTP connection successful!',
        //         'success',
        //         true
        //     );
        // }
    }

}
