<?php

class Numbering_Adapter implements Numbering_Interface
{

    /**
     * Compteur.
     * @var array
     */
    private $aCounter;

    private $sSeparator;

    private $iCurrentDivision;

    public function __construct ($sSeparator='.')
    {
        $this->sSeparator = $sSeparator;
        $this->aCounter = array(0);
        $this->iCurrentDivision = 0;
    }

    public function getNextCounterValue ()
    {
        $this->aCounter[$this->iCurrentDivision]++;
        return implode($this->sSeparator, array_slice($this->aCounter, 0, $this->iCurrentDivision+1));
    }

    public function addCounterDivision ()
    {
        $this->iCurrentDivision++;
        if ($this->iCurrentDivision >= count($this->aCounter)) {
            $this->aCounter[] = 0;
        }
        return $this;
    }

    public function removeCounterDivision ()
    {
        if ($this->iCurrentDivision > 0) {
            $this->iCurrentDivision--;
        }
        return $this;
    }
}
