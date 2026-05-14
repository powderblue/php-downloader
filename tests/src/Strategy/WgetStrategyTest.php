<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use PowderBlue\Downloader\Strategy\WgetStrategy;
use PowderBlue\Downloader\StrategyInterface;
use ReflectionClass;

class WgetStrategyTest extends TestCase
{
    public function testIsAStrategy(): void
    {
        $this->assertTrue(
            new ReflectionClass(WgetStrategy::class)->implementsInterface(StrategyInterface::class),
        );
    }
}
