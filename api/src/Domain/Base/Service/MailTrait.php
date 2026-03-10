<?php

namespace App\Domain\Base\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\BodyRendererInterface;

trait MailTrait
{
    protected function settings() {
        return require __DIR__ . "/../../../../config/settings.php";
    }

    private function getMailFrom(): string {
        $applicationSettings = (object)$this->settings()["application"];
        return $applicationSettings->email ?? 'info@spacehuddle.io';
    }

    private function getApplicationName(): string {
        $applicationSettings = (object)$this->settings()["application"];
        return $applicationSettings->name ?? 'SpaceHuddle';
    }

    private function getHtmlWrapper(string $header, string $content, string $buttonUrl = null, string $buttonText = null): string {
        $colorBrainstorming = "#7b61ff";
        $colorBackground = "#f4f7f9";

        $buttonHtml = "";
        if ($buttonUrl && $buttonText) {
                    $buttonHtml = "
                        <div style='margin: 30px 0 10px 0; text-align: center;'>
                            <a href='$buttonUrl' style='background-color: $colorBrainstorming; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block; font-size: 16px;'>
                                $buttonText
                            </a>
                        </div>
                        <div style='text-align: center; padding: 0 20px;'>
                            <p style='margin: 0; color: #9199A1; font-size: 6px; line-height: 1.2; word-break: break-all;'>
                                $buttonUrl
                            </p>
                        </div>";
                }

        return "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                </head>
                <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: $colorBackground;'>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: $colorBackground; padding: 40px 10px;'>
                        <tr>
                            <td align='center'>
                                <table width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);' border='0' cellspacing='0' cellpadding='0'>
                                    <tr>
                                        <td style='padding: 30px; text-align: center;'>
                                            <img src='cid:logo' alt='Logo' width='140' style='display: block; margin: 0 auto;'>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 0 40px 40px 40px;'>
                                            <h1 style='color: #1a202c; font-size: 22px; text-align: center; margin-bottom: 20px;'>$header</h1>
                                            <div style='color: #4a5568; font-size: 16px; line-height: 1.6; text-align: center;'>$content</div>
                                            $buttonHtml
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 20px; text-align: center; background-color: #f8fafc; color: #a0aec0; font-size: 12px;'>
                                            &copy; " . date('Y') . " " . $this->getApplicationName() . "
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>";
    }

    protected function smtpMail($email, $mailHeader, $message, $mailBody, $buttonUrl = null, $buttonText = null): void {
        $application = $this->getApplicationName();
        $smptSettings = (object)$this->settings()["smtp"];
        $dsn = sprintf("%s://%s:%s@%s:%s", $smptSettings->type, $smptSettings->username, $smptSettings->password, $smptSettings->host, $smptSettings->port);

        $mailer = new Mailer(Transport::fromDsn($dsn));

        $renderedHeader = str_replace("#application", $application, $mailHeader);
        $renderedBody = str_replace("#application", $application, $message);
        $htmlContent = $this->getHtmlWrapper($renderedHeader, $renderedBody, $buttonUrl, $buttonText);

        $emailObj = (new Email())
            ->from($this->getMailFrom())
            ->to($email)
            ->subject($renderedHeader)
            ->text(str_replace("#application", $application, $mailBody))
            ->html($htmlContent);

        $logoPath = __DIR__ . '/../../../../../frontend/src/assets/icons/svg/logo.png';
        if (file_exists($logoPath)) {
            $emailObj->embedFromPath($logoPath, 'logo');
        }

        $mailer->send($emailObj);
    }

    protected function sendMail(string $email, string $mailHeader, string $mailBody): void {
        $this->smtpMail($email, $mailHeader, $mailBody, $mailBody);
    }

    protected function sendMailWithUrl(string $email, string $mailHeader, string $mailBody, string $linkDisplayName, string $linkTargetName, string $linkParameter = ""): void {
        $applicationSettings = (object)$this->settings()["application"];
        $url = $applicationSettings->baseUrl . $applicationSettings->$linkTargetName . $linkParameter;
        $this->smtpMail($email, $mailHeader, $mailBody, $mailBody, $url, $linkDisplayName);
    }

    protected function sendMailWithTokenUrl(string $email, string $mailHeader, string $mailBody, string $linkDisplayName, string $linkTargetName, string $action): void {
        $jwt = $this->jwtAuth->createJwt(["action" => $action, "username" => $email]);
        $applicationSettings = (object)$this->settings()["application"];
        $url = $applicationSettings->baseUrl . $applicationSettings->$linkTargetName . $jwt;
        $this->smtpMail($email, $mailHeader, $mailBody, $mailBody, $url, $linkDisplayName);
    }
}
