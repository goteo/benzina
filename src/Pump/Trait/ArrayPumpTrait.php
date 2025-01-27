<?php

namespace Goteo\BenzinaBundle\Pump\Trait;

trait ArrayPumpTrait
{
    /**
     * Determine if the data array has all the necessary keys.
     *
     * @param array $sample A sample of the data
     * @param array $keys   The keys that the data should have
     */
    public function hasAllKeys(array $sample, array $keys): bool
    {
        if (count(\array_diff($keys, \array_keys($sample))) === 0) {
            return true;
        }

        return false;
    }
}
