<?php

declare(strict_types=1);

namespace HPlus\Validate\Exception;

use Throwable;

/**
 * 验证异常
 */
class ValidateException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}