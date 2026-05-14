<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Strategy;

use Override;
use PowderBlue\Downloader\Exception\WgetDownloadFailedException;
use PowderBlue\Downloader\StrategyInterface;

use function array_replace;
use function escapeshellarg;
use function exec;
use function implode;
use function unlink;

class WgetStrategy implements StrategyInterface
{
    /**
     * @throws WgetDownloadFailedException If it failed to download the specified file
     */
    #[Override]
    public function downloadFile(
        string $fromUrl,
        string $toPathname,
        array $options = [],
    ): void {
        $commandArray = [
            'wget',
        ];

        $options = array_replace([
            // Overrideable:
            '--timeout' => '2',  // (Seconds)
            '--tries' => '3',  // Prevents infinite retries on a failing URL
            '--no-check-certificate' => '',
        ], $options, [
            // Not overrideable:
            '--output-document' => escapeshellarg($toPathname),
            '--quiet' => '',
        ]);

        foreach ($options as $name => $value) {
            $commandArray[] = '' === $value
                ? $name
                : "{$name}={$value}"
            ;
        }

        $commandArray[] = escapeshellarg($fromUrl);

        $resultCode = 1;
        exec(implode(' ', $commandArray), $ignore, $resultCode);

        if (0 !== $resultCode) {
            unlink($toPathname);

            throw new WgetDownloadFailedException($fromUrl, $toPathname, $resultCode);
        }
    }
}
