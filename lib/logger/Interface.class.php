<?php

interface Logger_Interface
{

    const DEBUG=10;
    const INFO=20;
    const WARNING=30;
    const ERROR=40;

    public function log ($sMessage, $iLevel=self::INFO);
}
