<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * Class fakeSettingsHelper
 * @package NW\WebService\References\Operations\Notification
 */
abstract class FakeSettingsHelper
{
    /**
     * @return string
     */
    public static function getResellerEmailFrom(): string
    {
        return 'contractor@example.com';
    }

    /**
     * @param $resellerId
     * @param $event
     * @return string[]
     */
    public static function getEmailsByPermit($resellerId, $event): array
    {
        // fakes the method
        return ['someemeil@example.com', 'someemeil2@example.com'];
    }
}