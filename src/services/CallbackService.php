<?php

namespace QD\altapay\services;

use Craft;
use Exception;
use QD\altapay\config\Utils;

class CallbackService
{
  //TODO: Update this to be a proper response model
  public static function response()
  {
    $request = Craft::$app->getRequest()->getBodyParams();

    $xml = simplexml_load_string($request['xml'], 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) throw new Exception('Failed to parse XML response', 1);
    unset($request['xml']);

    $meta = json_decode(json_encode($xml), true);
    unset($meta['@attributes']);

    $response = Utils::objectify($request);
    $response->meta = Utils::objectify($meta);

    return $response;
  }
}
