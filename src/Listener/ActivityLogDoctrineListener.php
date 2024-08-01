<?php

namespace Ecosystem\ActivityLogDoctrineBundle\Listener;

use Ecosystem\ActivityLogDoctrineBundle\Interface\ActivityLogDoctrineInterface;
use Ecosystem\ActivityLogBundle\Service\ActivityLogService;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogDoctrineListener
{
    private const CREATE = 'create';
    private const UPDATE = 'update';
    private const DELETE = 'delete';

    public function __construct(
        private ActivityLogService $activityLog,
        private RequestStack $requestStack,
        private readonly string $id = 'system',
        private readonly string $screenName = 'system',
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ActivityLogDoctrineInterface) {
            return;
        }

        $triggerData = $this->getTriggerData();

        $this->activityLog->log(
            $this->getNamespace(get_class($entity)),
            self::CREATE,
            $entity->getActivityLogId(),
            $triggerData['type'],
            $triggerData['id'],
            $triggerData['screen'],
            [],
            []
        );
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ActivityLogDoctrineInterface) {
            return;
        }

        $triggerData = $this->getTriggerData();

        // Get record changes
        $changes = $args->getEntityChangeSet();

        // Process datetime fields
        foreach ($changes as $field => $value) {
            if (isset($value[0]) && $value[0] instanceof \DateTimeInterface) {
                $changes[$field][0] = $value[0]->format('Y-m-d H:i:s');
            }
            if (isset($value[1]) && $value[1] instanceof \DateTimeInterface) {
                $changes[$field][1] = $value[1]->format('Y-m-d H:i:s');
            }
        }

        // Discard unneeded changed fields
        $mutedFields = $entity->getActivityLogMutedFields();
        if (count($mutedFields) > 0) {
            foreach ($changes as $field => $value) {
                if (in_array($field, $mutedFields)) {
                    unset($changes[$field]);
                }
            }
        }

        // Process changed records
        $newRecords = [];
        $oldRecords = [];
        foreach ($changes as $field => $values) {
            $oldRecords[$field] = $values[0];
            $newRecords[$field] = $values[1];
        }

        $this->activityLog->log(
            $this->getNamespace(get_class($entity)),
            self::UPDATE,
            $entity->getActivityLogId(),
            $triggerData['type'],
            $triggerData['id'],
            $triggerData['screen'],
            $newRecords,
            $oldRecords
        );
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ActivityLogDoctrineInterface) {
            return;
        }

        $triggerData = $this->getTriggerData();

        $this->activityLog->log(
            $this->getNamespace(get_class($entity)),
            self::DELETE,
            $entity->getActivityLogId(),
            $triggerData['type'],
            $triggerData['id'],
            $triggerData['screen'],
            [],
            []
        );
    }

    private function getNamespace(string $entity): string
    {
        return ucfirst((new \ReflectionClass($entity))->getShortName());
    }

    /** @return array<string, string> */
    private function getTriggerData(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return [
                'type' => 'worker',
                'id' => $this->id,
                'screen' => $this->screenName
            ];
        }

        if (!$request->headers->has('x-ecosys-trigger')) {
            return [
                'type' => 'api',
                'id' => $this->id,
                'screen' => $this->screenName
            ];
        }

        $trigger = explode('|', base64_decode($request->headers->get('x-ecosys-trigger')));
        return [
            'type' => $trigger[0],
            'id' => $trigger[1],
            'screen' => $trigger[2]
        ];
    }
}
