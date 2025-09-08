<?php

namespace Goteo\Benzina\Pump;

use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use Symfony\Contracts\Service\Attribute\Required;

trait DoctrinePumpTrait
{
    use ContextAwareTrait;

    private EntityManagerInterface $entityManager;

    private array $toBePumped;

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    #[Required()]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $entityManager = clone $entityManager;

        $middlewares = $entityManager->getConnection()->getConfiguration()->getMiddlewares();
        $middlewares = \array_filter($middlewares, fn ($m) => !$m instanceof LoggingMiddleware);

        $entityManager->getConnection()->getConfiguration()->setMiddlewares($middlewares);

        $this->entityManager = $entityManager;
    }

    public function persist(object $object, array $context): void
    {
        if ($this->isDryRun($context)) {
            return;
        }

        $this->toBePumped[] = $object;

        if ($this->isAtEnd($context)) {
            $this->doPersist();
        }
    }

    public function doPersist()
    {
        $toBePumped = $this->toBePumped;
        $entityManager = $this->getEntityManager();

        $iterable = SimpleBatchIteratorAggregate::fromTraversableResult(
            call_user_func(static function () use ($entityManager, $toBePumped) {
                foreach ($toBePumped as $object) {
                    $entityManager->persist($object);

                    yield $object;
                }
            }),
            $entityManager,
            100,
        );

        \iterator_to_array($iterable);
    }
}
