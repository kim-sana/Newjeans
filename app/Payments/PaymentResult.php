<?php

declare(strict_types=1);

namespace App\Payments;

final class PaymentResult
{
    private bool $successful;

    private string $message;

    private ?string $reference;

    public function __construct(bool $successful, string $message, ?string $reference = null)
    {
        $this->successful = $successful;
        $this->message = $message;
        $this->reference = $reference;
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }
}
