<?php

namespace AcMarche\Notion\Lib;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseUtil
{
    public static function sendErrorResponse(string $message): JsonResponse
    {
        $response = [
            'error' => [
                'status' => 'error',
                'data' => null,
                'message' => $message,
                "target" => "query",
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ],
        ];
        $jsonResponse = new JsonResponse($response);
        $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);

        return $jsonResponse->send();
    }

    public static function sendSuccessResponse(mixed $data, string $message): JsonResponse
    {
        $jsonResponse = new JsonResponse($data);
        $jsonResponse->setStatusCode(Response::HTTP_OK);

        return $jsonResponse->send();
    }

    public static function send404Response(string $message): JsonResponse
    {
        $response = [
            'error' => [
                'status' => 'error',
                'data' => null,
                'message' => $message,
                'code' => Response::HTTP_NOT_FOUND,
            ],
        ];
        $jsonResponse = new JsonResponse($response);
        $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);

        return $jsonResponse->send();
    }

}