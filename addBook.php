<?php

use AcMarche\Notion\Lib\Grr;
use AcMarche\Notion\Lib\ResponseUtil;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require "vendor/autoload.php";

(new Dotenv())->load(__DIR__.'/.env');
$request = Request::createFromGlobals();

$grr = new Grr();
try {
    $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
    //{"person":{"name":"jf2","email":"jf@marche","phone":"084","street":"bois"}}
    $result = $grr->treatment($data);
    return ResponseUtil::sendSuccessResponse($result, 'Get successfully page');
} catch (Exception $e) {
    return ResponseUtil::sendErrorResponse($e->getMessage());
}