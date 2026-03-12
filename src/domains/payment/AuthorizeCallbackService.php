<?php

namespace QD\altapay\domains\payment;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\records\Transaction as RecordsTransaction;
use Exception;
use QD\altapay\config\Data;
use QD\altapay\services\TransactionService;
use Throwable;

class AuthorizeCallbackService
{
  //* Authorize
  public static function synchronous(string $callback, mixed $response)
  {
    $parent = TransactionService::getTransactionByHash($response->transaction_info->transaction);
    if (!$parent) throw new Exception("Parent transaction not found", 1);

    $order = Order::findOne($response->transaction_info->order);
    if (!$order) throw new Exception("Order not found", 1);

    $redirect = self::_addQueryParam($order->returnUrl, 'status', $callback);

    if ($order->getIsPaid()) return self::_redirect($redirect);
    if (TransactionService::isTransactionSuccessful($parent)) return self::_redirect($redirect);

    try {
      switch ($callback) {
        case Data::CALLBACK_OK:
          TransactionService::authorize(RecordsTransaction::STATUS_SUCCESS, $response, $response->status, '200');
          break;
        case Data::CALLBACK_FAIL:
          TransactionService::authorize(RecordsTransaction::STATUS_FAILED, $response, $response->error_message ?: $response->status, '500');
          break;
        case Data::CALLBACK_OPEN:
          TransactionService::authorize(RecordsTransaction::STATUS_PROCESSING, $response, $response->status, '102');
          break;
      }
    } catch (Throwable $th) {
      throw $th;
    }

    return self::_redirect($redirect);
  }

  public static function notification(mixed $response)
  {
    $result = $response?->meta?->Body?->Result ?? null;
    if (!$result) throw new Exception("Invalid response: Missing result", 1);

    $parent = TransactionService::getTransactionByHash($response->transaction_info->transaction);
    if (!$parent) throw new Exception("Parent transaction not found", 1);

    if (TransactionService::isTransactionSuccessful($parent)) return;

    $transactionStatus = TransactionService::status($result);
    TransactionService::authorize($transactionStatus, $response, $response->status, self::_code($transactionStatus));
  }

  private static function _code($status): string
  {
    switch ($status) {
      case RecordsTransaction::STATUS_SUCCESS:
        return '200';
      case RecordsTransaction::STATUS_FAILED:
        return '500';
      case RecordsTransaction::STATUS_PROCESSING:
        return '102';
      default:
        return '0';
    }
  }

  private static function _redirect($url)
  {
    return Craft::$app->getResponse()->redirect($url);
  }

  private static function _addQueryParam(string $url, string $key, string $value): string
  {
    $queryParams = [$key => $value];
    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . http_build_query($queryParams);
  }
}
