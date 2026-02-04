<?php

namespace QD\altapay\queue;

use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use Exception;
use QD\altapay\services\OrderService;
use QD\altapay\services\TransactionService;
use yii\queue\RetryableJobInterface;
use craft\commerce\records\Transaction as RecordsTransaction;

class CaptureQueue extends BaseJob implements RetryableJobInterface
{
  public $id;

  public function execute($queue): void
  {
    $order = Order::find()->id($this->id)->one();
    if (!$order) throw new Exception('Order not found');

    $gateway = $order->getGateway();
    if (!$gateway) throw new Exception('Gateway not found');

    if ($order->isPaid) {
      OrderService::setAfterCaptureStatus($order);
      return;
    }

    $transaction = TransactionService::getSuccessfulTransaction($this->id);
    if (!$transaction) throw new Exception('No valid transaction found for order');

    $child = TransactionService::captureTransaction($transaction);
    if (!$child) throw new Exception('Capture failed');

    if ($child->status === RecordsTransaction::STATUS_SUCCESS) OrderService::setAfterCaptureStatus($order);
  }


  public function getTtr(): int
  {
    return 300;
  }

  public function canRetry($attempt, $error)
  {
    return ($attempt < 5);
  }

  protected function defaultDescription(): string
  {
    return 'Altapay: Capturing';
  }
}
