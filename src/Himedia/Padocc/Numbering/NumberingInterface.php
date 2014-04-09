<?php

namespace Himedia\Padocc\Numbering;

/**
 * Gestion d'une numérotation hiérarchique (1.1, 1.2, ...).
 *
 * Un appel à addCounterDivision() suivi d'un appel à removeCounterDivision() est sans effet.
 * L'inverse est également vrai si l'on n'est pas au niveau le plus haut.
 * Par exemple : 1.3.7 => 1.3 => 1.3.7
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
interface NumberingInterface
{

    /**
     * Retourne la prochaine valeur du compteur hiérarchique en incrémentant le plus bas niveau.
     * Exemple : 1.1 => 1.2
     *
     * @return string prochaine valeur du compteur hiérarchique en intercalant le séparateur entre chaque niveau
     */
    public function getNextCounterValue();

    /**
     * Ajoute une nouvelle division hiérarchique et l'initialise à 0.
     * Par exemple : 1.1 => 1.1.0
     *
     * @return NumberingInterface $this
     */
    public function addCounterDivision();

    /**
     * Remonte d'un niveau hiérarchique.
     *
     * @return NumberingInterface $this
     */
    public function removeCounterDivision();
}
