<?php

namespace Himedia\Padocc;

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
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @license Apache License, Version 2.0
 */
class DeploymentStatus
{

    /**
     * In queue.
     * @var string
     */
    const QUEUED      = 'queued';

    const IN_PROGRESS = 'in progress';

    const FAILED      = 'failed';

    const WARNING     = 'warning';

    const SUCCESSFUL  = 'successful';
}
