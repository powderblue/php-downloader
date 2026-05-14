<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Tests;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PowderBlue\Downloader\Downloader;
use PowderBlue\Downloader\Exception\DownloadFailedException;
use ReflectionClass;
use SplFileInfo;

use function unlink;

class DownloaderTest extends TestCase
{
    private static function getFixturesDir(): string
    {
        $classShortName = new ReflectionClass(__CLASS__)->getShortName();

        return __DIR__ . "/{$classShortName}";
    }

    /** @return array<mixed[]> */
    public static function providesConstructorArgs(): array
    {
        return [
            [
                __DIR__,
                [],
                [__DIR__],
            ],
            [
                __DIR__,
                ['image/jpeg'],
                [__DIR__, ['image/jpeg']],
            ],
        ];
    }

    /**
     * @param string[] $acceptedMimeTypes
     * @param array{0:string,1:string[]} $constructorArgs
     */
    #[DataProvider('providesConstructorArgs')]
    public function testIsInstantiable(
        string $downloadsDir,
        array $acceptedMimeTypes,
        array $constructorArgs,
    ): void {
        $downloader = new Downloader(...$constructorArgs);

        $this->assertSame($downloadsDir, $downloader->getDownloadsDir());
        $this->assertSame($acceptedMimeTypes, $downloader->getAcceptedMimeTypes());
    }

    public function testConstructorThrowsAnExceptionIfThePathDoesNotReferToADirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The pathname does not refer to a directory');

        $downloader = new Downloader(__FILE__);
    }

    public function testDownload(): void
    {
        $fixturesDir = self::getFixturesDir() . '/' . __FUNCTION__;
        $basenameOfDownloadedFile = 'downloaded_image';
        $pathnameOfDownloadedFile = "{$fixturesDir}/{$basenameOfDownloadedFile}";
        $urlOfFileToDownload = 'https://picsum.photos/id/13/100';

        $this->assertFileDoesNotExist($pathnameOfDownloadedFile);

        $downloader = new Downloader($fixturesDir);
        $downloadedFileInfo = $downloader->download($urlOfFileToDownload, $basenameOfDownloadedFile);

        $this->assertInstanceOf(SplFileInfo::class, $downloadedFileInfo);
        $this->assertTrue($downloadedFileInfo->isFile());

        unlink($pathnameOfDownloadedFile);
    }

    public function testDownloadThrowsAnExceptionIfTheUrlIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The URL is invalid');

        $downloader = new Downloader(__DIR__);
        $downloader->download('*&^*&^((*&(sdlfk092380jskdfljlskjflksfLKJKLJL', 'irrelevant');
    }

    public function testDownloadThrowsAnExceptionIfTheFileCouldNotBeDownloaded(): void
    {
        $fixturesDir = self::getFixturesDir() . '/' . __FUNCTION__;
        $basenameOfDownloadedFile = 'irrelevant';
        $pathnameOfDownloadedFile = "{$fixturesDir}/{$basenameOfDownloadedFile}";
        $urlOfFileToDownload = 'https://picsum.photos/non-existent';

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionMessage("Failed to download `{$urlOfFileToDownload}` to `{$pathnameOfDownloadedFile}`: ");

        $downloader = new Downloader($fixturesDir);
        $downloader->download($urlOfFileToDownload, $basenameOfDownloadedFile);
    }

    public function testDownloadDoesNotLeaveTrashIfItFailedToDownloadTheFile(): void
    {
        $fixturesDir = self::getFixturesDir() . '/' . __FUNCTION__;
        $basenameOfDownloadedFile = 'irrelevant';
        $pathnameOfDownloadedFile = "{$fixturesDir}/{$basenameOfDownloadedFile}";
        $urlOfFileToDownload = 'https://picsum.photos/non-existent';

        $this->assertFileDoesNotExist($pathnameOfDownloadedFile);

        $downloader = new Downloader($fixturesDir);

        try {
            $downloader->download($urlOfFileToDownload, $basenameOfDownloadedFile);
        } catch (Exception $ex) {
        }

        $this->assertFileDoesNotExist($pathnameOfDownloadedFile);
    }
}
