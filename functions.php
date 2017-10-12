<?php
require("vendor/autoload.php");

$tpaga_url = 'https://sandbox.tpaga.co';

// Production
$private_api_key_tpaga = 'gpb6s6l4dk9ss668djjj7d59a8in12up';
// Development
//$private_api_key_tpaga = '2mduai312lq43clg9kmlbmqlbe0gpimf';

function getEcwidPayload($app_secret_key, $data) {
  // Get the encryption key (16 first bytes of the app's client_secret key)
  $encryption_key = substr($app_secret_key, 0, 16);  

  // Decrypt payload
  $json_data = aes_128_decrypt($encryption_key, $data);

  // Decode json
  $json_decoded = json_decode($json_data, true);
  return $json_decoded;
}

function aes_128_decrypt($key, $data) {
  // Ecwid sends data in url-safe base64. Convert the raw data to the original base64 first
  $base64_original = str_replace(array('-', '_'), array('+', '/'), $data);  

  // Get binary data
  $decoded = base64_decode($base64_original);  

  // Initialization vector is the first 16 bytes of the received data
  $iv = substr($decoded, 0, 16);

  // The payload itself is is the rest of the received data
  $payload = substr($decoded, 16);  

  // Decrypt raw binary payload
  $json = openssl_decrypt($payload, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);  
  // $json = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $payload, MCRYPT_MODE_CBC, $iv); // You can use this instead of openssl_decrupt, if mcrypt is enabled in your system

  return $json;
}

function create_tpaga_customer($firstName, $email, $phone) {
        $customer_data = [ 
                'firstName' => ($firstName),
                'email' => ($email),
                'phone' => ($phone) 
        ];
        
        $json_response = tpaga_api_post ( '/api/customer', $customer_data, [ 
                201 
        ] );
        
        return $json_response;
}

function assoc_cc_customer($customerId, $ccToken) {

        $json_response = tpaga_api_post ( "/api/customer/" . $customerId . "/credit_card_token", [ 
                'token' => ($ccToken) 
        ], [ 
                201 
        ] );
        
        return $json_response;
    }

function create_charge($taxAmount, $amount, $creditCard, $currency = 'COP', $installments, $orderId) {

        $json_response = tpaga_api_post ( "/api/charge/credit_card", [ 
                'taxAmount' => $taxAmount,
                'amount' => intval ( $amount ),
                'currency' => $currency,
                'creditCard' => $creditCard,
                'installments' => $installments,
                'orderId' => $orderId
        ], [ 
                201,
                402 
        ] );
        
        return $json_response;
}

function create_cc($primaryAccountNumber, $cardHolderName, $expirationMonth, $expirationYear, $cvc) {

        $card_data = [ 
                'primaryAccountNumber' => ($primaryAccountNumber),
                'cardHolderName' => ($cardHolderName),
                'expirationMonth' => ($expirationMonth),
                'expirationYear' => ($expirationYear),
                'cvc' => ($cvc) 
        ];

        $json_response = tpaga_api_post_tokenize ( '/api/tokenize/credit_card', $card_data, [ 
                201 
        ] );

        return $json_response;
}

function update_ecwid($storeId, $orderNumber, $token, $paymentStatus) {

        $json_response = ecwid_update_put ( '/api/v3/' . $storeId . '/orders/' . $orderNumber . '?token=' . $token, $paymentStatus, [ 
                200,
                201 
        ] );

        return $json_response;
}

function tpaga_api_post($url, $data, $expected_http_codes) {
        $client = new GuzzleHttp\Client ( [
                // Production
                'base_uri' => 'https://api.tpaga.co',
                // Development
//                'base_uri' => 'https://sandbox.tpaga.co',
                'timeout' => 30,
                'headers' => [ 
                        'Content-Type' => 'application/json' 
                ],
                'http_errors' => false,
                'verify' => false 
        ] );
        
        $response = null;
        
        try {

            if (isset($data['firstName'])) {

                $response = $client->request('POST', $url, [ 
                        'auth' => [
                            // Production
                                'gpb6s6l4dk9ss668djjj7d59a8in12up',
                            // Development
//                                '2mduai312lq43clg9kmlbmqlbe0gpimf',
                                ': '
                        ],
                        'json' => [
                                    'firstName' => $data['firstName'],
                                    'email' => $data['email'],
                                    'phone' => $data['phone']
                                  ]
                ] );

                $bug = 'create_tpaga_customer';
            }

            elseif (isset($data['amount'])) {

                $response = $client->request('POST', $url, [ 
                        'auth' => [
                            // Production
                                'gpb6s6l4dk9ss668djjj7d59a8in12up',
                            // Development
//                                '2mduai312lq43clg9kmlbmqlbe0gpimf',
                                ': '
                        ],
                        'json' => [
                                    'taxAmount' => $data['taxAmount'],
                                    'amount' => $data['amount'],
                                    'currency' => $data['currency'],
                                    'creditCard' => $data['creditCard'],
                                    'installments' => $data['installments'],
                                    'orderId' => $data['orderId']
                                  ]
                ] );

                $bug = 'create_charge';
            }

            else{
                $response = $client->request('POST', $url, [ 
                        'auth' => [
                            // Production
                                'gpb6s6l4dk9ss668djjj7d59a8in12up',
                            // Development
//                                '2mduai312lq43clg9kmlbmqlbe0gpimf',
                                ': '
                        ],
                        'json' => [
                                    'token' => $data['token']
                                  ]
                ] );

                $bug = 'assoc_cc_customer';
            }
        }
        catch ( Exception $e )
        {
            error_log ( "Caught exception: " . $e->getMessage () );
            echo ('Error: ' . $e->getMessage ());
            exit ();
        }
        
        if (! in_array ( $response->getStatusCode (), $expected_http_codes ))
        {
            update_ecwid($GLOBALS['storeId'], $GLOBALS['orderNumber'], $GLOBALS['token'], "CANCELLED");
            print_r( 'tpaga_api_post - ' . $response->getStatusCode(). ' - bug: ' . $bug); die();
            exit ();
        }
        
        return json_decode ( $response->getBody (), true );
    }

function ecwid_update_put($url, $data, $expected_http_codes) {

      $client = new GuzzleHttp\Client ( [ 
                'base_uri' => 'https://app.ecwid.com',
                'timeout' => 30,
                'headers' => [ 
                        'Content-Type' => 'application/json' 
                ],
                'http_errors' => false,
                'verify' => false 
        ] );
        
        $response = null;
        
        try {
            $response = $client->request('PUT', $url, [
                            'json' => [
                                        'paymentStatus' => $data
                                      ]
            ] );
        }
        catch ( Exception $e ) {
            error_log ( "Caught exception: " . $e->getMessage () );
            echo ('Error: ' . $e->getMessage ());
            exit ();
            
            // header ( "Location: http://" . $_SERVER [HTTP_HOST] . "/" );
            // die ();
        }
        
        if (! in_array ( $response->getStatusCode (), $expected_http_codes )) {
            // TODO set proper path for redirect
            // header ( "Location: http://" . $_SERVER [HTTP_HOST] . "/" );
          print_r('ecwid: ' . $response->getStatusCode()); die();
            exit ();
        }
        
        return json_decode ( $response->getBody (), true );
    }
?>