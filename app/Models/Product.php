<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Producto",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Camiseta básica"),
 *     @OA\Property(property="description", type="string", example="Camiseta 100% algodón"),
 *     @OA\Property(property="price", type="number", format="float", example=19.99),
 *     @OA\Property(property="stock", type="integer", example=50),
 *     @OA\Property(property="sku", type="string", example="CAM-001"),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/img.jpg"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sku',
        'image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
