<?php

namespace Goteo\Benzina\Pump;

interface PumpInterface
{
    /**
     * Sets flexible configuration values for this pump.
     *
     * @param array $config The configuration array
     */
    public function setConfig(array $config = []): void;

    /**
     * Read the configuration values for this pump.
     *
     * @param string|null $key     A configuration array key to return
     * @param mixed|null  $default Any value to return in case the configuration key does not exist
     *
     * @return array The configuration value at the specified key, or full keys and values if null
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed;

    /**
     * Determines if the sample can be pumped.
     *
     * @param mixed $sample A sample of the records, e.g: A row from an user table
     */
    public function supports(mixed $sample): bool;

    /**
     * Pump a data record into a final destination.
     *
     * @param mixed $record A pumped record, e.g: A row from an user table
     */
    public function pump(mixed $record): void;
}
