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
 * Install dependencies with Bower package manager.
 *
 * {@link http://bower.io Bower} is a package manager for front-end packages and assets.
 *
 * Bower keeps track of your project dependencies in a manifest file named <tt>bower.json</tt>. So assuming that your
 * working directory contains a manifest file, you can fetch and install packages required to your project by simply
 * using the <tt><bower/></tt> task with the following attributes.
 *
 * Note: you must be aware that the task actually performs a "<tt>bower install</tt>" in command line.
 *
 * <b>Attributes:</b>
 *
 * - <tt>dir</tt> (required): Working directory (where to find the manifest file, <tt>bower.json</tt>)
 * - <tt>options</tt> (optional): Options you want to pass to the command
 *
 * <b>Allowed Options:</b>
 *
 * - <tt>-F</tt>, <tt>--force-latest</tt>: Force latest version on conflict
 * - <tt>-p</tt>, <tt>--production</tt>: Do not install project "<tt>devDependencies</tt>" (Development dependencies)
 * - <tt>-q</tt>, <tt>--quiet</tt>: Only output important information
 * - <tt>-s</tt>, <tt>--silent</tt>: Do not output anything, besides errors
 * - <tt>-V</tt>, <tt>--verbose</tt>: Makes output more verbose
 *
 * This task must be used within a {@link Environment} or {@link Target} task.
 *
 * <b>Example:</b>
 *
 * <pre>
 * <bower dir="/tmp/build/foo.com/conf" options="--production" />
 * </pre>
 *
 * Installs project dependencies as defined in the manifest file <tt>/tmp/build/foo.com/conf/bower.json</tt>.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Letournel <gletournel@hi-media.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class Bower extends Task
{
    /**
     * @var array A list of options that are allowed to be passed to the command
     */
    private static $allowedOptions = array(
        '-F', '--force-latest',
        '-p', '--production',
        '-q', '--quiet',
        '-s', '--silent',
        '-V', '--verbose'
    );

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        parent::init();

        $this->aAttrProperties = array(
            'dir'     => AttributeProperties::DIR |
                         AttributeProperties::REQUIRED |
                         AttributeProperties::ALLOW_PARAMETER,
            'options' => AttributeProperties::OPTIONAL
        );
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public static function getTagName()
    {
        return 'bower';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \DomainException When trying to use an option which is not allowed
     */
    public function check()
    {
        parent::check();

        if (empty($this->aAttValues['options'])) {
            $this->aAttValues['options'] = '';
        }

        $options = explode(' ', $this->aAttValues['options']);
        $allowed = array_flip(self::$allowedOptions);

        foreach ($options as $opt) {
            if ('-' == substr($opt, 0, 1) && !isset($allowed[$opt])) {
                throw new \DomainException('Option not allowed: ' . $opt);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException When Bower is not installed
     */
    protected function centralExecute()
    {
        parent::centralExecute();
        $this->getLogger()->info('+++');

        // Checks if Bower is installed on the system
        $checkCommand = 'which bower 1>/dev/null 2>&1 && echo 1 || echo 0';

        // Skip interactive operations by default
        $options = trim('--config.interactive=false ' . $this->aAttValues['options']);

        foreach ($this->processPath($this->aAttValues['dir']) as $dir) {
            $result = $this->oShell->execSSH($checkCommand, $dir);

            if (!isset($result[0]) || $result[0] !== '1') {
                throw new \RuntimeException('Bower is not installed!');
            }

            $this->getLogger()->info("Execute bower on '$dir':+++");
            list(, , $localPath) = $this->oShell->isRemotePath($dir);
            $result = $this->oShell->execSSH('cd "' . $localPath . '" && bower install ' . $options, $dir);
            $this->getLogger()->info(implode("\n", $result) . '---');
        }

        $this->getLogger()->info('---');
    }
}
