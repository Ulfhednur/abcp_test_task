<?php
namespace NW\WebService\References\Operations\Notification;

/**
 * Куча классов в одном файле - это то же ошибка.
 * Как это потом поддерживать то?
 *
 * Class ReferencesOperation
 * @package NW\WebService\References\Operations\Notification
 */
abstract class ReferencesOperation
{
    public function __construct()
    {
        $this->load();
    }

    abstract public function doOperation(): array;

    abstract protected function load();

    public function getRequest($pName)
    {
        return $_REQUEST[$pName];
    }

    /**
     * Это мы вынесем сюда, иначе вообще зачем городить огород с наследованием
     * @param string $entityClass
     * @param int $id
     * @return Contractor|Employee|Seller
     * @throws \Exception
     */
    protected static function getEntityById(string $entityClass, int $id): Contractor|Employee|Seller
    {
        if(class_exists($entityClass)) {
            $entity = $entityClass::getById($id);
            if ($entity === null) {
                throw new \Exception($entityClass . ' not found!', 404);
            }
            return $entity;
        }
        throw new \Exception('Internal server error', 500);
    }
}