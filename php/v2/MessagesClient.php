<?php
namespace NW\WebService\References\Operations\Notification;

/**
 * Class MessagesClient
 * @package NW\WebService\References\Operations\Notification
 */
abstract class MessagesClient
{
    public static function encodeFrom(string $string): string
    {
        if (mb_check_encoding($string, 'ASCII')) {
            return mb_convert_encoding($string, 'ASCII', 'UTF-8');
        }
        return "=?utf-8?b?" . base64_encode($string) . "?=";
    }
    public static function sendMessage(array $messageParams): bool
    {
        /**
         * По хорошему, тут должна была использоваться библиотека phpMailer https://github.com/PHPMailer/PHPMailer
         * или аналог
         * mail() сработает только если в настройках PHP корректно сконфигурирован smtp-транспорт
         * phpMailer умеет отправлять почту как smtp-клиент
         *
         * я в курсе про PHP_EOL, а вот большинство почтовых серверов про "\n" - нет...
         */
        $headers = 'From: '.self::encodeFrom($messageParams['emailFrom']).'<'.$messageParams['emailFrom'].'>'."\r\n";
        $headers .= 'Reply-To: '.$messageParams['emailFrom']."\r\n";
        $headers .= 'X-Mailer: PHP/'.phpversion()."\r\n";
        $headers .= 'MIME-Version: 1.0'."\r\n";
        $headers .= 'Content-Type: text/html; charset=utf-8'."\r\n";
        $headers .= 'Content-Transfer-Encoding: BASE64'."\r\n";
        mb_internal_encoding('UTF-8');
        $encoded_subject = mb_encode_mimeheader($messageParams['subject'], 'UTF-8', 'B', "\r\n", strlen('Subject: '));
        return mail(
            $messageParams['emailTo'],
            $encoded_subject,
            base64_encode($messageParams['message']),
            $headers,
            '-f '.$messageParams['emailFrom']
        );
    }
}