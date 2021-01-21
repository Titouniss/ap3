<?php

namespace App\Traits;

trait ReturnsJsonResponse
{
    protected static $response_codes = [
        'success' => 200,
        'error_request' => 400,
        'error_unauthorized' => 403,
        'error_not_found' => 404,
        'error_server' => 500
    ];

    /**
     * Returns an item not found response.
     */
    protected function notFoundResponse()
    {
        return $this->errorResponse("Ressource introuvable.", static::$response_codes['error_not_found']);
    }

    /**
     * Returns a successful response.
     */
    protected function successResponse($payload, string $message = 'Chargement terminé avec succès.', array $extra = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'payload' => $payload
        ];
        if ($extra != null) {
            $response = array_merge($response, $extra);
        }
        return response()->json($response, static::$response_codes['success']);
    }

    /**
     * Returns a successful response.
     */
    protected function errorResponse(string $message, int $status_code = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        return response()->json($response, $status_code ?? static::$response_codes['error_request']);
    }
}
