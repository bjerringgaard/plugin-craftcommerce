<?php

namespace QD\altapay\controllers;

use craft\web\Controller;
use QD\altapay\config\Data;
use QD\altapay\domains\payment\CaptureCallbackService;
use Exception;
use QD\altapay\services\CallbackService;
use Throwable;

class RecurringCallbackController extends Controller
{
  public $enableCsrfValidation = false;
  protected array|bool|int $allowAnonymous = [
    'ok' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    'fail' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
  ];


  // callback_ok
  public function actionOk()
  {
    try {
      $response = CallbackService::response();
      $this->_validate($response);
      CaptureCallbackService::callback(Data::CALLBACK_OK, $response);
    } catch (\Throwable $th) {
      throw new Exception($th->getMessage(), 1);
    }
  }

  // callback_fail
  public function actionFail()
  {
    try {
      $response = CallbackService::response();
      $this->_validate($response);
      CaptureCallbackService::callback(Data::CALLBACK_FAIL, $response);
    } catch (\Throwable $th) {
      throw new Exception($th->getMessage(), 1);
    }
  }

  private function _validate($response)
  {
    if (!$response->data->CaptureAmount) {
      throw new Exception('Invalid response: Missing capture arguments', 1);
    }

    if (!$response->data->Transactions->Transaction[0]->PaymentId) {
      throw new Exception('Invalid response: Missing payment reference', 1);
    }
  }
}
