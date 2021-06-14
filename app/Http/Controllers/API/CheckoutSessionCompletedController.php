<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe;
use App\Models\CheckoutSession;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Cart;

class CheckoutSessionCompletedController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
		$endpoint_secret = 'whsec_7bZEUrFBgspkuuiXt0xpnz8j4CTxUkBi';
		$payload = @file_get_contents('php://input');
		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event = null;

		try {
			$event = Stripe\Webhook::constructEvent(
				$payload, $sig_header, $endpoint_secret
			);
		}catch(\UnexpectedValueException $e) {
			// Invalid payload.
			 $response = [
					'success' => false,
					'message' => 'Invalid payload',
				];
			return response()->json($response, 400);
			//return $response->withStatus(400);
			
		} catch(\Stripe\Exception\SignatureVerificationException $e) {
			 // Invalid Signature.
			 $response = [
					'success' => false,
					'message' => 'Invalid signature',
				];
			return response()->json($response, 400);
			//return $response->withStatus(400);
		}
		// Handle the checkout.session.completed event
		if ($event->type == 'checkout.session.completed') {
				$session = $event->data->object;
			  // Fulfill the purchase...
			  $this->fulfill_order($session);
		}
		$response = [
					'success' => true,
					'message' =>  'Successful'
				];
		return response()->json($response, 200);
    }
	private function fulfill_order($session) {
		$session_id = $session->id;
		$checkoutSession = CheckoutSession::where('session_id',$session_id)->first();
		if($checkoutSession) {
			$foodie_id = $checkoutSession->user_id;
			
			if($session->payment_status == 'paid'){
				//create payment
				$payment = new Payment;
				$payment->subtotal = sprintf('%0.2f',($session->amount_subtotal/100));
				$payment->total = sprintf('%0.2f',($session->amount_total/100));
				$payment->tax = sprintf('%0.2f',($session->total_details->amount_tax/100));
				$payment->payment_intent = $session->payment_intent;
				$payment->user_id = $foodie_id;
				$payment->save();
				$payment_id = $payment->id;
				$chef_id = $session->metadata->chef_id;
				$cart_id = $session->metadata->cart_id;
				$cart_item = explode(',',$cart_id);
				//create order 
				$order = new Order;
				$order->payment_id = $payment_id;
				$order->chef_id = $chef_id;
				$order->foodie_id = $foodie_id;
				$order->save();
				$order_id = $order->id;
				//create order details
				foreach($cart_item as $value){
					$orderDetails = new OrderDetails;
					$orderDetails->order_id = $order_id;
					$orderDetails->cart_id = $value;
					$orderDetails->save();
					//update the cart
					$cart = Cart::find($value);
					$cart->status = 'inactive';
					$cart->save();
				}
				
			}
		}
	}
}
