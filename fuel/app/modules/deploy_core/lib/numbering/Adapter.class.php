<?php

/**
 * Gestion d'une numérotation hiérarchique (1.1, 1.2, ...).
 *
 * Un appel à addCounterDivision() suivi d'un appel à removeCounterDivision() est sans effet.
 * L'inverse est également vrai si l'on n'est pas au niveau le plus haut.
 * Par exemple : 1.3.7 => 1.3 => 1.3.7
 *
 * @category TwengaDeploy
 * @package Lib
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Numbering_Adapter implements Numbering_Interface
{

    /**
     * Compteur hiérarchique.
     * Mémorise pour la plus haute valeur d'un niveau hiérarchique donné
     * la plus haute valeur atteinte du sous-niveau.
     * @var array
     */
    private $_aCounter;

    /**
     * Chaîne intercalée entre chaque niveau hiérarchique.
     * @var string
     * @see getNextCounterValue()
     */
    private $_sSeparator;

    /**
     * Niveau hierarchique courant.
     * @var int
     */
    private $_iCurrentDivision;

    /**
     * Constructeur.
     *
     * @param string $sSeparator chaîne intercalée entre chaque niveau hiérarchique
     */
    public function __construct ($sSeparator='.')
    {
        $this->_sSeparator = $sSeparator;
        $this->_aCounter = array(0);
        $this->_iCurrentDivision = 0;
    }

    /**
     * Retourne la prochaine valeur du compteur hiérarchique en incrémentant le plus bas niveau.
     * Exemple : 1.1 => 1.2
     *
     * @return string prochaine valeur du compteur hiérarchique en intercalant le séparateur entre chaque niveau
     */
    public function getNextCounterValue ()
    {
        $this->_aCounter[$this->_iCurrentDivision]++;
        if (count($this->_aCounter) > $this->_iCurrentDivision+1) {
            $this->_aCounter = array_slice($this->_aCounter, 0, $this->_iCurrentDivision+1);
        }
        return implode($this->_sSeparator, array_slice($this->_aCounter, 0, $this->_iCurrentDivision+1));
    }

    /**
     * Ajoute une nouvelle division hiérarchique et l'initialise à 0.
     * Par exemple : 1.1 => 1.1.0
     *
     * @return Numbering_Interface $this
     */
    public function addCounterDivision ()
    {
        $this->_iCurrentDivision++;
        if ($this->_iCurrentDivision >= count($this->_aCounter)) {
            $this->_aCounter[] = 0;
        }
        return $this;
    }

    /**
     * Remonte d'un niveau hiérarchique.
     *
     * @return Numbering_Interface $this
     */
    public function removeCounterDivision ()
    {
        if ($this->_iCurrentDivision > 0) {
            $this->_iCurrentDivision--;
        }
        return $this;
    }
}
