<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'order_id' => 'required|exists:orders,order_id',
            'amount' => 'required|numeric|min:0',
        ]);

        $order = Order::findOrFail($request->order_id);

        // MoMo payment setup
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $endpoint = env('MOMO_ENDPOINT');

        $orderInfo = 'Order payment';
        $amount = $request->amount;
        $orderId = $order->order_id;
        $redirectUrl = route('payment.success'); // Set your success route
        $ipnUrl = route('payment.notify'); // Set your IPN route
        $requestId = time();
        $extraData = ''; // You can add any extra data if needed

        // Create MoMo payment request
        $data = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'returnUrl' => $redirectUrl,
            'notifyUrl' => $ipnUrl,
            'extraData' => $extraData,
        ];

        // Hash the request
        $signature = hash_hmac('sha256', json_encode($data), $secretKey);
        $data['signature'] = $signature;

        // Send request to MoMo
        $response = Http::post($endpoint, $data);

        // Check for success
        if ($response->successful()) {
            // Save the payment details
            $payment = Payment::create([
                'order_id' => $order->order_id,
                'user_id' => Auth::id(),
                'payment_method' => 'MoMo',
                'amount' => $amount,
                'status' => 'Pending',
                'transaction_id' => null, // Transaction ID will be filled after successful payment
            ]);

            return response()->json($response->json(), 200);
        }

        return response()->json(['error' => 'Payment creation failed'], 500);
    }

    public function success(Request $request)
    {
        // Retrieve payment information from the request
        $paymentId = $request->input('paymentId');
        $orderId = $request->input('orderId');
        $amount = $request->input('amount');
        $transactionId = $request->input('transactionId');
        $status = $request->input('status'); // Check the status returned by MoMo

        // Find the corresponding order
        $order = Order::findOrFail($orderId);

        // Update the payment record
        $payment = Payment::where('order_id', $orderId)->where('transaction_id', null)->first();
        if ($payment) {
            $payment->status = $status;
            $payment->transaction_id = $transactionId;
            $payment->save();
        }

        // Optionally, update the order status
        if ($status === 'successful') {
            $order->status = 'Completed'; // Update order status to Completed
            $order->save();
        }

        return view('payment.success', ['payment' => $payment, 'order' => $order]);
    }

    public function notify(Request $request)
    {
        // MoMo sends an IPN when the payment is processed
        // Retrieve payment information from the request
        $orderId = $request->input('orderId');
        $transactionId = $request->input('transactionId');
        $status = $request->input('status'); // Payment status

        // Find the corresponding payment record
        $payment = Payment::where('order_id', $orderId)->first();
        if ($payment) {
            // Update the payment record
            $payment->status = $status;
            $payment->transaction_id = $transactionId;
            $payment->save();

            // Optionally, update the order status if payment was successful
            if ($status === 'successful') {
                $order = Order::findOrFail($orderId);
                $order->status = 'Completed';
                $order->save();
            }
        }

        return response()->json(['message' => 'Notification received'], 200);
    }
}
