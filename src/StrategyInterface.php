<?php declare(strict_types=1);

namespace PowderBlue\Downloader;

use PowderBlue\Downloader\Exception\DownloadFailedException;

/**
 * @phpstan-type GenericOptionsArray array{
 *   userAgent?: non-empty-string,
 * }
 */
interface StrategyInterface
{
    /**
     * Attempts to download the file with path `$fromUrl` to `$toPathname`; throws an exception if it was unsuccessful
     *
     * N.B. Overwrites if there's an existing file with the same pathname
     *
     * @phpstan-param GenericOptionsArray $options
     * @throws DownloadFailedException If it failed to download the file
     */
    public function downloadFile(
        string $fromUrl,
        string $toPathname,
        array $options = [],
    ): void;
}
