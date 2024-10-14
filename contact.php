<?php

require "vendor/autoload.php";

use AcMarche\Notion\Lib\Mailer;
use AcMarche\Notion\Lib\ResponseUtil;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

$request = Request::createFromGlobals();

$data = json_decode($request->getContent(false));
$contact = $data->contact;

if (!$contact->name && $contact->email && !$contact->message) {
    return ResponseUtil::sendSuccessResponse(['error' => 'xx', 'data' => $contact], "contaat");
}

(new Dotenv())->load(__DIR__.'/.env');
try {
    $mailer = new Mailer();
    $mailer->sendContact($contact);

    return ResponseUtil::sendSuccessResponse($data, 'Get successfully page');
} catch (\Exception|TransportExceptionInterface $e) {
    Mailer::sendError($e->getMessage());

    return ResponseUtil::sendErrorResponse($e->getMessage());
}