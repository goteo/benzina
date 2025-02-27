<?php

namespace Goteo\Benzina\Pump;

interface PumpInterface
{
    /**
     * Determines if the sample can be pumped.
     *
     * @param mixed $sample A sample of the records, e.g: A row from an user table
     */
    public function supports(mixed $sample): bool;

    /**
     * Pump a data record into a final destination.
     *
     * @param mixed                                                                                  $record  A pumped record, e.g: A row from an user table
     * @param array{source: SourceInterface, options: array<string, mixed>, previous_record?: mixed} $context
     */
    public function pump(mixed $record, array $context): void;
}
