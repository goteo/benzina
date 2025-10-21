<?php

namespace Goteo\Benzina\Pump;

use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait DoctrinePumpTrait
{
    use ContextAwareTrait;

    private EntityManagerInterface $entityManager;

    private bool $preventFlushAndClear = false;

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    #[Required()]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $middlewares = $entityManager->getConnection()->getConfiguration()->getMiddlewares();
        $middlewares = \array_filter($middlewares, fn($m) => !$m instanceof LoggingMiddleware);
        $entityManager->getConnection()->getConfiguration()->setMiddlewares($middlewares);

        $entityManager->getMetadataFactory()->getAllMetadata();

        $this->entityManager = $entityManager;
    }

    /**
     * Persist an entity, flush and clear immediately.
     * Fastest possible in Doctrine ORM 3 for complex graphs.
     */
    public function persist(object $object, array $context): void
    {
        if ($this->isDryRun($context)) {
            return;
        }

        $this->entityManager->persist($object);

        if ($this->preventFlushAndClear) {
            return;
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function setPreventFlushAndClear(bool $prevent): void
    {
        $this->preventFlushAndClear = $prevent;
    }
}
