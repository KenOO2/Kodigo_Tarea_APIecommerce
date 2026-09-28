<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/products",
     *     tags={"Productos"},
     *     summary="Listado público de productos activos",
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Parameter(name="search", in="query", description="Buscar por nombre", @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de productos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product"))
     *         )
     *     )
     * )
     */
    public function index(\Illuminate\Http\Request $request)
    {
        $query = Product::query()->active();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->orderByDesc('id')->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     tags={"Productos"},
     *     summary="Ver el detalle de un producto",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle del producto"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/products",
     *     tags={"Productos"},
     *     summary="Crear un producto (requiere rol admin)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","stock","sku"},
     *             @OA\Property(property="name", type="string", example="Camiseta básica"),
     *             @OA\Property(property="description", type="string", example="Camiseta 100% algodón"),
     *             @OA\Property(property="price", type="number", format="float", example=19.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="sku", type="string", example="CAM-001"),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/img.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos de administrador"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $product,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     tags={"Productos"},
     *     summary="Actualizar un producto (requiere rol admin)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number", format="float"),
     *             @OA\Property(property="stock", type="integer"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="image_url", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos de administrador"),
     *     @OA\Response(response=404, description="Producto no encontrado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $product->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     tags={"Productos"},
     *     summary="Eliminar un producto (requiere rol admin)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Producto eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos de administrador"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}
