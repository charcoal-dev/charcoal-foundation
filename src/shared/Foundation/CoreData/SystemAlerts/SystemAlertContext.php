<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\SystemAlerts;

use Charcoal\App\Kernel\Errors;

/**
 * Class SystemAlertContext
 * @package App\Shared\Foundation\CoreData\SystemAlerts
 */
class SystemAlertContext
{
    public readonly ?array $exception;
    private array $data = [];

    public function __construct(?\Throwable $exception)
    {
        $this->exception = $exception ? Errors::Exception2Array($exception) : null;
    }

    public function set(string $key, string|int|null|bool|float $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }
}