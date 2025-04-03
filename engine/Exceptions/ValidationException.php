<?php

declare(strict_types=1);

namespace Forge\Exceptions;

final class ValidationException extends BaseException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
