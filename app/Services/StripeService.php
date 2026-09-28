<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\PaymentIntent;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Crea un PaymentIntent en Stripe para el monto indicado (en la unidad menor de la moneda, ej. centavos).
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = []): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($paymentIntentId);
    }

    public function confirmPaymentIntent(string $paymentIntentId, ?string $paymentMethod = null): PaymentIntent
    {
        $params = [];

        if ($paymentMethod) {
            $params['payment_method'] = $paymentMethod;
        }

        return $this->client->paymentIntents->confirm($paymentIntentId, $params);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret)
    {
        return \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
    }
}
