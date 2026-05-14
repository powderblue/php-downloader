<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Strategy;

use Override;
use PowderBlue\Downloader\StrategyInterface;
use RuntimeException;

use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function fclose;
use function fopen;

use const CURLOPT_FILE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const false;
use const true;

class CurlStrategy implements StrategyInterface
{
    /**
     * @throws RuntimeException If it failed to open the file for writing
     * @throws RuntimeException If it failed to initialize a cURL session
     * @throws RuntimeException If it failed to set all cURL options
     * @throws RuntimeException If it failed to execute the cURL session
     */
    #[Override]
    public function downloadFile(
        string $fromUrl,
        string $toPathname,
        array $options = [],
    ): void {
        $fp = fopen($toPathname, 'wb');

        if (false === $fp) {
            throw new RuntimeException("Failed to open `{$toPathname}` for writing");
        }

        try {
            $ch = curl_init($fromUrl);

            if (false === $ch) {
                throw new RuntimeException("Failed to initialize a cURL session for `{$fromUrl}`");
            }

            $optionsWereApplied = curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            if (false === $optionsWereApplied) {
                throw new RuntimeException("Failed to set all cURL options for `{$fromUrl}`");
            }

            $sessionWasPerformed = curl_exec($ch);

            if (false === $sessionWasPerformed) {
                throw new RuntimeException("Failed to execute cURL session for `{$fromUrl}`");
            }
        } finally {
            fclose($fp);
        }
    }
}
