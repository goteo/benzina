<?php

namespace Goteo\Benzina;

use Goteo\Benzina\Pump\PumpInterface;
use Goteo\Benzina\Source\SourceInterface;

class Benzina
{
    /** @var PumpInterface[] */
    private array $pumps = [];

    public function __construct(
        iterable $instanceof,
    ) {
        $this->pumps = \iterator_to_array($instanceof);
    }

    /**
     * Get the Pumps that can process the records.
     *
     * @return PumpInterface[]
     */
    public function getPumpsFor(SourceInterface $source): array
    {
        $sample = $source->sample();

        $pumps = [];
        foreach ($this->pumps as $pump) {
            if ($pump->supports($sample)) {
                $pumps[] = $pump;
            }
        }

        return $pumps;
    }
}
