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

    public function minify (array $aSrcPaths, $sDestPath);
}
