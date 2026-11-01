<?php

namespace Wuwx\Sm3\Test;

use PHPUnit\Framework\TestCase;
use Wuwx\Sm3\Sm3;

class Sm3Test extends TestCase
{
    public function testGbt32905VectorAbc(): void
    {
        $this->assertSame(
            '66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0',
            Sm3::hash('abc')
        );
    }

    public function testGbt32905VectorAbcdRepeated(): void
    {
        $this->assertSame(
            'debe9ff92275b8a138604889c18e5a4d6fdb70e5387e5765293dcba39c0c5732',
            Sm3::hash(str_repeat('abcd', 16))
        );
    }

    public function testEmptyString(): void
    {
        $this->assertSame(
            '1ab21d8355cfa17f8e61194831e81a8f22bec8c728fefb747ed035eb5082aa2b',
            Sm3::hash('')
        );
    }

    public function testPaddingBoundaries(): void
    {
        $this->assertSame('288337eef51eec62e7544d7270424c8dbe656254c99852870a73b2453a6a7fb1', Sm3::hash(str_repeat('a', 55)));
        $this->assertSame('ba00ebedaab54065a5fd4f9f56326016203166bcee3eed44ea868d59d67aa3c8', Sm3::hash(str_repeat('a', 56)));
        $this->assertSame('616ec433c359e7c2b19f360e2b8f2a1b6e9ed76b8dc1a7d207b31a5341c611e9', Sm3::hash(str_repeat('a', 64)));
    }

    public function testBinaryData(): void
    {
        $this->assertSame('35b867ed6528bb46099058baf776e4eefcf98d6daccc0f678541899df16fd639', Sm3::hash("a\x00b"));
    }

    public function testUtf8(): void
    {
        $this->assertSame('a16c22b2e1f51d723df51cb481f4680176a720849ee66440e78711112eed7e47', Sm3::hash('中文'));
    }

    public function testOutputFormat(): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', Sm3::hash('anything'));
    }
}
