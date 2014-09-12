<?php

namespace Himedia\Padocc\Numbering;

/**
 * Gestion d'une numérotation hiérarchique (1.1, 1.2, ...).
 *
 * Un appel à addCounterDivision() suivi d'un appel à removeCounterDivision() est sans effet.
 * L'inverse est également vrai si l'on n'est pas au niveau le plus haut.
 * Par exemple : 1.3.7 => 1.3 => 1.3.7
 *
 *
 *
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @license Apache License, Version 2.0
 */
class Adapter implements NumberingInterface
{

    /**
     * Compteur hiérarchique.
     * Mémorise pour la plus haute valeur d'un niveau hiérarchique donné
     * la plus haute valeur atteinte du sous-niveau.
     * @var array
     */
    private $aCounter;

    /**
     * Chaîne intercalée entre chaque niveau hiérarchique.
     * @var string
     * @see getNextCounterValue()
     */
    private $sSeparator;

    /**
     * Niveau hierarchique courant.
     * @var int
     */
    private $iCurrentDivision;

    /**
     * Constructeur.
     *
     * @param string $sSeparator chaîne intercalée entre chaque niveau hiérarchique
     * @codeCoverageIgnore
     */
    public function __construct($sSeparator = '.')
    {
        $this->sSeparator = $sSeparator;
        $this->aCounter = array(0);
        $this->iCurrentDivision = 0;
    }

    /**
     * Retourne la prochaine valeur du compteur hiérarchique en incrémentant le plus bas niveau.
     * Exemple : 1.1 => 1.2
     *
     * @return string prochaine valeur du compteur hiérarchique en intercalant le séparateur entre chaque niveau
     */
    public function getNextCounterValue()
    {
        $this->aCounter[$this->iCurrentDivision]++;
        if (count($this->aCounter) > $this->iCurrentDivision+1) {
            $this->aCounter = array_slice($this->aCounter, 0, $this->iCurrentDivision+1);
        }
        return implode($this->sSeparator, array_slice($this->aCounter, 0, $this->iCurrentDivision+1));
    }

    /**
     * Ajoute une nouvelle division hiérarchique et l'initialise à 0.
     * Par exemple : 1.1 => 1.1.0
     *
     * @return NumberingInterface $this
     */
    public function addCounterDivision()
    {
        $this->iCurrentDivision++;
        if ($this->iCurrentDivision >= count($this->aCounter)) {
            $this->aCounter[] = 0;
        }
        return $this;
    }

    /**
     * Remonte d'un niveau hiérarchique.
     *
     * @return NumberingInterface $this
     */
    public function removeCounterDivision()
    {
        if ($this->iCurrentDivision > 0) {
            $this->iCurrentDivision--;
        }
        return $this;
    }
}
