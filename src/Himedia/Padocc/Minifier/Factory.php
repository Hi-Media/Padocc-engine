<?php

namespace Himedia\Padocc\Minifier;

use GAubry\Shell\ShellAdapter;

/**
 * Factory de MinifierInterface.
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
class Factory
{
    /**
     * Pour indiquer que l'on souhaite construire une instance de JSMinAdapter.
     * @var int
     * @see getInstance()
     */
    const TYPE_JSMIN = 1;

    /**
     * Retourne une instance de MinifierInterface selon le $iType spécifié.
     *
     * @param int $iType type d'instance désiré
     * @param ShellAdapter $oShell
     * @throws \BadMethodCallException si type inconnu.
     * @return MinifierInterface une instance de MinifierInterface selon le $iType spécifié.
     */
    public static function getInstance ($iType, ShellAdapter $oShell)
    {
        switch ($iType) {
            case self::TYPE_JSMIN:
                $oMinifier = new JSMinAdapter('$this->aConfig[\'jsmin_path\']', $oShell);
                break;

            default:
                throw new \BadMethodCallException("Unknown type: '$iType'!");
                break;
        }
        return $oMinifier;
    }

    /**
     * Simple factory, non instanciable.
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
