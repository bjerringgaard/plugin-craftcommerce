<?php

namespace QD\altapay\controllers;

use Craft;
use craft\web\Controller;
use QD\altapay\config\Data;
use QD\altapay\config\Utils;
use QD\altapay\domains\payment\AuthorizeCallbackService;

use Exception;
use QD\altapay\services\CallbackService;
use Throwable;

class PaymentCallbackController extends Controller
{
  public $enableCsrfValidation = false;
  protected array|bool|int $allowAnonymous = [
    'ok' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    'fail' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    'open' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
  ];


  // callback_ok
  //? This callback is called when the order is successfully authorized
  public function actionOk()
  {
    try {
      $response = CallbackService::response();
      $this->_validate($response);
      return AuthorizeCallbackService::synchronous(Data::CALLBACK_OK, $response);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), 1);
    }
  }

  // callback_fail
  //? This callback is called when the autorization failed, and the payment was not completed
  public function actionFail()
  {
    try {
      $response = CallbackService::response();
      $this->_validate($response);
      return AuthorizeCallbackService::synchronous(Data::CALLBACK_FAIL, $response);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), 1);
    }
  }

  // callback_open
  //? This callback is called when the payment is not yet processed (most likely awaiting 3rd party verification)
  public function actionOpen()
  {
    try {
      $response = CallbackService::response();
      $this->_validate($response);
      return AuthorizeCallbackService::synchronous(Data::CALLBACK_OPEN, $response);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), 1);
    }
  }

  private function _validate($response)
  {
    if (!$response->transaction_info->order) {
      throw new Exception('Invalid response: Missing order reference', 1);
    }
  }
}
