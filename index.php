<?php
require_once("functions.php");
require_once "db_connection/models/User.php";
require_once "db_connection/models/CreditCard.php";
require_once "db_connection/search_temp.php";

if (isset($_POST['data'])) {

    // Get payload from the POST and process it
    $ecwid_payload = $_POST['data'];
    $client_secret = "Qxke8FssqmvrpupcnivJtLTXMNKofaNw"; // this is a dummy value. Please place your app secret key here

    // The resulting JSON array will be in $result variable
    $result = getEcwidPayload($client_secret, $ecwid_payload);

    // TODO: Esta es una implementación temporal
    $searchUser = findUser($result['cart']['order']['customerId']);
    //$searchUser = $user->findOne('customer_id_ecwid', $result['cart']['order']['customerId']);

    // Si no encuentra el usuario en la base de datos
    if(!isset($searchUser))
    {
        $response_tpaga_customer = create_tpaga_customer($result['cart']['order']['billingPerson']['name'], $result['cart']['order']['email'], $result['cart']['order']['billingPerson']['phone']);

        $GLOBALS['idTpagaCustomer'] = $response_tpaga_customer['id'];

        $user = new User();
        $user->customer_id_ecwid = $result['cart']['order']['customerId'];
        $user->token_tpaga = $response_tpaga_customer['id'];
        $user->save();
    }

    else
    {
        $GLOBALS['idCustomer'] = $searchUser[0];
        $GLOBALS['idTpagaCustomer'] = $searchUser[1];

        // TODO: Temporal
        $searchAllCreditCards = findAllCreditCards($searchUser[0]);
    }

}

elseif (isset($_POST['idTpagaCustomer']))
{
    if($_POST['lastFour'] !== '')
    {
        $response_asocie_cc = assoc_cc_customer($_POST['idTpagaCustomer'], $_POST['tmpCcToken']);

        if(isset($response_asocie_cc['id']))
        {
            $creditCard = new CreditCard();
            $creditCard->token = $response_asocie_cc['id'];
            $creditCard->last_four = $_POST['lastFour'];
            $creditCard->user_id = $_POST['idCustomer'];
            $creditCard->save();
        }

        else
        {
            header("Location: https://megapiel.com/tpaga/decline.php");
            die();
        }



        $response_charge = create_charge($_POST['taxAmount'], $_POST['amount'], $response_asocie_cc['id'], $currency = 'COP', $_POST['quotesForm'], $_POST['orderNumber']);
    }

    else
    {
        $response_charge = create_charge($_POST['taxAmount'], $_POST['amount'], strval($_POST['tmpCcToken']), $currency = 'COP', $_POST['quotesForm'], $_POST['orderNumber']);
    }


    if($response_charge['errorCode'] != "00")
    {
    	header("Location: https://megapiel.com/tpaga/decline.php?message=" . $response_charge['errorMessage']);
		die();
    }

    $GLOBALS['storeId'] = $_POST['storeId'];
    $GLOBALS['orderNumber'] = $_POST['orderNumber'];
	$GLOBALS['token'] = $_POST['token'];

    $response_ecwid = update_ecwid($GLOBALS['storeId'], $GLOBALS['orderNumber'], $GLOBALS['token'], "PAID");

    if($response_ecwid['updateCount'] == 1)
    {
    	header("Location: https://megapiel.com/tpaga/success.php");
		die();
    }

    else
    {
    	header("Location: https://megapiel.com/tpaga/decline.php");
		die();
    }
}

?>

