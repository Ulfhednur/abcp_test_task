<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * Class Contractor
 * @package NW\WebService\References\Operations\Notification
 */
class Contractor
{
    const TYPE_CUSTOMER = 0;

    public int $id;
    public int $type;
    public int $seller_id;
    public string $name;
    public string $email;
    public string $phone;

    public Seller $Seller;

    /**
     * Contractor constructor.
     * @param $id
     */
    public function __construct($id)
    {
        /**
         * В общем случае это всё берётся из базы каким нибудь методом фреймворка
         *
         */
        $this->id = $id;
        $this->name = 'Фамилия Имя отчество';
        $this->type = self::TYPE_CUSTOMER;
        $this->phone = '+79211234567';
        $this->email = 'email@example.com';
        $this->Seller = Seller::getById((int) $_REQUEST['data']['resellerId']);
    }

    public static function getById(int $resellerId): static
    {
        return new static($resellerId); // fakes the getById method
    }

    public function getFullName(): string
    {
        return $this->name . ' ' . $this->id;
    }
}
