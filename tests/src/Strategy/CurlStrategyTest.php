<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Tests\Strategy;

use PHPUnit\Framework\TestCase;
use PowderBlue\Downloader\Strategy\CurlStrategy;
use PowderBlue\Downloader\StrategyInterface;
use ReflectionClass;

class CurlStrategyTest extends TestCase
{
    public function testIsAStrategy(): void
    {
        $this->assertTrue(
            new ReflectionClass(CurlStrategy::class)->implementsInterface(StrategyInterface::class),
        );
    }
}