<html>
    <head>
        <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Checkout - Tpaga</title>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <!-- Include Ecwid JS SDK -->
    <script src="https://djqizrxa6f10j.cloudfront.net/ecwid-sdk/js/1.2.3/ecwid-app.js"></script>

    <script>
        // Initialize the application
        EcwidApp.init({
          app_id: "tpaga", // place your application namespace here (not clientID)
          autoloadedflag: true, 
          autoheight: true
        });

        // Get the store ID and access token
        var storeData = EcwidApp.getPayload();
        var storeId = storeData.store_id;
        var accessToken = storeData.access_token;
        var language = storeData.lang;

        if (storeData.public_token !== undefined){
          var publicToken = storeData.public_token;
        }

        if (storeData.app_state !== undefined){
          var appState = storeData.app_state;
        }

        // do something...

        language = {
          title: "Tpaga"      
        }
    </script>
    <script>
        $.fn.serializeObject = function()
        {
            var o = {};
            var a = this.serializeArray();
            $.each(a, function() {
                if (o[this.name] !== undefined) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        };

        function notify_backend_tempcctoken(jd)
        {
            $('[name="tmpCcToken"]').val(jd.token);
            $('form#assoc_customer_cc').submit();
        }

        function handle_request_error(request, text_status, error_thrown) {
            if (request.status == 401) {
                alert("Problema con credenciales de acceso a Tpaga");
                return;
            }
            if (request.status == 422) {
                var jd = JSON.parse(request.responseText);
                alert("Datos erróneos en el campo " + jd.errors[0].field);
                return;
            }
        }

        function form_submit(evt)
        {
            var d = new Date();
            var year = d.getFullYear();
            var month = d.getMonth()+1;

            if($('[name="primaryAccountNumber"]').val() == "")
            {
                alert('Debe escribir el número de la tarjeta de crédito');
                return false;
            }

            else if($('[name="cardHolderName"]').val() == "")
            {
                alert('Debe escribir el nombre como aparece en la tarjeta');
                return false;
            }

            else if($('[name="expirationYear"]').val() == 0)
            {
                alert('Debe elegir el año de expiración');
                return false;
            }

            else if($('[name="expirationMonth"]').val() == 0)
            {
                alert('Debe elegir el mes de expiración');
                return false;
            }

            else if($('[name="cvc"]').val() == "")
            {
                alert('Debe escribir el CVC');
                return false;
            }

            else if($('[name="quotes"]').val() == 0)
            {
                alert('Debe elegir el número de cuotas');
                return false;
            }

            else if($('[name="expirationYear"]').val() == year && $('[name="expirationMonth"]').val() <= month)
            {
                alert('Esta tarjeta ha expirado');
                return false;
            }

//            var public_token = "dn19iq9df9qse9lgghssv9h21g8h28ph";

            var public_token = "plvakmngej7ejnpb4lgj6p2tf0mak0f8";

            $.ajax('https://sandbox.tpaga.co/api/tokenize/credit_card', {
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader("Authorization", "Basic " + btoa("plvakmngej7ejnpb4lgj6p2tf0mak0f8" + ": "));
                },
                username: public_token,
                password: '',

                data: JSON.stringify($("form#cc_data").serializeObject()),
                contentType: 'application/json',
                dataType: 'json',

                success: notify_backend_tempcctoken,
                error: handle_request_error,
            });

            return false;
        }

        function tc_registradas_submit(jd)
        {
            $('[name="tmpCcToken"]').val($('[name="credit_card"]').val());

            $('[name="quotesForm"]').val($('[name="tc_registradas_quotes"]').val());

            $('form#assoc_customer_cc').submit();

            return false;
        }

        $(document).ready(function ()
        {
            $('form#cc_data').on('submit', form_submit);

            $('form#tc_registradas').on('submit', tc_registradas_submit);
        });

    </script>
    </head>
<body>
    <div class="container">
        <img class="img-responsive" src="images/logo.png">
        <?php
            if(isset($searchAllCreditCards))
            {

        ?>
            <div class="col-md-4 text-center">
                <h1>Mis tarjeta registradas</h1>
                <form id="tc_registradas">
                    <div class="form-group">
                        <select name="credit_card" class="form-control">
                            <?php
                            foreach ($searchAllCreditCards as $card)
                            {
                                echo '<option value="' . $card[1] . '">' . $card[0] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="tc_registradas_quotes" class="form-control">
                            <option>Número de cuotas</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
                            <option value="13">13</option>
                            <option value="14">14</option>
                            <option value="15">15</option>
                            <option value="16">16</option>
                            <option value="17">17</option>
                            <option value="18">18</option>
                            <option value="19">19</option>
                            <option value="20">20</option>
                            <option value="21">21</option>
                            <option value="22">22</option>
                            <option value="23">23</option>
                            <option value="24">24</option>
                            <option value="25">25</option>
                            <option value="26">26</option>
                            <option value="27">27</option>
                            <option value="28">28</option>
                            <option value="29">29</option>
                            <option value="30">30</option>
                            <option value="31">31</option>
                            <option value="32">32</option>
                            <option value="33">33</option>
                            <option value="34">34</option>
                            <option value="35">35</option>
                            <option value="36">36</option>
                        </select>
                    </div>
                    <input type="submit" id="submit" class="btn btn-default" value="Pagar">
                </form>
            </div>
        <?php
            }
        ?>
        <div class="col-md-4 col-md-offset-4 text-center">
        	<h2>Valor Total: <?php echo '$' . number_format($result['cart']['order']['total']); ?></h2>
        	<h2>Valor IVA: <?php echo '$' . number_format($result['cart']['order']['tax']); ?></h2>
		    <form id="cc_data">
		     	<div class="form-group">
		        	<input type="number" class="form-control" name="primaryAccountNumber" size="16" onBlur="validCard()" placeholder="Número de la Tarjeta de Credito"br>
		        </div>
		        <div class="form-group">
		        	<input type="text" class="form-control" name="cardHolderName" onBlur="validCardHolderName()" placeholder="Nombre">
		        </div>
		        <div class="form-group">
		        	<select name="expirationYear" class="form-control">
			        	<option value="0">Año de expiración</option>
			        	<option value="2017">2017</option>
			        	<option value="2018">2018</option>
			        	<option value="2019">2019</option>
			        	<option value="2020">2020</option>
			        	<option value="2021">2021</option>
			        	<option value="2022">2022</option>
			        	<option value="2023">2023</option>
			        	<option value="2024">2024</option>
			        	<option value="2025">2025</option>
			        	<option value="2026">2026</option>
			        	<option value="2027">2027</option>
			        	<option value="2028">2028</option>
			        	<option value="2029">2029</option>
			        	<option value="2030">2030</option>
		        	</select>
		        </div>
		        <div class="form-group">
		        	<select name="expirationMonth" class="form-control" onBlur="validateExpirationMonth()">
		        		<option value="0">Mes de expiración</option>
		          		<option value="01">01</option>
			          	<option value="02">02</option>
			          	<option value="03">03</option>
			          	<option value="04">04</option>
			          	<option value="05">05</option>
			          	<option value="06">06</option>
			          	<option value="07">07</option>
			          	<option value="08">08</option>
			          	<option value="09">09</option>
			          	<option value="10">10</option>
			          	<option value="11">11</option>
			          	<option value="12">12</option>
		        	</select>
		        </div>
		        <div class="form-group">
		        	<input type="password" class="form-control" name="cvc" size="10" placeholder="CVC" onBlur="validCvc()">
		        </div>
		        <div class="form-group">
                    <select name="quotes" class="form-control">
                        <option>Número de cuotas</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                        <option value="13">13</option>
                        <option value="14">14</option>
                        <option value="15">15</option>
                        <option value="16">16</option>
                        <option value="17">17</option>
                        <option value="18">18</option>
                        <option value="19">19</option>
                        <option value="20">20</option>
                        <option value="21">21</option>
                        <option value="22">22</option>
                        <option value="23">23</option>
                        <option value="24">24</option>
                        <option value="25">25</option>
                        <option value="26">26</option>
                        <option value="27">27</option>
                        <option value="28">28</option>
                        <option value="29">29</option>
                        <option value="30">30</option>
                        <option value="31">31</option>
                        <option value="32">32</option>
                        <option value="33">33</option>
                        <option value="34">34</option>
                        <option value="35">35</option>
                        <option value="36">36</option>
                    </select>
                </div>
		        <input type="submit" id="submit" class="btn btn-default" value="Pagar">
		    </form>
            <br><br>
            <img class="img-responsive" src="images/tpaga.png">
		</div>
	</div>

    <form id="assoc_customer_cc" action="index.php" method="POST">
        <input type="hidden" name="tmpCcToken">
        <input type="hidden" name="lastFour">
        <input type="hidden" name="idCustomer" value="<?php print_r($GLOBALS['idCustomer']); ?>">
        <input type="hidden" name="idTpagaCustomer" value="<?php print_r($GLOBALS['idTpagaCustomer']); ?>">
        <input type="hidden" name="taxAmount" value="<?php echo $result['cart']['order']['tax']; ?>">
        <input type="hidden" name="amount" value="<?php echo $result['cart']['order']['total']; ?>">
        <input type="hidden" name="quotesForm">
        <input type="hidden" name="storeId" value="<?php echo $result['storeId']; ?>">
        <input type="hidden" name="orderNumber" value="<?php echo $result['cart']['order']['orderNumber']; ?>">
        <input type="hidden" name="token" value="<?php echo $result['token']; ?>">
    </form>

        <script type="text/javascript">
    	// takes the form field value and returns true on valid number
		function valid_credit_card (value) {
		  // accept only digits, dashes or spaces
		    if (/[^0-9-\s]+/.test(value)) return false;

		    // The Luhn Algorithm. It's so pretty.
		    var nCheck = 0, nDigit = 0, bEven = false;
		    value = value.replace(/\D/g, "");

		    for (var n = value.length - 1; n >= 0; n--) {
		        var cDigit = value.charAt(n),
		              nDigit = parseInt(cDigit, 10);

		        if (bEven) {
		            if ((nDigit *= 2) > 9) nDigit -= 9;
		        }

		        nCheck += nDigit;
		        bEven = !bEven;
		    }

		    return (nCheck % 10) == 0;
		}
    	
		function validCard()
		{
			if($('[name="primaryAccountNumber"]').val() != "")
			{
				$valid_cc = valid_credit_card($('[name="primaryAccountNumber"]').val());
	    		
	    		if($valid_cc === false || $('[name="primaryAccountNumber"]').val().length <= 13 || $('[name="primaryAccountNumber"]').val().length > 16)
	    		{
	    			$('#submit').prop( "disabled", true );
	    			alert("Debe ingresar un número de tarjeta de crédito valido");
	    			$('[name="primaryAccountNumber"]').val("");
	    		}
	    		else
	    		{
	    			$('#submit').prop( "disabled", false );
	    		}

                var primaryAccountNumberToString = $('[name="primaryAccountNumber"]').val().toString(); //convert to string

                $('[name="lastFour"]').val(primaryAccountNumberToString.slice(-4));
			}			
		}

		function validateExpirationMonth()
		{
			var f = new Date();
			
			if ($('[name="expirationYear"]').val() == 0)
			{
				alert("Seleccione primero el año de expiración");
				$("#expirationMonth").val("0");
			}

			else if ($('[name="expirationYear"]').val() == f.getFullYear())
			{
				if ($('[name="expirationMonth"]').val() <= f.getMonth())
				{
					alert("Esta tarjeta se encuentra vencida");
					$('[name="expirationMonth"]').val("0");
				}
			}
		}

		function validCvc()
		{
			if ($('[name="cvc"]').val().length > 4)
			{
				alert("El CVC no puede exceder los 4 caracteres");
				$('[name="cvc"]').val("");
			}
		}

        function validCardHolderName()
        {
            if ($('[name="cardHolderName"]').val().length < 2)
            {
                alert("El Nombre debe ser minimo de 2 caracteres");
                $('[name="cardHolderName"]').val("");
            }
        }

		$('[name="quotes"]').on('change', function() {
  			$('[name="quotesForm"]').val(this.value);
		})
    </script>
</body>
</html>
