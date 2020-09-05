<?php
namespace Controllers;

use Lib\Image;
use Models\Documents;
use Models\ProductOrder;
use Models\Products;
use Models\Survey;
use Models\SurveyUsers;
use Models\TempFiles;

class ProductController extends \Phalcon\Mvc\Controller
{
	public function indexAction($id)
	{
		$error 			= false;
		$success 		= false;

		$data 	= Products::getById($id);

		$photos = Documents::find([
			[
				"uuid" 			=> $data->uuid,
				"is_deleted"	=> ['$ne' => 1]
			]
		]);

		$this->view->setVar("data", $data);
		$this->view->setVar("photos", $photos);

		$this->view->partial("product/index");
		exit;
	}



	public function step2Action($id)
	{
		$error 			= false;
		$success 		= false;

		$quantity 		= (int)$this->request->get("quantity");
		if($quantity < 1) $quantity = 1;

		$data 	= Products::getById($id);

		if($this->request->get("save") > 0)
		{
			$firstname 		= trim($this->request->get("firstname"));
			$lastname 		= trim($this->request->get("lastname"));
			$email 			= trim($this->request->get("email"));
			$phone 			= trim($this->request->get("phone"));

			if(strlen($firstname) < 2){
				$error = $this->lang->get("FirstnameError", "Firstname is empty");
			}elseif(strlen($lastname) < 2){
				$error = $this->lang->get("LastnameError", "Lastname is empty");
			}elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
				$error = $this->lang->get("EmailError", "Email is wrong");
			}elseif(strlen($phone) < 5 || !is_numeric($phone)){
				$error = $this->lang->get("PhoneError", "Phone is wrong");
			}else{

			}
		}


		$this->view->setVar("data", $data);
		$this->view->setVar("quantity", $quantity);

