<?php

class PaymentGatewayControllerTest extends PaymentTest{

	public static $fixture_file = array(
		'payment.yml'
	);

	protected $autoFollowRedirection = false;

	public function setUp(){
		parent::setUp();

		Config::inst()->update("Payment", "allowed_gateways", array(
			'PayPal_Express',
			'PaymentExpress_PxPay',
			'Manual',
			'Dummy'
		));

	}

	public function testReturnUrlGeneration() {
		$transaction = $this->objFromFixture('GatewayMessage','transaction1');
		$url = PaymentGatewayController::get_return_url($transaction,'action',"shop/complete");
		$this->assertEquals(
			Director::absoluteURL("paymentendpoint/UNIQUEHASH23q5123tqasdf/action/c2hvcC9jb21wbGV0ZQ%3D%3D"),
			$url,
			"generated url"
		);
	}

	public function testSucessfulEndpoint() {

		PaymentService::set_http_client($this->getHttpClient());
		PaymentService::set_http_request($this->getHttpRequest());

		$this->setMockHttpResponse('PaymentExpress/Mock/PxPayPurchaseSuccess.txt');//add success mock response from file

		//Note the string 'c2hvcC9jb21wbGV0ZQ%3D%3D' is just "shop/complete" base64 encoded, then url encoded
		$response = $this->get("paymentendpoint/UNIQUEHASH23q5123tqasdf/complete/c2hvcC9jb21wbGV0ZQ%3D%3D"); //mimic gateway update

		$transaction = GatewayMessage::get()
						->filter('Identifier','UNIQUEHASH23q5123tqasdf')
						->first();
		//redirect works
		$headers = $response->getHeaders();
		$this->assertEquals(Director::baseURL()."shop/complete", $headers['Location'], "redirected to shop/complete");

		$payment = $transaction->Payment();

		//TODO: model is appropriately updated - need to
		//$this->assertEquals('Captured', $payment->Status);

	}

	public function testBadReturnURLs(){
		$response = $this->get("paymentendpoint/ASDFHSADFunknonwhash/complete/c2hvcC9jb2");

	}

	public function testSecurity() {
		//$this->get(); //mimic mallicious activity
		//incorrect security token
		//
		//database changes shouldn't be made by unauthorised means
		//see https://github.com/burnbright/silverstripe-omnipay/issues/13
	}

	//TODO: test purchase -> completePurchase (this failed because gateaway passed identifier was $message->ID, not $message->Identifier)
	//TODO: test authorize -> completeAuthorize

}
