<?php

namespace QD\altapay\controllers;

use craft\web\Controller;
use QD\altapay\services\CallbackService;
use QD\altapay\services\QueueService;

class NotificationCallbackController extends Controller
{
  public $enableCsrfValidation = false;
  protected array|bool|int $allowAnonymous = [
    'notification' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
  ];

  // callback_notification
  //? This is a follow up system callback, used to update the order status, if previous state was "open", or to verify fail/ok status
  //! This callback has a timeout of 5 secounds, therefore the heavy handling is done in a queue job
  public function actionNotification(): void
  {
    $response = CallbackService::response();
    QueueService::notification($response);
  }
}
