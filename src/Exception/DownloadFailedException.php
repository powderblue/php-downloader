<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Exception;

use RuntimeException;
use Throwable;

use const null;

class DownloadFailedException extends RuntimeException
{
    public function __construct(
        private string $fromUrl,
        private string $toPathname,
        private string $detail = '',
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        $message = "Failed to download `{$this->fromUrl}` to `{$this->toPathname}`";

        if ('' !== $this->detail) {
            $message .= ": {$this->detail}";
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
