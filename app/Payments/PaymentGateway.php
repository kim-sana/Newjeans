<?php

declare(strict_types=1);

namespace App\Payments;

interface PaymentGateway
{
    public function provider(): string;

    public function simulate(string $outcome): PaymentResult;
}
