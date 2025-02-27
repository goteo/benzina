<?php

namespace Goteo\Benzina\Pump;

use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait DoctrinePumpTrait
{
    private EntityManagerInterface $entityManager;

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
        if (
            \array_key_exists('dry-run', $context['options'])
            && true === $context['options']['dry-run']
        ) {
            return;
        }

        $this->entityManager->persist($object);

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
