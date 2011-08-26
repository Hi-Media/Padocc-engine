<?php

/**
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY
 */
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
        if (count($this->_aCounter) > $this->_iCurrentDivision+1) {
            $this->_aCounter = array_slice($this->_aCounter, 0, $this->_iCurrentDivision+1);
        }
        return implode($this->_sSeparator, array_slice($this->_aCounter, 0, $this->_iCurrentDivision+1));
    }

    // Les nouvelles divisions commencent à 0.
    // Les préexistantes conservent leur valeur.
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
