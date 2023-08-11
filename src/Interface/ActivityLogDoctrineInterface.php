<?php

namespace Ecosystem\ActivityLogDoctrineBundle\Interface;

interface ActivityLogDoctrineInterface
{
    public function getActivityLogId(): string;

    /** @return string[] */
    public function getActivityLogMutedFields(): array;
}
