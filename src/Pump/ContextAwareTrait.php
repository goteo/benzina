<?php

namespace Goteo\Benzina\Pump;

trait ContextAwareTrait
{
    /**
     * @param array{
     *  source: \Goteo\Benzina\Source\SourceInterface,
     *  options: array<string, mixed>,
     *  arguments: array<string, mixed>,
     *  previous_record?: mixed} $context
     */
    private function isDryRun(array $context): bool
    {
        $options = @$context['options'] ?? [];

        if (\array_key_exists('dry-run', $options)) {
            return $options['dry-run'];
        }

        return false;
    }
}
