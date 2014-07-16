<?php

namespace Himedia\Padocc\Minifier;

/**
 * Compresser les fichiers JS et CSS.
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
interface MinifierInterface
{

    /**
     * Minifie la liste de fichiers JS ou CSS spécifiée et enregistre le résultat dans $sDestPath.
     *
     * @param array $aSrcPaths liste de fichiers se finissant tous par '.js', ou tous par '.css'
     * @param string $sDestPath chemin/fichier dans lequel enregistrer le résultat du minify
     * @return MinifierInterface $this
     * @throws \BadMethodCallException si $aSrcPaths vide
     * @throws \UnexpectedValueException si les sources n'ont pas toutes la même extension de fichier
     * @throws \UnexpectedValueException si la destination est un CSS quand les sources sont des JS ou inversement
     * @throws \DomainException si des fichiers ne se terminent ni par '.js', ni par '.css'
     */
    public function minify(array $aSrcPaths, $sDestPath);
}
