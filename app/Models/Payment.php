<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Pago",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=1),
 *     @OA\Property(property="stripe_payment_intent_id", type="string", example="pi_3P..."),
 *     @OA\Property(property="stripe_client_secret", type="string", example="pi_3P..._secret_..."),
 *     @OA\Property(property="amount", type="number", format="float", example=59.97),
 *     @OA\Property(property="currency", type="string", example="usd"),
 *     @OA\Property(property="status", type="string", example="succeeded", enum={"requires_payment_method","requires_confirmation","requires_action","processing","succeeded","canceled","failed"}),
 *     @OA\Property(property="payment_method", type="string", nullable=true, example="card"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'amount',
        'currency',
        'status',
        'payment_method',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
