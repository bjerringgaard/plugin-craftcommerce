<?php

namespace QD\altapay\domains\payment;

use craft\commerce\records\Transaction as RecordsTransaction;
use Exception;
use QD\altapay\Altapay;
use QD\altapay\config\Data;
use QD\altapay\events\RecurringChargeEvent;
use QD\altapay\services\TransactionService;

class CaptureCallbackService
{
  //* Authorize
  public static function callback(string $callback, mixed $response)
  {
    $result = $response->data->Result ?? null;
    if (!$result) throw new Exception("Invalid response: Missing result", 1);

    //TODO: [0] might not work here, as the response array is retuned randomly
    $action = $response->data->Transactions->Transaction[0] ?? null;
    if (!$action) throw new Exception("Invalid response: Missing transaction data", 1);

    $parent = TransactionService::getTransactionByReference($action->PaymentId);
    if (!$parent) throw new Exception("Transaction not found", 1);

    $order = $parent->getOrder();
    if (!$order) throw new Exception("Order not found", 1);

    $responseStatus = self::_status($result);
    $transactionStatus = TransactionService::status($result);
    $child = TransactionService::create($order, $parent, $parent->reference, RecordsTransaction::TYPE_CAPTURE, $transactionStatus, $response);

    // EVENT
    $plugin = Altapay::getInstance();
    if ($plugin->hasEventHandlers(Altapay::EVENT_RECURRING_CHARGE)) {
      $event = new RecurringChargeEvent([
        'order' => $order,
        'transaction' => $child,
        'status' => $responseStatus
      ]);
      $plugin->trigger(Altapay::EVENT_RECURRING_CHARGE, $event);
    }
  }

  public static function notification(mixed $response)
  {
    $result = $response->meta->Body->Result ?? null;
    if (!$result) throw new Exception("Invalid response: Missing result", 1);

    $orderId = $response->transaction_info->order ?? null;
    if (!$orderId) throw new Exception("Invalid response: Missing order ID", 1);

    $parent = TransactionService::getLatestProcessingTransaction($orderId, RecordsTransaction::TYPE_CAPTURE);
    if (!$parent) throw new Exception("Transaction not found", 1);
    if (TransactionService::isTransactionSuccessful($parent)) return;

    $order = $parent->getOrder();
    if (!$order) throw new Exception("Order not found", 1);

    $responseStatus = self::_status($result);
    $transactionStatus = TransactionService::status($result);
    $child = TransactionService::create($order, $parent, $parent->reference, RecordsTransaction::TYPE_CAPTURE, $transactionStatus, $response);

    // EVENT
    $plugin = Altapay::getInstance();
    if ($plugin->hasEventHandlers(Altapay::EVENT_RECURRING_CHARGE)) {
      $event = new RecurringChargeEvent([
        'order' => $order,
        'transaction' => $child,
        'status' => $responseStatus
      ]);
      $plugin->trigger(Altapay::EVENT_RECURRING_CHARGE, $event);
    }
  }

  private static function _status(?string $result): string
  {
    switch ($result) {
      case Data::RESPONSE_SUCCESS:
        return RecordsTransaction::STATUS_SUCCESS;

      case Data::RESPONSE_ERROR:
      case Data::RESPONSE_FAIL:
      case Data::RESPONSE_FAILED:
        return RecordsTransaction::STATUS_FAILED;

      case Data::RESPONSE_OPEN:
        return RecordsTransaction::STATUS_PROCESSING;

      case Data::RESPONSE_CANCELLED:
        return Data::TRANSACTION_STATUS_CANCELLED;

      case Data::RESPONSE_PARTIAL_SUCCESS:
        throw new Exception("Partial Success not implemented", 1);

      default:
        throw new Exception("Unknown response status: $result", 1);
    }
  }
}
