<?php

namespace Ecosystem\ActivityLogDoctrineBundle\Listener;

use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Ecosystem\ActivityLogBundle\Service\ActivityLogService;
use Symfony\Contracts\Service\Attribute\Required;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class ActivityLogDoctrineListener
{
    public function __construct(private ActivityLogService $activityLog)
    {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
    }
}
