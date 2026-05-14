<?php declare(strict_types=1);

namespace PowderBlue\Downloader;

/**
 * @phpstan-type GenericOptionsArray array<string,string>
 */
interface StrategyInterface
{
    /**
     * Attempts to download the file with path `$fromUrl` to `$toPathname`; throws an exception if it was unsuccessful
     *
     * N.B. Overwrites if there's an existing file with the same pathname
     *
     * @phpstan-param GenericOptionsArray $options
     * @todo Generic options!
     */
    public function downloadFile(
        string $fromUrl,
        string $toPathname,
        array $options = [],
    ): void;
}
