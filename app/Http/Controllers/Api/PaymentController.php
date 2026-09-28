<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\CreatePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(protected StripeService $stripe)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/payments/create-intent",
     *     tags={"Pagos"},
     *     summary="Crear un PaymentIntent de Stripe para una orden",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="PaymentIntent creado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Payment")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="La orden no pertenece al usuario"),
     *     @OA\Response(response=422, description="La orden ya fue pagada o datos inválidos")
     * )
     */
    public function createIntent(CreatePaymentRequest $request)
    {
        $user = auth('api')->user();
        $order = Order::findOrFail($request->validated('order_id'));

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para pagar esta orden.',
            ], 403);
        }

        if ($order->status === Order::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden ya fue pagada.',
            ], 422);
        }

        try {
            $intent = $this->stripe->createPaymentIntent(
                amount: (float) $order->total,
                currency: $order->currency,
                metadata: ['order_id' => $order->id, 'order_number' => $order->order_number],
            );
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe error creando PaymentIntent', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible iniciar el pago con Stripe.',
            ], 502);
        }

        $payment = Payment::create([
            'order_id' => $order->id,
            'stripe_payment_intent_id' => $intent->id,
            'stripe_client_secret' => $intent->client_secret,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => $intent->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PaymentIntent creado correctamente.',
            'data' => $payment,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/payments/confirm",
     *     tags={"Pagos"},
     *     summary="Confirmar el estado de un pago con Stripe",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_intent_id"},
     *             @OA\Property(property="payment_intent_id", type="string", example="pi_3P...")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Estado del pago actualizado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Pago no encontrado")
     * )
     */
    public function confirm(ConfirmPaymentRequest $request)
    {
        $payment = Payment::where('stripe_payment_intent_id', $request->validated('payment_intent_id'))
            ->firstOrFail();

        $user = auth('api')->user();
        if ($payment->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para consultar este pago.',
            ], 403);
        }

        try {
            $intent = $this->stripe->retrievePaymentIntent($payment->stripe_payment_intent_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No fue posible consultar el estado del pago en Stripe.',
            ], 502);
        }

        $payment->update([
            'status' => $intent->status,
            'payment_method' => $intent->payment_method,
            'raw_response' => $intent->toArray(),
        ]);

        if ($intent->status === 'succeeded') {
            $payment->order->update(['status' => Order::STATUS_PAID]);
        }

        return response()->json([
            'success' => true,
            'data' => $payment->fresh(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/payments/webhook",
     *     tags={"Pagos"},
     *     summary="Webhook de Stripe para eventos de pago (uso interno de Stripe)",
     *     @OA\Response(response=200, description="Evento procesado"),
     *     @OA\Response(response=400, description="Firma de webhook inválida")
     * )
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = $this->stripe->constructWebhookEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            Log::warning('Firma de webhook de Stripe inválida', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Firma inválida.'], 400);
        }

        if (in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'])) {
            $intent = $event->data->object;

            $payment = Payment::where('stripe_payment_intent_id', $intent->id)->first();

            if ($payment) {
                $payment->update([
                    'status' => $intent->status,
                    'raw_response' => $intent->toArray(),
                ]);

                if ($event->type === 'payment_intent.succeeded') {
                    $payment->order->update(['status' => Order::STATUS_PAID]);
                } else {
                    $payment->order->update(['status' => Order::STATUS_FAILED]);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
