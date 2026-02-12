<?php

namespace QD\altapay\queue;

use craft\queue\BaseJob;
use QD\altapay\config\Data;
use QD\altapay\domains\payment\AuthorizeCallbackService;
use QD\altapay\domains\payment\CaptureCallbackService;

class NotificationQueue extends BaseJob
{
  public mixed $response;

  public function execute($queue): void
  {
    if ($this->response->type === Data::PAYMENT_CALLBACK_TYPE_SUBSCRIPTION_PAYMENT) {
      CaptureCallbackService::notification($this->response);
      return;
    }

    AuthorizeCallbackService::notification($this->response);
  }


  public function getTtr(): int
  {
    return 300;
  }

  public function canRetry($attempt, $error)
  {
    return false;
  }

  protected function defaultDescription(): string
  {
    return 'AltaPay: Notification';
  }
}
