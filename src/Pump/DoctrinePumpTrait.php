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

    private int $flushBatchSize = 16;
    private int $flushBatchCount = 0;

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

    public function setPreventFlushAndClear(bool $preventFlushAndClear): void
    {
        $this->preventFlushAndClear = $preventFlushAndClear;
    }

    public function setFlushBatchSize(int $flushBatchSize): void
    {
        $this->flushBatchSize = $flushBatchSize;
    }

    public function persist(object $object, array $context): void
    {
        if ($this->isDryRun($context)) {
            return;
        }

        $this->entityManager->persist($object);

        if ($this->preventFlushAndClear) {
            return;
        }

        if (++$this->flushBatchCount >= $this->flushBatchSize || $this->isAtEnd($context)) {
            $this->entityManager->flush();
            $this->entityManager->clear();

            $this->flushBatchCount = 0;
        }
    }
}
