<?php

namespace App\Helpers;

class ResponseHelper
{
    /**
     * Crear respuesta JSON estándar de éxito
     */
    public static function success(string $message, array $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'type' => 'success',
            ...$data
        ], $status);
    }

    /**
     * Crear respuesta JSON estándar de error
     */
    public static function error(string $message, array $data = [], int $status = 422): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'type' => 'error',
            ...$data
        ], $status);
    }

    /**
     * Crear respuesta JSON de advertencia
     */
    public static function warning(string $message, array $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'type' => 'warning',
            ...$data
        ], $status);
    }
}

