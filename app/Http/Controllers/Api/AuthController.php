<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Autenticación"},
     *     summary="Registrar un nuevo cliente",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secreta123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secreta123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuario registrado correctamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/User"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => 'customer',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente.',
            'data' => $this->respondWithToken($token, $user),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Autenticación"},
     *     summary="Iniciar sesión y obtener token JWT",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secreta123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login exitoso"),
     *     @OA\Response(response=401, description="Credenciales inválidas"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => $this->respondWithToken($token, auth('api')->user()),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Autenticación"},
     *     summary="Cerrar sesión (invalida el token actual)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Sesión cerrada correctamente"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Autenticación"},
     *     summary="Refrescar el token JWT",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token refrescado"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function refresh()
    {
        try {
            $token = auth('api')->refresh();
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No fue posible refrescar el token.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $this->respondWithToken($token, auth('api')->user()),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Autenticación"},
     *     summary="Obtener datos del usuario autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Datos del usuario",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => auth('api')->user(),
        ]);
    }

    protected function respondWithToken(string $token, User $user): array
    {
        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
