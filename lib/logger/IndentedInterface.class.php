<?php

/**
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY
 */
interface Logger_IndentedInterface extends Logger_Interface
{
    public function indent();
    public function unindent();
}
