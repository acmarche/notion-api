<?php

namespace AcMarche\Notion\Lib;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseUtil
{
    public static function sendErrorResponse(string $message): JsonResponse
    {
        $response = [
            'status' => 'error',
            'statusMessage' => $message,
            'message' => $message,
            'code' => Response::HTTP_BAD_REQUEST,
            'statusCode' => Response::HTTP_BAD_REQUEST,
        ];
        $jsonResponse = new JsonResponse($response);
        $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST, $message);

        return $jsonResponse->send();
    }

    public static function sendInternalErrorResponse(string $message): JsonResponse
    {
        $response = [
            'status' => 'error',
            'statusMessage' => $message,
            'message' => $message,
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
        $jsonResponse = new JsonResponse($response);
        $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST, $message);

        return $jsonResponse->send();
    }

    public static function sendSuccessResponse(mixed $data, string $message): JsonResponse
    {
        $jsonResponse = new JsonResponse($data);
        $jsonResponse->setStatusCode(Response::HTTP_OK, $message);

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
        $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND, $message);

        return $jsonResponse->send();
    }

}