<?php

namespace PaymentManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PaymentManager\Contracts\PaymentManagerInterface;

class WebhookController extends Controller
{
    /**
     * @var PaymentManagerInterface
     */
    protected PaymentManagerInterface $paymentManager;

    /**
     * Create a new controller instance
     *
     * @param PaymentManagerInterface $paymentManager
     */
    public function __construct(PaymentManagerInterface $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Handle Cinetpay webhook
     *
     * @param Request $request
     * @return Response
     */
    public function cinetpay(Request $request): Response
    {
        try {
            $response = $this->paymentManager->processWebhook($request->all(), 'cinetpay');

            if ($response->isSuccessful()) {
                return response('OK', 200);
            }

            return response('Error processing webhook', 400);
        } catch (\Exception $e) {
            return response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle Bizao webhook
     *
     * @param Request $request
     * @return Response
     */
    public function bizao(Request $request): Response
    {
        try {
            $response = $this->paymentManager->processWebhook($request->all(), 'bizao');

            if ($response->isSuccessful()) {
                return response('OK', 200);
            }

            return response('Error processing webhook', 400);
        } catch (\Exception $e) {
            return response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle Winipayer webhook
     *
     * @param Request $request
     * @return Response
     */
    public function winipayer(Request $request): Response
    {
        try {
            $response = $this->paymentManager->processWebhook($request->all(), 'winipayer');

            if ($response->isSuccessful()) {
                return response('OK', 200);
            }

            return response('Error processing webhook', 400);
        } catch (\Exception $e) {
            return response('Error: ' . $e->getMessage(), 500);
        }
    }
}
