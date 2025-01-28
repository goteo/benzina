<?php

namespace Goteo\Tests\BenzinaBundle\Pump;

use Goteo\Benzina\Pump\ArrayPumpTrait;
use PHPUnit\Framework\TestCase;

class ArrayPumpTraitTest extends TestCase
{
    /** @var ArrayPumpTrait */
    private $mock;

    public function setUp(): void
    {
        $this->mock = $this->getMockForTrait(ArrayPumpTrait::class);
    }

    public function testHasAllKeysFalseOnMissingKeys()
    {
        $data = ['key1' => null, 'key2' => null];
        $keys = ['key1', 'key2', 'key3'];

        $result = $this->mock->hasAllKeys($data, $keys);

        $this->assertFalse($result);
    }

    public function testHasAllKeysTrueOnExtraKeys()
    {
        $data = ['key1' => null, 'key2' => null, 'key3' => null];
        $keys = ['key1', 'key2'];

        $result = $this->mock->hasAllKeys($data, $keys);

        $this->assertTrue($result);
    }
}
