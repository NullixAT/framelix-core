<?php

namespace Framelix\Framelix\Utils;

use Exception;
use Framelix\Framelix\Config;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Storable\User;
use PHPMailer\PHPMailer\PHPMailer;

use function explode;
use function mb_strtolower;
use function str_replace;
use function substr;
use function uniqid;

class Email
{
    /**
     * Check if email is configured correctly
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return !!self::getPhpMailer();
    }

    /**
     * Send an email
     * @param string $title
     * @param string $body
     * @param string|User|null $to Multiple separated by ;
     * @param string|User|null $cc Multiple separated by ;
     * @param string|User|null $bcc Multiple separated by ;
     * @param string|User|null $replyTo
     * @param string|User|null $from
     * @param array|null $attachments Key is filename, value is #filepath or @filedata as binary string (not base64)
     * @return bool
     */
    public static function send(
        string $title,
        string $body,
        string|User|null $to = null,
        string|User|null $cc = null,
        string|User|null $bcc = null,
        string|User|null $replyTo = null,
        string|User|null $from = null,
        array|null $attachments = null
    ): bool {
        $mailer = self::getPhpMailer();
        if (!$mailer) {
            throw new Exception('E-Mail is not configured correctly');
        }
        $mailer->Subject = str_replace(
            ['{title}'],
            [Lang::get($title)],
            Config::get('emailTitleTemplate') ?: '{title}'
        );
        $mailer->MsgHTML(
            str_replace(
                ['{title}', '{body}'],
                [Lang::get($title), Lang::get($body)],
                Config::get('emailBodyTemplate') ?: '{body}'
            )
        );
        $emailOverride = Config::get('emailOverrideRecipient') ?: null;
        if ($to) {
            if ($to instanceof User) {
                $to = $to->email;
            }
            $emails = explode(";", $to);
            foreach ($emails as $email) {
                $mailer->addAddress(self::sanitizeEmail($emailOverride ?? $email));
            }
        }
        if ($cc) {
            if ($cc instanceof User) {
                $cc = $cc->email;
            }
            $emails = explode(";", $cc);
            foreach ($emails as $email) {
                $mailer->addCC(self::sanitizeEmail($emailOverride ?? $email));
            }
        }
        if ($bcc) {
            if ($bcc instanceof User) {
                $bcc = $bcc->email;
            }
            $emails = explode(";", $bcc);
            foreach ($emails as $email) {
                $mailer->addBCC(self::sanitizeEmail($emailOverride ?? $email));
            }
        }
        if ($replyTo) {
            if ($replyTo instanceof User) {
                $replyTo = $replyTo->email;
            }
            $mailer->addReplyTo(self::sanitizeEmail($replyTo));
        }
        if ($from) {
            if ($from instanceof User) {
                $from = $from->email;
            }
            $mailer->setFrom(self::sanitizeEmail($from));
        }
        if ($attachments) {
            foreach ($attachments as $filename => $file) {
                if (str_starts_with($file, "#")) {
                    $mailer->addAttachment(substr($file, 1), $filename);
                } elseif (str_starts_with($file, "@")) {
                    $mailer->addStringAttachment(substr($file, 1), $filename);
                }
            }
        }
        return $mailer->send();
    }

    /**
     * Get naked php mailer instance
     * @return PHPMailer|null Null when not configured correctly
     */
    public static function getPhpMailer(): ?PHPMailer
    {
        $folder = FileUtils::getModuleRootPath("Framelix") . "/vendor/phpmailer";
        require_once("$folder/PHPMailer.php");
        require_once("$folder/Exception.php");
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        switch (Config::get('mailSendType')) {
            case 'mail':
                $mail->isMail();
                break;
            case 'smtp':
                $smtp = Config::get('smtpHost');
                if (!$smtp) {
                    return null;
                }
                require_once("$folder/SMTP.php");
                $mail->isSMTP();
                $mail->Host = Config::get('smtpHost');
                $mail->Port = Config::get('smtpPort');
                $mail->Username = Config::get('smtpUsername');
                $mail->Password = Config::get('smtpPassword');
                if ($mail->Username) {
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = Config::get('smtpSecure');
                }
                break;
            case 'sendmail':
                $mail->isSendmail();
                break;
            default:
                return null;
        }
        $from = Config::get('emailDefaultFrom');
        if ($from) {
            $mail->setFrom($from);
            $exp = explode("@", $from);
            $mail->MessageID = '<' . md5(uniqid('', true)) . "@" . $exp[1] . '>';
        }
        return $mail;
    }

    /**
     * Sanitize a single email an remove invalid chars
     * @param mixed $email
     * @return string
     */
    private static function sanitizeEmail(mixed $email): string
    {
        return str_replace(["?", "<", ">", "\t", "\r", "\n", ",", ";"], "", mb_strtolower($email));
    }

}