		$this->view->partial("product/step2");
		exit;
	}



	public function droppointsAction()
	{
		$deliverer  = trim($this->request->get("deliverer"));
		$postcode   = trim($this->request->get("postcode"));

		$url = "https://app.pakkelabels.dk/api/public/v3/pickup_points";
		$vars = [
		    "carrier_code"  => $deliverer, // bring, dao, db_schenker_se, gls, pdk
		    "country_code"  => "DK",
		    "zipcode"       => $postcode,
        ];

        $datastring = "";
        foreach ($vars as $key => $value)
            $datastring .= $key."=".urlencode($value)."&";

        $ch = curl_init($url."?".$datastring);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "364ef8a5-6974-4f25-8b59-1c4ab5da167c:783e6ee0-f97e-435f-a7fd-bfa703fab397");
        //curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic QWxhZGRpbjpPcGVuU2VzYW1l']);
        $result = curl_exec($ch);
        $result = json_decode($result, true);

        if(count($result) > 0)
        {
            $response = [
                "status" 	=> "success",
                "data"		=> $result,
            ];
        }
        else
        {
            $response = [
                "status" 		=> "error",
                "description"	=> $this->lang->get("PostcodeIsWrong", "Postcode is wrong"),
            ];
        }
		exit(json_encode($response, true));
	}




	public function payAction()
	{
	    $error = false;

	    define("VAT", 10);

	    $O                          = new ProductOrder();
        $O->id                      = 1;
	    $O->firstname               = $this->request->get("firstname");
	    $O->lastname                = $this->request->get("lastname");
	    $O->phone                   = trim(str_replace(["+","-"," ","_",".",","], "", $this->request->get("phone")));
	    $O->email                   = mb_strtolower($this->request->get("email"));
	    $O->city                    = trim($this->request->get("city"));
	    $O->deliverer               = trim($this->request->get("deliverer"));
	    $O->postcode                = trim($this->request->get("postcode"));
	    $O->droppoint               = trim($this->request->get("droppoint"));
	    $O->droppoint_text          = trim($this->request->get("droppointvalue"));
        $O->quantity                = (int)$this->request->get("quantity");
        $O->product_price           = PRODUCT_PRICE;
        $O->delivery_cost           = DELIVERY_COST;
        $O->vat                     = VAT;
        $O->total                   = $O->quantity * PRODUCT_PRICE + DELIVERY_COST;

        /**
        if (strlen($O->firstname) < 1 || strlen($O->firstname) > 100)
        {
            $error = $this->lang->get("FirstnameError", "Firstname is empty");
        }
        elseif (strlen($O->lastname) < 1 || strlen($O->lastname) > 100)
        {
            $error = $this->lang->get("LastnameError", "Lastname is empty");
        }
        elseif (!filter_var($O->email, FILTER_VALIDATE_EMAIL))
        {
            $error = $this->lang->get("EmailError", "Email is wrong");
        }
        elseif ($O->quantity < 1)
        {
            $error = $this->lang->get("TechnicalError", "Technical error occurred. Please, try again");
        }
        elseif (strlen($O->phone) < 1 || strlen($O->phone) > 50 || !is_numeric($O->phone))
        {
            $error = $this->lang->get("PhoneError", "Phone is wrong");
        }
        else
        {
            $O->id              = ProductOrder::getNewId();
            $O->status          = 0;
            $O->paid            = 0;
            $O->is_deleted      = 0;
            $O->created_at      = ProductOrder::getDate();
            $O->save(); */

        $tax = ($O->total / VAT) * 10000;

            $data = [
                "order" => [
                    "items" => [
                        [
                            "reference"         => (string)$O->id,
                            "name" 				=> "LeakBot med VVS-service",
                            "quantity" 			=> 1,
                            "unit" 				=> "pcs",
                            "unitPrice" 		=> $O->total * 100,
                            "taxRate" 			=> VAT * 100,
                            "taxAmount" 		=> $tax,
                            "grossTotalAmount" 	=> $O->total * 100 + $tax,
                            "netTotalAmount" 	=> $O->total * 100
                        ]
                    ],
                    "amount" 	=> $O->total * 100 + $tax,
                    "currency" 	=> "DKK",
                    //"paymentType"      => ["VISA","MC","AMEX","MTRO","ELEC"],
                    "reference"         => "Demo order",
                ],
                "checkout" => [
                    "url" 			=> "http://ihomecrons.besfly.com/product/step2/1?quantity=1",
                    "termsUrl" 		=> "http://ihomecrons.besfly.com/product/step2/1?quantity=2",
                    "consumerType" 	=> [
                        "supportedTypes" => [
                            "B2C",
                            "B2B"
                        ],
                        "default" => "B2C"
                    ],
                    "ShippingCountries" => [["countryCode" => "DNK"]],
                    "publicDevice" 	=> true,
                    "charge" 		=> true,
                ],


                "paymentMethods" => [
                    [
                        "name" => "easyinvoice",
                        "fee" => [
                            "reference"         => (string)$O->id,
                            "name" 				=> "LeakBot med VVS-service",
                            "quantity" 			=> 1,
                            "unit" 				=> "pcs",
                            "unitPrice" 		=> $O->total * 100,
                            "taxRate" 			=> VAT * 100,
                            "taxAmount" 		=> $tax,
                            "grossTotalAmount" 	=> $O->total * 100 + $tax,
                            "netTotalAmount" 	=> $O->total * 100
                        ]
                    ]
                ],
                /**

                "webHooks" => [
                    [
                        "eventName" 	=> "payment.refund.completed",
                        "url" 			=> URL,
                        "authorization" => string
                    ]
                ] */
            ];

            $datastring = json_encode($data);

            $ch = curl_init('https://test.api.dibspayment.eu/v1/payments');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datastring);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: '.DIBS_PAYMENT_KEY)
            );
            $result = curl_exec($ch);
            $result = json_decode($result, true);

            $response = [
                "status"        => "success",
                "payment_id"    => $result["paymentId"]
            ];
        //}

        if($error)
        {
            $response = [
                "status"        => "error",
                "description"   => $error
            ];
        }
        exit(json_encode($response, true));
	}

}