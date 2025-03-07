<?php

namespace Goteo\Tests\BenzinaBundle\Stream;

use Goteo\Benzina\Stream\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    private const CHUNK_SIZE = 100;
    private const STREAM_SIZE = 1000;

    private Stream $stream;

    public function setUp(): void
    {
        $this->stream = new Stream(\str_repeat('0', self::STREAM_SIZE));
    }

    public function testStreamHasSize()
    {
        $size = $this->stream->size();

        $this->assertEquals(self::STREAM_SIZE, $size);
    }

    public function testStreamReadsLength()
    {
        $chunk = $this->stream->read(self::CHUNK_SIZE + 1);

        $this->assertEquals(\str_repeat('0', self::CHUNK_SIZE), $chunk);
    }

    public function testStreamTells()
    {
        $this->assertEquals(0, $this->stream->tell());

        $chunk = $this->stream->read(self::CHUNK_SIZE);

        $this->assertEquals(\strlen($chunk), $this->stream->tell());
    }
}
