<?php

namespace Goteo\Benzina;

use Goteo\Benzina\Pump\PumpInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class BenzinaBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(PumpInterface::class)
            ->addTag('goteo.benzina.pump');
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import('../config/services.yaml');
    }
}
