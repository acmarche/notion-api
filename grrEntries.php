<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\Grr;
use AcMarche\Notion\Lib\Mailer;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
$roomId = $request->query->getInt('id');

(new Dotenv())->load(__DIR__.'/.env');

if ($roomId) {
    $grr = new Grr();
    try {
        $grr->connect();
        $response = new JsonResponse($grr->findByRoomId($roomId));
    } catch (Exception $e) {
        Mailer::sendError("grr esquare findByRoomId ".$e->getMessage());
        $response = new JsonResponse(['error' => $e->getMessage()], 500);
    }
} else {
    Mailer::sendError("grr esquare No room id");
    $response = new JsonResponse(['error' => 'No room id'], 500);
}
$response->send();
die();