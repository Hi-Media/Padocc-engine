<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Minifier\Factory;
use Himedia\Padocc\Minifier\MinifierInterface;
use Himedia\Padocc\Task;

/**
 * Minifie/compacte un ou plusieurs fichiers soit JS soit CSS.
 * À inclure dans une tâche ''env'' ou ''target''.
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
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license Apache License, Version 2.0
 */
class Minifier extends Task
{

    /**
     * Instance MinifierInterface.
     * @var MinifierInterface
     */
    private $oMinifier;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'srcfile' => AttributeProperties::FILEJOKER | AttributeProperties::ALLOW_PARAMETER,
            'destfile' => AttributeProperties::FILE | AttributeProperties::ALLOW_PARAMETER
        );
        $this->oMinifier = Factory::getInstance(Factory::TYPE_JSMIN, $this->oShell);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'minify';
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute()
    {
        parent::centralExecute();

        $sMsg = "+++Minify '" . $this->aAttValues['srcfile'] . "' into '" . $this->aAttValues['destfile'] . "'.";
        $this->getLogger()->info($sMsg);

        $aSrcPaths = $this->processPath($this->aAttValues['srcfile']);
        $sDestPaths = $this->processSimplePath($this->aAttValues['destfile']);
        $this->oMinifier->minify($aSrcPaths, $sDestPaths);

        $this->getLogger()->info('---');
    }
}
