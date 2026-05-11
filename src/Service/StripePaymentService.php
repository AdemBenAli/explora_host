<?php

namespace App\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePaymentService
{
    private function getSecretKey(): string
    {
        $key = (string) ($_ENV['STRIPE_SECRET_KEY'] ?? $_SERVER['STRIPE_SECRET_KEY'] ?? '');
        return trim($key);
    }

    public function chargeCard(
        float $amount,
        string $currency,
        string $cardBrand,
        string $cardNumber,
        string $cardholderName,
        string $description,
        array $metadata = []
    ): array {
        $secretKey = $this->getSecretKey();
        if ($secretKey === '') {
            throw new \RuntimeException('Stripe is not configured. Missing STRIPE_SECRET_KEY.');
        }

        $amountInCents = (int) round($amount * 100);
        if ($amountInCents <= 0) {
            throw new \RuntimeException('Payment amount must be greater than 0.');
        }

        $stripe = new StripeClient($secretKey);
        $paymentMethodId = $this->resolveTestPaymentMethodId($cardBrand);
        if ($paymentMethodId === null) {
            throw new \RuntimeException('Unsupported card brand for Stripe test flow. Supported: Visa, Mastercard, Amex, Discover.');
        }

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'confirm' => true,
                'description' => $description,
                'payment_method' => $paymentMethodId,
                'payment_method_types' => ['card'],
                'metadata' => array_map(static fn($value): string => (string) $value, $metadata),
            ]);
        } catch (ApiErrorException $exception) {
            throw new \RuntimeException('Stripe error: ' . $exception->getMessage(), 0, $exception);
        }

        return [
            'id' => (string) ($paymentIntent->id ?? ''),
            'status' => (string) ($paymentIntent->status ?? ''),
            'payment_method' => (string) ($paymentIntent->payment_method ?? $paymentMethodId),
        ];
    }

    private function resolveTestPaymentMethodId(string $cardBrand): ?string
    {
        $normalized = strtolower(trim($cardBrand));

        return match ($normalized) {
            'visa' => 'pm_card_visa',
            'mastercard' => 'pm_card_mastercard',
            'amex' => 'pm_card_amex',
            'discover' => 'pm_card_discover',
            default => null,
        };
    }
}
