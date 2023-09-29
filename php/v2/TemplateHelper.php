<?php

namespace NW\WebService\References\Operations\Notification;

abstract class TemplateHelper
{
    private const TMPL_DIR    = 'tmpl';

    private static function getTmplPath()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . self::TMPL_DIR;
    }

    public static function __(string $templateFileName, ?array $templateData = null, ?Seller $reseller = null): string
    {
        ob_start();
        ob_implicit_flush(false);
        require self::getTmplPath() . DIRECTORY_SEPARATOR . $templateFileName.'.php';
        return ob_get_clean();
    }
}