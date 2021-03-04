<?php
//данные для добавления в таблицу логов
  require_once("server_config.php");

  $response = $_POST["g-recaptcha-response"];

  $url = 'https://www.google.com/recaptcha/api/siteverify';
	$data = array(
		'secret' => $GLOBALS['RPC_RECAPTCHA_SECRETKEY'],
		'response' => $response
	);
	$options = array(
		'http' => array (
       'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                    "User-Agent:MyAgent/1.0\r\n",
			'method' => 'POST',
			'content' => http_build_query($data)
		)
	);
	$context  = stream_context_create($options);
	$verify = @file_get_contents($url, false, $context);
	$captcha_success=json_decode($verify);
	if ($captcha_success->success==false) {

			$errors['human'] = 'Неправильная капча.';
			$data['errors'] = true;
			$data['errors']  = $errors;
			echo json_encode($data);
      die;

	} elseif ($captcha_success->success==true) {

      require_once("jsonRPCClient.php");
        //try{
      		$alt = new jsonRPCClient($GLOBALS["RPC_URL"]); 
        //}
        /*catch(Exception $e) {
            $errors['RPCClient'] = "Нет соединения!";
            $data['errors'] = true;
            $data['errors']  = $errors;
            echo json_encode($data);
            die;
        }*/
      $min = $GLOBALS["PAYOUT_MIN"];
      $max = $GLOBALS["PAYOUT_MAX"];
      $multi = (rand(50, 100) / 100) * 10;
      $amount = rand($min,$max)*$multi;
      $amount_max = $GLOBALS["PAYOUT_MAX"]*10;
      $amount=$amount/$GLOBALS["PAYOUT_AMOUNT_MULTIPLIER"];
      		$username = $_POST['address'];
      		$check = $alt->validateaddress($username);

      		if($alt->getbalance() < $amount){
        			$errors['balance'] = 'Кран пуст.';
        			$data['errors'] = true;
        			$data['errors']  = $errors;
        			echo json_encode($data);
              die;

      		} else {

            if($check->{'isvalid'} == 1){
    				
                $alt->sendtoaddress($username, $amount);

      					$data['success'] = true;
                $chance = ($amount/$amount_max)*$GLOBALS["PAYOUT_AMOUNT_MULTIPLIER"]*100;
      					$data['boa'] = "Вы получили " . $amount . " енотов!<br>Мультикаст: ".$multi."x<br>Эффективность: ".$chance."%";
                
      					echo json_encode($data);
                die;
  				} else {
                $errors['address'] = 'Неправильный адрес.';
      					$data['errors'] = true;
      					$data['errors']  = $errors;
      					echo json_encode($data);
                die;
          }
          
        }

    }
?>