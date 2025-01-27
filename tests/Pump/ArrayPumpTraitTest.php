<?php

namespace Goteo\Tests\BenzinaBundle\Pump;

use Goteo\BenzinaBundle\Pump\Trait\ArrayPumpTrait;
use PHPUnit\Framework\TestCase;

class ArrayPumpTraitTest extends TestCase
{
    use ArrayPumpTrait;

    public function testHasAllKeysFalseOnMissingKeys()
    {
        $data = ['key1' => null, 'key2' => null];
        $keys = ['key1', 'key2', 'key3'];

        $result = $this->hasAllKeys($data, $keys);

        $this->assertFalse($result);
    }

    public function testHasAllKeysTrueOnExtraKeys()
    {
        $data = ['key1' => null, 'key2' => null, 'key3' => null];
        $keys = ['key1', 'key2'];

        $result = $this->hasAllKeys($data, $keys);

        $this->assertTrue($result);
    }
}
