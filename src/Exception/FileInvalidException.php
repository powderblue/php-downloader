<?php declare(strict_types=1);

namespace PowderBlue\Downloader\Exception;

use RuntimeException;
use Throwable;

use const null;

class FileInvalidException extends RuntimeException
{
    public function __construct(
        private string $whyInvalid = '',
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        $message = (
            'The file is invalid'
            . ('' === $this->getWhyInvalid() ? '' : ": {$this->getWhyInvalid()}")
        );

        parent::__construct($message, $code, $previous);
    }

    public function getWhyInvalid(): string
    {
        return $this->whyInvalid;
    }
}
