<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * Class NotificationManager
 * @package NW\WebService\References\Operations\Notification
 *
 * Здесь должна быть интеграция какого ни будь СМС-сервиса
 * сделал по минимуму на примере sms.ru
 */
abstract class NotificationManager
{
    private const API_KEY = 'sms.ru_api_key';
    private const API_URL = 'https://sms.ru/sms/';
    private const SMS_PRICE = 1.3;

    /**
     * У нас же только в одном месте КУРЛ, мы же не будем ставить композер и тянуть из него GuzzleHttpClient?
     * Хотя и очень хочется.
     *
     * @param string $method
     * @param string $data
     * @param bool $post
     * @return \stdClass
     * @throws \Exception
     */
    private static function apiCall(string $method, string $data, bool $post = false): \stdClass
    {
        $url = self::API_URL . $method;
        if (!$post) {
            $url .= '?' . $data;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        $errors = curl_error($ch);
        curl_close($ch);
        if (!empty($errors)) {
            throw new \Exception($errors, 500);
        }
        $result = json_decode($result);
        if(!$result){
            throw new \Exception(json_last_error_msg(), 500);
        }
        return $result;
    }

    /**
     * @param string $phone
     * @param string $msg
     * @return bool
     * @throws \Exception
     */
    private static function sendMessage(string $phone, string $msg): bool
    {
        if (!preg_match("/^\\+?[1-9][0-9]{7,14}$/", $phone)) {
            throw new \Exception('Invalid phone number '.$phone, 500);
        }
        try {
            $res = self::apiCall(
                'send',
                http_build_query(
                    [
                        'api_id' => self::API_KEY,
                        'to' => str_replace('+', '', $phone),
                        'msg' => $msg,
                        'json' => 1
                    ]
                ),
                true
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }

        if ($res->status !== 'OK') {
            throw new \Exception($res->status_text, 500);
        }
        foreach($res->sms as $sms) {
            if ($sms->status_code !== 100) {
                throw new \Exception($sms->status_text, 500);
            }
        }
        return true;
    }

    /**
     * @return float
     * @throws \Exception
     */
    public static function checkBalance(): float
    {
        try {
            $res = self::apiCall(
                'balance',
                http_build_query(
                    [
                        'api_id' => self::API_KEY,
                        'json' => 1
                    ]
                ),
                true
            );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 500);
        }
        if ($res->status !== 'OK') {
            throw new \Exception($res->status_text, 500);
        }
        return $res->balance;
    }

    /**
     * @param string $phone
     * @param array $data
     * @param array $errorMessage
     * @return bool
     */
    public static function send(string $phone, array $data, array &$errorMessage): bool
    {
        $message = $data['difference'];
        try {
            if (self::checkBalance() >= self::SMS_PRICE) {
                $isSend = self::sendMessage($phone, $message);
            } else {
                $errorMessage[] = 'insufficient balance';
                return false;
            }
        } catch (\Exception $e) {
            $errorMessage[] = $e->getMessage();
            return false;
        }
        return $isSend;
    }
}