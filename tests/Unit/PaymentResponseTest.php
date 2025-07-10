<?php

namespace PaymentManager\Tests\Unit;

use PaymentManager\Responses\PaymentResponse;
use PHPUnit\Framework\TestCase;

class PaymentResponseTest extends TestCase
{
    public function test_successful_payment_response()
    {
        $data = [
            'transaction_id' => 'TEST_123',
            'status' => 'completed',
            'amount' => 1000.00,
            'currency' => 'XOF',
            'payment_url' => 'https://example.com/pay',
            'metadata' => ['key' => 'value'],
        ];

        $response = PaymentResponse::success('cinetpay', $data);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('TEST_123', $response->getTransactionId());
        $this->assertEquals('completed', $response->getStatus());
        $this->assertEquals(1000.00, $response->getAmount());
        $this->assertEquals('XOF', $response->getCurrency());
        $this->assertEquals('cinetpay', $response->getGatewayName());
        $this->assertEquals('https://example.com/pay', $response->getPaymentUrl());
        $this->assertEquals(['key' => 'value'], $response->getMetadata());
        $this->assertNull($response->getErrorMessage());
        $this->assertNull($response->getErrorCode());
    }

    public function test_failed_payment_response()
    {
        $response = PaymentResponse::failure(
            'bizao',
            'Payment failed due to insufficient funds',
            'INSUFFICIENT_FUNDS',
            ['amount' => 500.00]
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('bizao', $response->getGatewayName());
        $this->assertEquals('Payment failed due to insufficient funds', $response->getErrorMessage());
        $this->assertEquals('INSUFFICIENT_FUNDS', $response->getErrorCode());
        $this->assertEquals(500.00, $response->getAmount());
    }

    public function test_response_to_array()
    {
        $data = [
            'transaction_id' => 'TEST_456',
            'status' => 'pending',
            'amount' => 2500.00,
            'currency' => 'XOF',
        ];

        $response = PaymentResponse::success('winipayer', $data);
        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['successful']);
        $this->assertEquals('TEST_456', $array['transaction_id']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals(2500.00, $array['amount']);
        $this->assertEquals('XOF', $array['currency']);
        $this->assertEquals('winipayer', $array['gateway_name']);
    }

    public function test_response_with_default_values()
    {
        $response = new PaymentResponse(true, 'cinetpay', []);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('cinetpay', $response->getGatewayName());
        $this->assertEquals('unknown', $response->getStatus());
        $this->assertEquals(0.0, $response->getAmount());
        $this->assertEquals('XOF', $response->getCurrency());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getPaymentUrl());
        $this->assertEquals([], $response->getMetadata());
    }
}
