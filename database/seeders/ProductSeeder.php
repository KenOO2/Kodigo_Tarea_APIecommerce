<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Camiseta básica algodón', 'description' => 'Camiseta 100% algodón, disponible en varios colores.', 'price' => 14.99, 'stock' => 100, 'sku' => 'CAM-001'],
            ['name' => 'Pantalón jeans clásico', 'description' => 'Jeans corte recto, alta durabilidad.', 'price' => 39.90, 'stock' => 60, 'sku' => 'PAN-002'],
            ['name' => 'Zapatillas running Pro', 'description' => 'Zapatillas ligeras para correr, suela de goma antideslizante.', 'price' => 79.50, 'stock' => 40, 'sku' => 'ZAP-003'],
            ['name' => 'Mochila urbana 20L', 'description' => 'Mochila resistente al agua con compartimento para laptop.', 'price' => 34.00, 'stock' => 75, 'sku' => 'MOC-004'],
            ['name' => 'Reloj digital deportivo', 'description' => 'Resistente al agua, cronómetro y monitor de ritmo cardiaco.', 'price' => 59.99, 'stock' => 30, 'sku' => 'REL-005'],
            ['name' => 'Audífonos inalámbricos', 'description' => 'Bluetooth 5.0, cancelación de ruido, 20h de batería.', 'price' => 45.00, 'stock' => 90, 'sku' => 'AUD-006'],
            ['name' => 'Gorra ajustable', 'description' => 'Gorra de algodón con cierre ajustable trasero.', 'price' => 12.50, 'stock' => 150, 'sku' => 'GOR-007'],
            ['name' => 'Chaqueta impermeable', 'description' => 'Chaqueta ligera resistente al viento y la lluvia.', 'price' => 65.00, 'stock' => 25, 'sku' => 'CHA-008'],
            ['name' => 'Botella térmica 750ml', 'description' => 'Acero inoxidable, mantiene temperatura hasta 12 horas.', 'price' => 18.75, 'stock' => 120, 'sku' => 'BOT-009'],
            ['name' => 'Lentes de sol polarizados', 'description' => 'Protección UV400, marco resistente.', 'price' => 22.00, 'stock' => 80, 'sku' => 'LEN-010'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                array_merge($product, ['is_active' => true])
            );
        }

        // Algunos productos adicionales generados con factory
        Product::factory()->count(10)->create();
    }
}
