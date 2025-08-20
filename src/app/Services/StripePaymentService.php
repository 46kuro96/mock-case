<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Exception;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * カード決済用のPaymentIntentを作成
     */
    public function createCardPaymentIntent($amount, $currency = 'jpy', $metadata = [])
    {
        try {
            return PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
                'confirmation_method' => 'manual',
                'confirm' => false,
            ]);
        } catch (Exception $e) {
            throw new Exception('カード決済の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * コンビニ決済用のPaymentIntentを作成
     */
    public function createKonbiniPaymentIntent($amount, $currency = 'jpy', $metadata = [])
    {
        try {
            return PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['konbini'],
                'metadata' => $metadata,
                'confirmation_method' => 'manual',
                'confirm' => false,
            ]);
        } catch (Exception $e) {
            throw new Exception('コンビニ決済の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * PaymentIntentを確認
     */
    public function confirmPaymentIntent($paymentIntentId, $paymentMethodId = null)
    {
        try {
            $params = [];
            
            if ($paymentMethodId) {
                $params['payment_method'] = $paymentMethodId;
            }

            return PaymentIntent::retrieve($paymentIntentId)->confirm($params);
        } catch (Exception $e) {
            throw new Exception('決済の確認に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * コンビニ決済メソッドを作成
     */
    public function createKonbiniPaymentMethod($customerEmail, $customerName)
    {
        try {
            return PaymentMethod::create([
                'type' => 'konbini',
                'konbini' => [],
                'billing_details' => [
                    'email' => $customerEmail,
                    'name' => $customerName,
                ],
            ]);
        } catch (Exception $e) {
            throw new Exception('コンビニ決済メソッドの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * PaymentIntentのステータスを取得
     */
    public function getPaymentIntentStatus($paymentIntentId)
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (Exception $e) {
            throw new Exception('決済情報の取得に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * PaymentIntentをキャンセル
     */
    public function cancelPaymentIntent($paymentIntentId)
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId)->cancel();
        } catch (Exception $e) {
            throw new Exception('決済のキャンセルに失敗しました: ' . $e->getMessage());
        }
    }
}