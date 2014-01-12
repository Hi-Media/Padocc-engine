<?php

namespace Himedia\Padocc;

/**
 * Classe outil des inclassables.
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Tools
{

    /**
     * Retourne un couple comprenant d'une part le nombre d'octets contenus dans la plus grande unité informatique
     * inférieure à la taille spécifiée, et d'autre part le nom de cette unité.
     *
     * Par exemple, si $iFileSize vaut 2000, alors le résultat sera : array(1024, 'Kio').
     *
     * @param int $iFileSize taille en octets à changer d'unité
     * @return array tableau (int, string) comprenant d'une part le nombre d'octets contenus dans la plus grande
     * unité inférieure à la taille spécifiée, et d'autre part le nom de cette unité.
     */
    public static function getFileSizeUnit ($iFileSize)
    {
        if ($iFileSize < 1024) {
            $iUnit = 1;
            $sUnit = 'o';
        } elseif ($iFileSize < 1024*1024) {
            $iUnit = 1024;
            $sUnit = 'Kio';
        } else {
            $iUnit = 1024*1024;
            $sUnit = 'Mio';
        }
        return array($iUnit, $sUnit);
    }

    /**
     * Retourne un couple comprenant d'une part la taille spécifiée arrondie,
     * et d'autre part l'unité dans laquelle la taille a été arrondie.
     *
     * Le second paramètre, si <> de 0, permet de spécifier une taille de référence pour le calcul de l'unité.
     *
     * Par exemple :
     * (100, 0) => ('100', 'o')
     * (100, 2000000) => ('<1', 'Mio')
     * (200, 0) => ('2', 'Kio')
     *
     * @param int $iSize taille à convertir
     * @param int $iRefSize référentiel de conversion, si différent de 0
     * @return array un couple comprenant d'une part la taille spécifiée arrondie,
     * et d'autre part l'unité dans laquelle la taille a été arrondie.
     */
    public static function convertFileSize2String ($iSize, $iRefSize=0)
    {
        if ($iRefSize === 0) {
            $iRefSize = $iSize;
        }
        list($iUnit, $sUnit) = self::getFileSizeUnit($iRefSize);

        $sFileSize = round($iSize/$iUnit);
        if ($sFileSize == 0 && $iSize > 0) {
            $sFileSize = '<1';
        }
        return array($sFileSize, $sUnit);
    }

    /**
     * Classe outil non instanciable.
     */
    private function __construct ()
    {
    }
}
