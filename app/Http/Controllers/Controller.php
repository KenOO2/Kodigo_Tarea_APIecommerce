<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="API de E-commerce Segura",
 *     version="1.0.0",
 *     description="API RESTful para la gestión de un e-commerce: clientes, catálogo de productos, órdenes de compra y procesamiento de pagos con Stripe. Autenticación mediante JWT.",
 *     @OA\Contact(email="soporte@example.com", name="Soporte Técnico")
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Servidor de la API"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingresar el token JWT obtenido en /api/auth/login, precedido de la palabra Bearer. Ejemplo: Bearer eyJ0eXAiOiJKV1Qi..."
 * )
 *
 * @OA\Tag(name="Autenticación", description="Registro, login y gestión de sesión de usuarios")
 * @OA\Tag(name="Productos", description="Catálogo de productos (CRUD)")
 * @OA\Tag(name="Órdenes", description="Creación y consulta de órdenes de compra")
 * @OA\Tag(name="Pagos", description="Procesamiento de pagos con Stripe")
 */
abstract class Controller
{
    //
}
