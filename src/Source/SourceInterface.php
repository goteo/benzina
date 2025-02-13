<?php

namespace Goteo\Benzina\Source;

interface SourceInterface
{
    /**
     * Obtain the records from the source.
     * 
     * @return \Traversable A traversable collection of records
     */
    public function records(): \Traversable;

    /**
     * Obtain a single sample record from the source.
     * 
     * @return mixed The sample record data
     */
    public function sample(): mixed;

    /**
     * @return int The total number of records available
     */
    public function size(): int;
}
