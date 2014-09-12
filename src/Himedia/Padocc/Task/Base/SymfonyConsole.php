<?php
/**
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
 */

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\Task;

/**
 * Executes a command via the Symfony2 console tool.
 *
 * Deploying your {@link http://symfony.com/doc/current/cookbook/deployment/tools.html Symfony2} application
 * can be a complex and varied task depending on your setup. The typical steps include updating the vendor dependencies
 * (typically done via {@link https://getcomposer.org Composer}), running database migrations or similar tasks
 * to update your database, clearing and warming up your cache, making a dump of your assets, etc.
 *
 * Most of these tasks can be achieved using the Symfony2 console tool which comes with the framework.
 *
 * <b>Attributes:</b>
 *
 * - <tt>dir</tt> (required): Path to the Symfony2 application
 * - <tt>command</tt> (required): The command name to be executed
 * - <tt>arguments</tt> (optional): Arguments you want to pass to the command
 *
 * This task must be used within a {@link Environment} or {@link Target} task.
 *
 * <b>Example:</b>
 *
 * <pre>
 * <symfonyconsole dir="/tmp/build/www.foo.com" command="cache:clear" arguments="--env=prod --no-debug"/>
 * </pre>
 *
 * Clears and warms up the cache of your application.
 *
 * Equals to the following command line.
 *
 * <pre>
 * $ php /tmp/build/www.foo.com/app/console cache:clear --env=prod --no-debug
 * </pre>
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class SymfonyConsole extends Task
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'dir'       => AttributeProperties::DIR |
                           AttributeProperties::REQUIRED |
                           AttributeProperties::ALLOW_PARAMETER,
            'command'   => AttributeProperties::REQUIRED,
            'arguments' => AttributeProperties::OPTIONAL
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'symfonyconsole';
    }

    /**
     * {@inheritdoc}
     */
    protected function centralExecute()
    {
        parent::centralExecute();
        $this->getLogger()->info('+++');

        $commandName = $this->aAttValues['command'];
        $commandLine = 'app/console "' . $commandName . '"';

        if (isset($this->aAttValues['arguments'])) {
            $commandLine .= ' ' . $this->aAttValues['arguments'];
        }

        foreach ($this->processPath($this->aAttValues['dir']) as $dir) {
            list(, , $localPath) = $this->oShell->isRemotePath($dir);

            $this->getLogger()->info("Execute Symfony 'app/console $commandName' on '$dir':+++");
            $result = $this->oShell->execSSH("php $localPath/$commandLine", $dir);
            $this->getLogger()->info(implode("\n", $result) . '---');
        }

        $this->getLogger()->info('---');
    }
}
