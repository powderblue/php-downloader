<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Exception;

use RuntimeException;
use Throwable;

use const null;

class WgetDownloadFailedException extends RuntimeException
{
    /**
     * @var array<int,string>
     */
    private const array WGET_ERROR_MESSAGES = [
        8 => 'The remote server experienced a problem or rejected our request',
    ];

    private static function getWgetErrorMessage(int $code): string
    {
        return self::WGET_ERROR_MESSAGES[$code]
            ?? "Code {$code}"
        ;
    }

    public function __construct(
        private string $fromUrl,
        private string $toPathname,
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        $message = "Failed to download `{$this->fromUrl}` to `{$this->toPathname}`";

        if ($code) {
            $message .= ': ' . self::getWgetErrorMessage($code);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array{fromUrl:string,toPathname:string}
     */
    public function getErrorData(): array
    {
        return [
            'fromUrl' => $this->fromUrl,
            'toPathname' => $this->toPathname,
        ];
    }
}
