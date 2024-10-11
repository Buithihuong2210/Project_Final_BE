<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PayOSController extends Controller
{
    private $clientId;
    private $apiKey;
    private $checksumKey;

    public function __construct()
    {
        $this->clientId = env('PAYOS_CLIENT_ID');
        $this->apiKey = env('PAYOS_API_KEY');
        $this->checksumKey = env('PAYOS_CHECKSUM_KEY');
    }

    public function createPayment(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'amount' => 'required|numeric',
            'orderId' => 'required|string',
            'orderInfo' => 'required|string',
            'redirectUrl' => 'required|url',
        ]);

        // Prepare payment data
        $data = [
            'clientId' => $this->clientId,
            'orderId' => $request->orderId,
            'amount' => $request->amount,
            'orderInfo' => $request->orderInfo,
            'redirectUrl' => $request->redirectUrl,
            'checksum' => $this->generateChecksum($request->orderId, $request->amount),
        ];

        // Send request to PayOS
        $response = Http::post('https://payment.payos.com/api/payment/create', $data);

        // Check response
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Payment creation failed.'], 500);
        }
    }

    private function generateChecksum($orderId, $amount)
    {
        $data = $this->clientId . $orderId . $amount . $this->apiKey;
        return hash('sha256', $data); // You can change the hashing algorithm if needed
    }

    public function handlePaymentResponse(Request $request)
    {
        // Handle the response from PayOS
        // Validate the response and update the order status accordingly
        $request->validate([
            'orderId' => 'required|string',
            'paymentStatus' => 'required|string',
        ]);

        // Example of updating the order status
        // $order = Order::where('order_id', $request->orderId)->first();
        // if ($order) {
        //     $order->status = $request->paymentStatus; // 'success', 'failed', etc.
        //     $order->save();
        // }

        return response()->json(['message' => 'Payment response handled successfully.']);
    }
}
