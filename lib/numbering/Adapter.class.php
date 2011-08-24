<?php

class Numbering_Adapter implements Numbering_Interface
{

    /**
     * Compteur.
     * @var array
     */
    private $_aCounter;

    private $_sSeparator;

    private $_iCurrentDivision;

    public function __construct ($sSeparator='.')
    {
        $this->_sSeparator = $sSeparator;
        $this->_aCounter = array(0);
        $this->_iCurrentDivision = 0;
    }

    public function getNextCounterValue ()
    {
        $this->_aCounter[$this->_iCurrentDivision]++;
        return implode($this->_sSeparator, array_slice($this->_aCounter, 0, $this->_iCurrentDivision+1));
    }

    public function addCounterDivision ()
    {
        $this->_iCurrentDivision++;
        if ($this->_iCurrentDivision >= count($this->_aCounter)) {
            $this->_aCounter[] = 0;
        }
        return $this;
    }

    public function removeCounterDivision ()
    {
        if ($this->_iCurrentDivision > 0) {
            $this->_iCurrentDivision--;
        }
        return $this;
    }
}
