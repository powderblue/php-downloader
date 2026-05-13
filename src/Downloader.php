<?php declare(strict_types=1);

namespace PowderBlue\Downloader;

use InvalidArgumentException;
use PowderBlue\Downloader\Exception\FileInvalidException;
use PowderBlue\Downloader\Exception\WgetDownloadFailedException;
use RuntimeException;
use SplFileInfo;

use function array_rand;
use function array_replace;
use function escapeshellarg;
use function filter_var;
use function implode;
use function in_array;
use function is_dir;
use function is_file;
use function mime_content_type;
use function parse_url;
use function preg_replace;
use function strrpos;
use function substr;
use function unlink;

use const false;
use const FILTER_VALIDATE_URL;
use const DIRECTORY_SEPARATOR;
use const null;
use const PHP_URL_PATH;
use const true;

/**
 * @phpstan-type WgetOptionsArray array<string,string>
 */
class Downloader
{
    /**
     * @var string[]
     */
    private const array USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.2420.81',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 OPR/109.0.0.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.4; rv:124.0) Gecko/20100101 Firefox/124.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 OPR/109.0.0.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux i686; rv:124.0) Gecko/20100101 Firefox/124.0',
    ];

    /**
     * Attempts to download the file with path `$fromUrl` to `$toPathname`; throws an exception if it was unsuccessful
     *
     * N.B. Overwrites if there's an existing file with the same pathname
     *
     * @phpstan-param WgetOptionsArray $wgetOptions
     * @throws WgetDownloadFailedException If it failed to download the specified file
     */
    private static function downloadFile(
        string $fromUrl,
        string $toPathname,
        array $wgetOptions = [],
    ): void {
        $commandArray = [
            'wget',
        ];

        $wgetOptions = array_replace([
            // Overrideable:
            '--timeout' => '2',  // (Seconds)
            '--tries' => '3',  // Prevents infinite retries on a failing URL
            '--no-check-certificate' => '',
        ], $wgetOptions, [
            // Not overrideable:
            '--output-document' => escapeshellarg($toPathname),
            '--quiet' => '',
            '--user-agent' => escapeshellarg(self::USER_AGENTS[array_rand(self::USER_AGENTS)]),
        ]);

        foreach ($wgetOptions as $name => $value) {
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

    /**
     * @param string[] $acceptedMimeTypes
     * @throws InvalidArgumentException If the pathname does not refer to a directory
     */
    public function __construct(
        // phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.Indent
        // phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.EmptyLine
        // phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorrect
        private string $downloadsDir {
            set {
                if (!is_dir($value)) {
                    throw new InvalidArgumentException('The pathname does not refer to a directory');
                }

                $this->downloadsDir = $value;
            }
        },
        // phpcs:enable
        // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
        private array $acceptedMimeTypes = [],
    ) {
    }

    // phpcs:ignore PSR12.Functions.ReturnTypeDeclaration.SpaceBeforeReturnType -- Misreported
    public function getDownloadsDir(): string
    {
        return $this->downloadsDir;
    }

    /**
     * @return string[]
     */
    public function getAcceptedMimeTypes(): array
    {
        return $this->acceptedMimeTypes;
    }

    /**
     * Creates a pathname, based on the name of the remote file, that's unique in the downloads directory
     */
    private function createTempFilePathname(string $url): string
    {
        /** @var string */
        $urlPath = parse_url($url, PHP_URL_PATH);
        $posLastSlash = strrpos($urlPath, '/');

        $basename = false === $posLastSlash
            ? $urlPath
            : substr($urlPath, $posLastSlash + 1)
        ;

        // Do our best to prevent any later weirdness
        /** @var string */
        $basename = preg_replace('~[^a-zA-Z0-9_\-\.]~', '-', $basename);

        $posLastPeriod = strrpos($basename, '.');

        $basenameSuffix = '';
        $basenameMinusSuffix = $basename;

        // Includes period.
        if (false !== $posLastPeriod) {
            $basenameSuffix = substr($basename, $posLastPeriod);
            $basenameMinusSuffix = substr($basename, 0, $posLastPeriod);
        }

        // Ensure the filename will be unique in the downloads directory:

        $nextBasenameVersion = 1;
        $newBasename = "{$basenameMinusSuffix}{$basenameSuffix}";

        do {
            $newPathname = $this->getDownloadsDir() . DIRECTORY_SEPARATOR . $newBasename;
            $newBasename = "{$basenameMinusSuffix}_{$nextBasenameVersion}{$basenameSuffix}";
            $nextBasenameVersion++;
        } while (is_file($newPathname));

        return $newPathname;
    }

    private function validateFile(
        string $pathname,
        string|null &$whyInvalid = null,
    ): bool {
        $fileMimeType = mime_content_type($pathname);

        if (false === $fileMimeType) {
            $whyInvalid = 'The MIME type could not be determined';

            return false;
        }

        if ($this->getAcceptedMimeTypes() && !in_array($fileMimeType, $this->getAcceptedMimeTypes())) {
            $whyInvalid = "The MIME type, `{$fileMimeType}`, is not permitted";

            return false;
        }

        return true;
    }

    /**
     * If a destination basename is specified then an existing file will be overwritten
     *
     * @phpstan-param WgetOptionsArray $wgetOptions
     * @throws InvalidArgumentException If the URL is invalid
     * @throws RuntimeException If the downloaded file is invalid
     */
    public function download(
        string $fromUrl,
        string|null $toBasename = null,
        array $wgetOptions = [],
    ): SplFileInfo {
        if (!filter_var($fromUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('The URL is invalid');
        }

        $toPathname = null === $toBasename
            ? $this->createTempFilePathname($fromUrl)
            : $this->getDownloadsDir() . "/{$toBasename}"
        ;

        self::downloadFile($fromUrl, $toPathname, $wgetOptions);

        $whyInvalid = null;

        if (!$this->validateFile($toPathname, $whyInvalid)) {
            /** @var string $whyInvalid */

            unlink($toPathname);

            throw new FileInvalidException($whyInvalid);
        }

        return new SplFileInfo($toPathname);
    }
}
