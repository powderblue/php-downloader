<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Strategy;

use Override;
use PowderBlue\Downloader\Exception\DownloadFailedException;
use PowderBlue\Downloader\StrategyInterface;
use RuntimeException;
use Throwable;

use function array_intersect_key;
use function array_replace;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function fclose;
use function fopen;
use function is_resource;
use function unlink;

use const CURLOPT_FILE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_USERAGENT;
use const false;
use const true;

class CurlStrategy implements StrategyInterface
{
    /**
     * @var array<string,int>
     */
    private const array OPTION_MAP = [
        'userAgent' => CURLOPT_USERAGENT,
    ];

    /**
     * @throws RuntimeException If it failed to open the file for writing
     * @throws RuntimeException If it failed to initialize a cURL session
     * @throws RuntimeException If it failed to set all cURL options
     * @throws DownloadFailedException If it failed to perform the cURL session
     * @throws DownloadFailedException If it failed to download the file
     */
    #[Override]
    public function downloadFile(
        string $fromUrl,
        string $toPathname,
        array $options = [],
    ): void {
        $transformedOptions = [];

        foreach (array_intersect_key($options, self::OPTION_MAP) as $genericOptionName => $optionValue) {
            $specialOptionName = self::OPTION_MAP[$genericOptionName];
            $transformedOptions[$specialOptionName] = $optionValue;
        }

        $fp = fopen($toPathname, 'wb');

        if (false === $fp) {
            throw new RuntimeException("Failed to open `{$toPathname}` for writing");
        }

        try {
            $ch = curl_init($fromUrl);

            if (false === $ch) {
                throw new RuntimeException("Failed to initialize a cURL session for `{$fromUrl}`");
            }

            $mergedOptions = array_replace($transformedOptions, [
                CURLOPT_FILE => $fp,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $allOptionsWereSet = curl_setopt_array($ch, $mergedOptions);

            if (false === $allOptionsWereSet) {
                throw new RuntimeException("Failed to set all cURL options for `{$fromUrl}`");
            }

            $sessionWasPerformed = curl_exec($ch);

            if (false === $sessionWasPerformed) {
                throw new DownloadFailedException("Failed to perform cURL session for `{$fromUrl}`: " . curl_error($ch));
            }

            /** @var int|false */
            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $requestWasSuccessful = $responseCode >= 200 && $responseCode < 300;

            if (!$requestWasSuccessful) {
                throw new DownloadFailedException("Failed to download `{$fromUrl}` to `{$toPathname}`: response code {$responseCode}");
            }
        } catch (Throwable $t) {
            fclose($fp);
            unlink($toPathname);

            throw $t;
        } finally {
            // (The file pointer will still be open if the file was successfully downloaded)
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }
}
