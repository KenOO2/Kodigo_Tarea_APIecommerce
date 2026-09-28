<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"Órdenes"},
     *     summary="Crear una orden de compra",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items"},
     *             @OA\Property(property="items", type="array", @OA\Items(
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=2)
     *             )),
     *             @OA\Property(property="currency", type="string", example="usd")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Orden creada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos o stock insuficiente")
     * )
     */
    public function store(StoreOrderRequest $request)
    {
        $user = auth('api')->user();
        $items = $request->validated('items');

        try {
            $order = DB::transaction(function () use ($items, $user, $request) {
                $total = 0;
                $productsLocked = [];

                foreach ($items as $item) {
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                    if (! $product->is_active) {
                        throw new \RuntimeException("El producto '{$product->name}' no está disponible.");
                    }

                    if (! $product->hasStock((int) $item['quantity'])) {
                        throw new \RuntimeException("Stock insuficiente para el producto '{$product->name}'.");
                    }

                    $productsLocked[] = ['product' => $product, 'quantity' => (int) $item['quantity']];
                    $total += $product->price * $item['quantity'];
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                    'status' => Order::STATUS_PENDING,
                    'total' => $total,
                    'currency' => $request->validated('currency') ?? 'usd',
                ]);

                foreach ($productsLocked as $entry) {
                    $product = $entry['product'];
                    $quantity = $entry['quantity'];

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'subtotal' => $product->price * $quantity,
                    ]);

                    $product->decrement('stock', $quantity);
                }

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Orden creada correctamente.',
            'data' => $order->load('items.product'),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     tags={"Órdenes"},
     *     summary="Historial de compras del usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Listado de órdenes del usuario"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index()
    {
        $orders = auth('api')->user()
            ->orders()
            ->with(['items.product', 'payments'])
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     tags={"Órdenes"},
     *     summary="Ver el detalle de una orden propia",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de la orden"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="La orden no pertenece al usuario"),
     *     @OA\Response(response=404, description="Orden no encontrada")
     * )
     */
    public function show(Order $order)
    {
        $user = auth('api')->user();

        if ($order->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para ver esta orden.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load(['items.product', 'payments']),
        ]);
    }
}
