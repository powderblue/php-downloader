<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Tests;

use PHPUnit\Framework\TestCase;
use PowderBlue\Downloader\Exception\DownloadFailedException;

class DownloadFailedExceptionTest extends TestCase
{
    public function testIsInstantiable(): void
    {
        $fromUrl = 'https://example.com/README.md';
        $toPathname = '/path/to/README.md';

        $exception = new DownloadFailedException($fromUrl, $toPathname, 'Detailed information about the error');

        $this->assertSame(
            "Failed to download `{$fromUrl}` to `{$toPathname}`: Detailed information about the error",
            $exception->getMessage(),
        );
    }

    public function testGeterrordata(): void
    {
        $fromUrl = 'https://example.com/README.md';
        $toPathname = '/path/to/README.md';

        $exception = new DownloadFailedException($fromUrl, $toPathname, 'Foo');

        $this->assertEquals([
            'fromUrl' => $fromUrl,
            'toPathname' => $toPathname,
        ], $exception->getErrorData());
    }
}
