<?php

/**
 * Compresser les fichiers JS et CSS.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Minifier_Factory
{
    const TYPE_JSMIN = 1;

    public static function getInstance ($iType, Shell_Interface $oShell)
    {
        switch ($iType) {
            case self::TYPE_JSMIN:
                $oMinifier = new Minifier_JSMinAdapter(DEPLOYMENT_JSMIN_BIN_PATH, $oShell);
                break;

            default:
                throw new BadMethodCallException("Unknown type: '$iType'!");
                break;
        }
        return $oMinifier;
    }

    /**
     * Simple factory.
     */
    private function __construct()
    {
    }
}
