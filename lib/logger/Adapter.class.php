<?php

class Logger_Adapter implements Logger_Interface
{

    private $iMinErrorLevel;

    public function __construct ($iMinErrorLevel)
    {
        $this->iMinErrorLevel = $iMinErrorLevel;
    }

    public function log ($sMessage, $iLevel=self::INFO)
    {
        if ($iLevel >= $this->iMinErrorLevel) {
            echo str_replace("\n", '\\\n', rtrim($sMessage)) . "\n";
        }
        return $this;
    }
}