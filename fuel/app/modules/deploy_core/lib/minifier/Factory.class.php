<?php

/**
 * Factory de Minifier_Interface.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Minifier_Factory
{
    /**
     * Pour indiquer que l'on souhaite construire une instance de Minifier_JSMinAdapter.
     * @var int
     * @see getInstance()
     */
    const TYPE_JSMIN = 1;

    /**
     * Retourne une instance de Minifier_Interface selon le $iType spécifié.
     *
     * @param int $iType type d'instance désiré
     * @param Shell_Interface $oShell
     * @throws BadMethodCallException si type inconnu.
     * @return Minifier_Interface une instance de Minifier_Interface selon le $iType spécifié.
     */
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
     * Simple factory, non instanciable.
     */
    private function __construct()
    {
    }
}
