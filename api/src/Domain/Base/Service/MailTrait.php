<?php


namespace App\Domain\Base\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Trait that send mails.
 */
trait MailTrait
{
    /**
     * Application settings
     * @return mixed Application settings
     */
    protected function settings() {
        return require __DIR__ . "/../../../../config/settings.php";
    }

    private function getMailFrom(): string {
        $applicationSettings = (object)$this->settings()["application"];
        $from = $applicationSettings->email;
        if (!isset($from)) $from = 'info@spacehuddle.io';

        return $from;
    }

    private function getApplicationName(): string {
        $applicationSettings = (object)$this->settings()["application"];
        $name = $applicationSettings->name;
        if (!isset($name)) $name = 'spacehuddle.io';

        return $name;
    }

    /**
     * Calculated the mail header
     * @return string Mail Header
     */
    private function getMailHeader(): string {
        $from = $this->getMailFrom();

        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

        // Create email headers
        $headers .= 'From: '.$from."\r\n".
            'Reply-To: '.$from."\r\n";
        return $headers;
    }

    protected function smtpMail($email, $mailHeader, $message, $mailBody): void {
        $application = $this->getApplicationName();
        $smptSettings = (object)$this->settings()["smtp"];
        $dsn = sprintf(
            "%s://%s:%s@%s:%s",
            $smptSettings->type,
            $smptSettings->username,
            $smptSettings->password,
            $smptSettings->host,
            $smptSettings->port
        );
        $mailer = new Mailer(Transport::fromDsn($dsn));
        $from = $this->getMailFrom();
        $email = (new Email())
            ->from($from)
            ->to($email)
            ->subject(str_replace("#application", $application, $mailHeader))
            ->text(str_replace("#application", $application, $mailBody))
            ->html(str_replace("#application", $application, $message));

        $mailer->send($email);
    }

    /**
     * send a mail
     * @param string $email Receiver
     * @param string $mailHeader Email header
     * @param string $mailBody Email body
     */
    protected function sendMail(
        string $email,
        string $mailHeader,
        string $mailBody,
    ): void {
        $message = "
            <h1>$mailHeader</h1>
            <div>$mailBody</div>";

        $this->smtpMail($email, $mailHeader, $message, $mailBody);

        //mail($email, $mailHeader, $message, $this->getMailHeader());
    }

    /**
     * send a mail with a url
     * @param string $email Receiver
     * @param string $mailHeader Email header
     * @param string $mailBody Email body
     * @param string $linkDisplayName Display name of the receiver url.
     * @param string $linkTargetName Name of the receiver url in the settings file.
     * @param string $linkParameter Optional: Add link parameter.
     */
    protected function sendMailWithUrl(
        string $email,
        string $mailHeader,
        string $mailBody,
        string $linkDisplayName,
        string $linkTargetName,
        string $linkParameter = ""
    ): void {
        $applicationSettings = (object)$this->settings()["application"];
        $detailUrlPart = $applicationSettings->$linkTargetName;
        $resetUrl = "$applicationSettings->baseUrl$detailUrlPart";

        $message = "
            <h1>$mailHeader</h1>
            <div>$mailBody</div>
            <div>
                <a href='$resetUrl$linkParameter'>$linkDisplayName</a>
            </div>
            <p>
                If the link above does not work, please copy this url manually into your browser.
            </p>
            <div>
                $resetUrl$linkParameter
            </div>";

        $this->smtpMail($email, $mailHeader, $message, $mailBody);

        //mail($email, $mailHeader, $message, $this->getMailHeader());
    }

    /**
     * send a mail with a created token
     * @param string $email Receiver
     * @param string $mailHeader Email header
     * @param string $mailBody Email body
     * @param string $linkDisplayName Display name of the receiver url.
     * @param string $linkTargetName Name of the receiver url in the settings file.
     * @param string $action Action for which the token is to be generated.
     */
    protected function sendMailWithTokenUrl(
        string $email,
        string $mailHeader,
        string $mailBody,
        string $linkDisplayName,
        string $linkTargetName,
        string $action
    ): void {
        $jwt = $this->jwtAuth->createJwt(
            [
                "action" => $action,
                "username" => $email
            ]
        );

        $applicationSettings = (object)$this->settings()["application"];
        $detailUrlPart = $applicationSettings->$linkTargetName;
        $resetUrl = "$applicationSettings->baseUrl$detailUrlPart";

        $message = "
            <h1>$mailHeader</h1>
            <div>$mailBody</div>
            <div>
                <a href='$resetUrl$jwt'>$linkDisplayName</a>
            </div>
            <p>
                If the link above does not work, please copy this url manually into your browser.
            </p>
            <div>
                $resetUrl$jwt
            </div>";

        $this->smtpMail($email, $mailHeader, $message, $mailBody);

        //mail($email, $mailHeader, $message, $this->getMailHeader());
    }
}
