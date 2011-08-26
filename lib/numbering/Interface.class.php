<?php

/**
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY
 */
interface Numbering_Interface
{

    public function getNextCounterValue ();

    public function addCounterDivision ();

    public function removeCounterDivision ();
}
