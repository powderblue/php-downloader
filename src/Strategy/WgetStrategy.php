<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Strategy;

use Override;
use PowderBlue\Downloader\Exception\DownloadFailedException;
use PowderBlue\Downloader\StrategyInterface;

use function array_intersect_key;
use function array_replace;
use function escapeshellarg;
use function exec;
use function implode;
use function unlink;

class WgetStrategy implements StrategyInterface
{
    /**
     * @var array<int,string>
     */
    private const array WGET_ERROR_MESSAGES = [
        8 => 'The remote server experienced a problem or rejected our request',
    ];

    /**
     * @var array<string,string>
     */
    private const array OPTION_MAP = [
        'userAgent' => '--user-agent',
    ];

    private function createDownloadFailedException(
        string $fromUrl,
        string $toPathname,
        int $resultCode,
    ): DownloadFailedException {
        $message = "Failed to download `{$fromUrl}` to `{$toPathname}`: "
            . (self::WGET_ERROR_MESSAGES[$resultCode] ?? "Code {$resultCode}")
        ;

        return new DownloadFailedException($message);
    }

    /**
     * @throws DownloadFailedException If it failed to download the file
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

        $transformedOptions = [];

        foreach (array_intersect_key($options, self::OPTION_MAP) as $genericOptionName => $optionValue) {
            $specialOptionName = self::OPTION_MAP[$genericOptionName];
            $transformedOptions[$specialOptionName] = $optionValue;
        }

        $mergedOptions = array_replace([
            // Sensible defaults, overrideable:
            // '--timeout' => '30',  // (Seconds)
            // '--tries' => '3',    // Prevents infinite retries on a failing URL
            // '--no-check-certificate' => '',
        ], $transformedOptions, [
            // Not overrideable:
            '--output-document' => escapeshellarg($toPathname),
            '--quiet' => '',
        ]);

        foreach ($mergedOptions as $optionName => $optionValue) {
            $commandArray[] = '' === $optionValue
                ? $optionName
                : "{$optionName}={$optionValue}"
            ;
        }

        $commandArray[] = escapeshellarg($fromUrl);

        $resultCode = 1;

        exec(
            implode(' ', $commandArray),
            $ignore,
            $resultCode,
        );

        if (0 !== $resultCode) {
            unlink($toPathname);

            throw $this->createDownloadFailedException($fromUrl, $toPathname, $resultCode);
        }
    }
}
