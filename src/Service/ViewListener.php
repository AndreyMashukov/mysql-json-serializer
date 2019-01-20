<?php

namespace Mash\MysqlJsonSerializer\Service;

use Doctrine\ORM\EntityManager;
use Mash\MysqlJsonSerializer\QueryBuilder\SQL\SQL;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ViewListener implements EventSubscriberInterface
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onView', 200],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (!$result instanceof SQL) {
            return;
        }

        /** @var EntityManager $manager */
        $manager = $this->registry->getManager();
        $query   = $manager->getConnection()->prepare($result);
        $query->execute($result->getParameters());
        $result = $query->fetchAll(\PDO::FETCH_COLUMN);

        $event->setControllerResult(\json_decode($result[0], true));
    }
}
