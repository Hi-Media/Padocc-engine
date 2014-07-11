<?php

namespace Himedia\Padocc;

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
