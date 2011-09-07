<?php

/**
 * Compresser les fichiers JS et CSS.
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
interface Minifier_Interface
{

    public function minifyJS (array $aSrcPaths, $sDestPath);

    public function minifyCSS (array $aSrcPaths, $sDestPath);
}
