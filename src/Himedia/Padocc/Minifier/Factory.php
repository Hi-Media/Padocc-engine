<?php

namespace Himedia\Padocc\Minifier;

use GAubry\Shell\ShellAdapter;

/**
 * Factory de MinifierInterface.
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Factory
{
    /**
     * Pour indiquer que l'on souhaite construire une instance de JSMinAdapter.
     * @var int
     * @see getInstance()
     */
    const TYPE_JSMIN = 1;

    /**
     * Retourne une instance de MinifierInterface selon le $iType spécifié.
     *
     * @param int $iType type d'instance désiré
     * @param ShellAdapter $oShell
     * @throws \BadMethodCallException si type inconnu.
     * @return MinifierInterface une instance de MinifierInterface selon le $iType spécifié.
     */
    public static function getInstance ($iType, ShellAdapter $oShell)
    {
        switch ($iType) {
            case self::TYPE_JSMIN:
                $oMinifier = new JSMinAdapter('$this->aConfig[\'jsmin_path\']', $oShell);
                break;

            default:
                throw new \BadMethodCallException("Unknown type: '$iType'!");
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
