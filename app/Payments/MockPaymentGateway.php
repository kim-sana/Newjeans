<?php

declare(strict_types=1);

namespace App\Payments;

use InvalidArgumentException;

final class MockPaymentGateway implements PaymentGateway
{
    public function provider(): string
    {
        return 'mock';
    }

    public function simulate(string $outcome): PaymentResult
    {
        if ($outcome === 'success') {
            $reference = 'MOCK-' . strtoupper(bin2hex(random_bytes(4)));

            return new PaymentResult(true, 'Simulated payment succeeded.', $reference);
        }

        if ($outcome === 'failure') {
            return new PaymentResult(false, 'Simulated payment failed. Please try again.');
        }

        throw new InvalidArgumentException('Unsupported payment outcome: ' . $outcome);
    }
}
