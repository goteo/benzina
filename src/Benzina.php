<?php

namespace Goteo\BenzinaBundle;

use Goteo\BenzinaBundle\Pump\PumpInterface;

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
    public function getPumpsFor(\Iterator $source): array
    {
        $source->rewind();
        $source->next();
        
        $sample = $source->current();
        $source->rewind();

        $pumps = [];
        foreach ($this->pumps as $pump) {
            if ($pump->supports($sample)) {
                $pumps[] = $pump;
            }
        }

        return $pumps;
    }
}
