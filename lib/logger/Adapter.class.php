<?php

class Logger_Adapter implements Logger_Interface
{

    private $_iMinErrorLevel;

    public function __construct ($iMinErrorLevel)
    {
        $this->_iMinErrorLevel = $iMinErrorLevel;
    }

    public function log ($sMessage, $iLevel=self::INFO)
    {
        if ($iLevel >= $this->_iMinErrorLevel) {
            echo str_replace("\n", '\\\n', rtrim($sMessage)) . "\n";
        }
        return $this;
    }
}