<?php

declare(strict_types=1);

namespace Unit\Domain\Reference;

use App\Domain\Exception\InvalidArgumentException;
use App\Domain\Reference\Name;
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    /**
     * @dataProvider invalidNameDataProvider
     */
    public function testCreateNameWithInvalidString(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Name::fromString($value);
    }

    public function invalidNameDataProvider(): array
    {
        return [
            'to short name' => ['7e11'],
            'to long name' => ['chart?chs=500x500&chma=0,0,100,1000000FF&chd=t%3A122%2C42%2C0000FF&c2Chco=FF0000%2' .
                'CFFFF00%7CFF8000%2C00FF00%7C00FF00%2C0000FF&chd=t%3A122%2C42%2C17%2C10%2C8%2C7%2C7%2C7%2C7%2C6%2C' .
                '6%2C6%2C6%2C5%2C5&chl=122%7C42%7C17%7C10%7C8%7C7%7C7%7C7%7C7%7C6%7C6%7C6%7C6%7C5%7C5&chdl=android' .
                '%7Cjava%7Cstack-trace%7Cbroadcastreceiver%7Candroid-ndk%7Cuser-agent%7Candroid-webview%7Cwebview%' .
                '7Cbackground%7Cmultithreading%7Candroid-source%7Csms%7Cadb%7Csollections%7Cactivity|Chart'],
        ];
    }
}
