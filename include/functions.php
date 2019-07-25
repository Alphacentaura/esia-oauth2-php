<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
class AuthEsia
{
	private $attribute = array();
	private $user_id = 0;
	private $mnemonic = "OPKK02231";
	private $scope = 'fullname email mobile birthdate gender snils id_doc';
	
	private $serv_addr = 'esia.gosuslugi.ru';
	private $serv_port = 443;
	private $serv_page = 'aas/oauth2/te';
	private $timelimit = 20;
	
	//  онструктор: запрос авторизаци€ в ≈—»ј
	public function __construct()
	{
		require_once($_SERVER["DOCUMENT_ROOT"]. '/esia2/include/func.php');
		
		$codeAuth = '';
		if (isset($_GET['code'])) {
			$codeAuth = $_GET['code'];
		}
		if (isset($_POST['code'])) {
			$codeAuth = $_POST['code'];
		}
		$state = ''; //нужно сравнивать отправленный state с полученным.
		$stateOld = '';
		if (isset($_GET['state'])) {
			$stateOld = $_GET['state'];
		}
		if (isset($_POST['state'])) {
			$stateOld = $_POST['state'];
		}

		if ($codeAuth === '' or $stateOld === '') {

			$scope = $this->scope;
			$timestamp = date('Y.m.d H:i:s O');
			$clientId = $this->mnemonic;
			$state = generate_uuid();
			
			$data = $scope.$timestamp.$clientId.$state;
			$msgFile = $_SERVER["DOCUMENT_ROOT"].'/esia2/'.generateFileName();
			$fp = fopen($msgFile, "w");
			fwrite($fp, $data);
			fclose($fp);

			$pathPrive = "file://cert/sever.key";
			$pathOpen = "file://cert/sever.crt";
			$signFile = $_SERVER["DOCUMENT_ROOT"].'/esia2/'.generateFileName();

			// encrypt it
			$Zashifrovano = False;
			if (openssl_pkcs7_sign($msgFile, $signFile, $pathOpen,

			array($pathPrive, "ertdf"),
			array("To" => "info@MY_SECRET_SITE.ru", // keyed syntax
			"From: HQ <info@MY_SECRET_SITE.ru>",// indexed syntax
			"Subject" => "Theme"), PKCS7_BINARY | PKCS7_DETACHED
			)) {
				$Zashifrovano = True;
			}
			if ($Zashifrovano === false) {
				return false;
			}
			$signed = file_get_contents($signFile);
			unlink($signFile);
			unlink($msgFile);
			$signed = explode("\n\n", $signed);

			$sign = str_replace("\n", "", $signed[3]);

			header("Location: https://esia.gosuslugi.ru/aas/oauth2/ac?timestamp=".urlencode($timestamp)."&client_id=$clientId&client_secret=".urlencode($sign)."&redirect_uri=".urlencode("https://MY_SECRET_SITE.ru/esia2/index.php")."&scope=$scope&response_type=code&state=$state&access_type=online");
		}
		else 
		{
			$serv_addr = $this->serv_addr;
			$serv_port = $this->serv_port;
			$serv_page = $this->serv_page;
			$timelimit = $this->timelimit;
			$errstr = '';
			$errno = '';
			$scope = $this->scope;
			$timestamp = date('Y.m.d H:i:s O');
			$clientId = $this->mnemonic;
			$state = generate_uuid();
			$data = $scope.$timestamp.$clientId.$state;
			
			$msgFile = $_SERVER["DOCUMENT_ROOT"].'/esia2/'.generateFileName();
			$fp = fopen($msgFile, "w");
			fwrite($fp, $data);
			fclose($fp);
			
			$pathPrive = "file://cert/sever.key";
			$pathOpen = "file://cert/sever.crt";
			$signFile = $_SERVER["DOCUMENT_ROOT"].'/esia2/'.generateFileName();

			$Zashifrovano = False;
			if (openssl_pkcs7_sign($msgFile, $signFile, $pathOpen,

			array($pathPrive, "ertdf"),
			array("To" => "info@MY_SECRET_SITE.ru", // keyed syntax
			"From: HQ <info@MY_SECRET_SITE.ru>",// indexed syntax
			"Subject" => "Theme"), PKCS7_BINARY | PKCS7_DETACHED
			)) {
				$Zashifrovano = True;
			}
			if ($Zashifrovano === false) {
				return false;
			}
			$signed = file_get_contents($signFile);
			unlink($signFile);
			unlink($msgFile);
			$signed = explode("\n\n", $signed);

			$sign = str_replace("\n", "", $signed[3]);

			$post_data = array('client_id' => $clientId,
			'code' => $codeAuth,
			'grant_type' => 'authorization_code',
			'client_secret' => $sign,
			'state' => $state,
			'redirect_uri' => "https://MY_SECRET_SITE.ru/esia2/index.php",
			'scope' => $scope,
			'timestamp' => $timestamp,
			'token_type' => 'Bearer');
			// √енерируем строку с POST запросом
			$post_data_text = '';
			foreach ($post_data AS $key => $val)
			$post_data_text .= $key.'='.urlencode($val).'&'; //может быть здесь не нужно URL-кодировать

			// ”бираем последний символ & из строки $post_data_text
			$post_data_text = substr($post_data_text, 0, -1);


			$headers = array('POST /'.$serv_page.' HTTP/1.1',
			'Host: '.$serv_addr,
			'Content-type: application/x-www-form-urlencoded',
			'Content-length: '.strlen($post_data_text),
			'Accept: */*',
			//'Accept-Encoding: gzip, deflate',
			'');

			// —оздание строки заголовков
			$headers_txt = '';
			foreach ($headers AS $val)
			{
				$headers_txt .= $val.chr(13).chr(10);
			}
			$request_body = $headers_txt.$post_data_text.chr(13).chr(10).chr(13).chr(10);

			// ќткрытие сокета
			$sp = fsockopen('ssl://'.$serv_addr, $serv_port, $errno, $errstr, $timelimit);

			if (!$sp)
				exit('Error: '.$errstr.' #'.$errno);

			fwrite($sp, $request_body);
			$timelimit2 = 57; //есть настройка в php.ini max_execution_time

			$server_answer = '';
			$server_header = '';
			$start = microtime(true);
			$header_flag = 1;
			while(!feof($sp) && (microtime(true) - $start) < $timelimit2)
			{
				$chunksize = '';
				if ($header_flag == 1)
				{
					$content = fgets($sp, 4096);
					if ($content === chr(13).chr(10))
						$header_flag = 0;
					else
						$server_header .= $content;
				}
				else
				{
					do {
						$chunksize .= fread($sp, 1);
					} while (strpos($chunksize, "\r\n") === false);
					$chunksize = (int)base_convert($chunksize, 16, 10);

					if ($chunksize === 0) {
						fread($sp, 2); // read trailing "\r\n"
						break;
					} else {
						$data = '';
						$datalen = 0;
						while ($datalen < $chunksize + 2) {
							$data .= fread($sp, $chunksize - $datalen + 2);
							$datalen = strlen($data);
						}
						$server_answer .= substr($data, 0, -2); // -2 to remove the "\r\n" before the next chunk
					}
				}
			}
			fclose($sp);
			
			$answerJson = json_decode($server_answer);
			$access_token = $answerJson->{"access_token"};
			$PartsOfAnswer = explode('.', $access_token);

			$header = base64UrlSafeDecode($PartsOfAnswer[0]);
			$payload = base64UrlSafeDecode($PartsOfAnswer[1]);
			$signature = $PartsOfAnswer[2];

			$payloadJson = json_decode($payload);
			$oid = $payloadJson->{"urn:esia:sbj_id"}; // ESIA_ID !
			$exp = $payloadJson->{"exp"}; 
			$nbf = $payloadJson->{"nbf"};

			$errstr = '';
			$errno = '';
			
			
			$fp = fsockopen('ssl://'.$serv_addr, $serv_port, $errno, $errstr, $timelimit);
			if (!$fp) {
				echo "$errstr ($errno)<br />\n";
			}
			else {
				$out = "GET /rs/prns/".$oid." HTTP/1.1\r\n";
				$out .= "Host: esia.gosuslugi.ru\r\n";
				$out .= "Authorization: Bearer ".$access_token."\r\n";
				$out .= "Accept: */*\r\n\r\n";
				fwrite($fp, $out);

				$server_answer = '';
				$server_header = '';
				$start = microtime(true);
				$header_flag = 1;

				while(!feof($fp) && (microtime(true) - $start) < $timelimit2)
				{
					$chunksize = '';
					if ($header_flag == 1)
					{
						$content = fgets($fp, 4096);
						if ($content === chr(13).chr(10))
						{
							$header_flag = 0;
						}
						else
							$server_header .= $content;
					}
					else
					{
						do {
							$chunksize .= fread($fp, 1);
						} while (strpos($chunksize, "\r\n") === false);
						$chunksize = (int)base_convert($chunksize, 16, 10);
						if ($chunksize === 0) {
							fread($fp, 2); // read trailing "\r\n"
							break;
						} else {
							$data = '';
							$datalen = 0;
							while ($datalen < $chunksize + 2) {
								$data .= fread($fp, $chunksize - $datalen + 2);
								$datalen = strlen($data);
							}
							$server_answer .= substr($data, 0, -2); // -2 to remove the "\r\n" before the next chunk
						}	

					}
				}

				fclose($fp);
				
				$obj = json_decode($server_answer);
				$USER_FIRST_NAME = $obj->{"firstName"};
				$USER_LAST_NAME = $obj->{"lastName"};
				$USER_MIDDLE_NAME = $obj->{"middleName"};
				$USER_TRUSTED = $obj->{"trusted"}; //<trusted> Цтип  учетной  записи  (подтверждена (УtrueФ) / не подтверждена (УfalseФ));
				$USER_BDATE = $obj->{"birthDate"};
				$USER_GENDER = $obj->{"gender"};
				$USER_SNILS = $obj->{"snils"};
				
				$USER_PHONE = '';
				$USER_MAIL = '';
				
				$errstr = '';
				$errno = '';

				$fp = fsockopen('ssl://'.$serv_addr, $serv_port, $errno, $errstr, $timelimit);
				if (!$fp) {
					echo "$errstr ($errno)<br />\n";
				}
				else {
					$out = "GET /rs/prns/$oid/ctts HTTP/1.1\r\n";
					$out .= "Host: esia.gosuslugi.ru\r\n";
					$out .= "Authorization: Bearer ".$access_token."\r\n";
					$out .= "Accept: */*\r\n\r\n";
					fwrite($fp, $out);

					$server_answer = '';
					$server_header = '';
					$start = microtime(true);
					$header_flag = 1;

					while(!feof($fp) && (microtime(true) - $start) < $timelimit2)
					{
						$chunksize = '';
						if ($header_flag == 1)
						{
							$content = fgets($fp, 4096);
							if ($content === chr(13).chr(10))
							{
								$header_flag = 0;
							}
							else
							$server_header .= $content;
						}
						else
						{
							do {
								$chunksize .= fread($fp, 1);
							} while (strpos($chunksize, "\r\n") === false);
							$chunksize = (int)base_convert($chunksize, 16, 10);

							if ($chunksize === 0) {
								fread($fp, 2); // read trailing "\r\n"
								break;
							} else {
								$data = '';
								$datalen = 0;
								while ($datalen < $chunksize + 2) {
								$data .= fread($fp, $chunksize - $datalen + 2);
								$datalen = strlen($data);
								}
								$server_answer .= substr($data, 0, -2); // -2 to remove the "\r\n" before the next chunk
							}
						}
					}

					$struct = json_decode($server_answer, true);
					$elements = $struct["elements"]; //контакты

					if (count($elements) > 0):
					
					// ѕереберем все контакты пользовател€
					/*
				 онтактные данные:
				<type> Ц тип записи, может иметь значени€:
				≠УMBTФ Ц мобильный телефон;
				≠УPHNФ Ц домашний телефон;
				≠УEMLФ Ц электронна€ почта;
				≠УCEMФ Цслужебна€ электронна€ почта.
				<vrfStu> Ц сведени€  о  Ђподтвержденностиї контактов, может иметь значени€:
				≠ УNOT_VERIFIEDФ Цне подтвержден;
				≠УVERIFIEDФ Цподтвержден.
				¬  насто€щее врем€  статус  УVERIFIEDФ может  быть  только  у  мобильного  телефона (УMBTФ)  и  адреса  электронной  почты (УEMLФ).
				<value> Цзначение контакта.
					*/
					foreach($elements as $val) {

						$Contact = '';
						$path = '';

						$Contact = $val; // https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/ctts/14216788
						$n = strpos($Contact, '/', 8);
						if($n !== false):
							$path = substr($Contact, $n); // /rs/prns/1000299686/ctts/14216788
						endif;

						if ($path !== ''):
							$out = "GET $path HTTP/1.1\r\n";
							$out .= "Host: esia.gosuslugi.ru\r\n";
							$out .= "Authorization: Bearer ".$access_token."\r\n";
							$out .= "Accept: */*\r\n\r\n";
							fwrite($fp, $out);
							$server_answer = '';
							$server_header = '';
							$start = microtime(true);
							$header_flag = 1;

							while(!feof($fp) && (microtime(true) - $start) < $timelimit2)
							{
								$chunksize = '';
								if ($header_flag == 1)
								{
									$content = fgets($fp, 4096);

									if ($content === chr(13).chr(10))
									{$header_flag = 0;
									}
									else
									$server_header .= $content;

								}
								else
								{
									do {
										$chunksize .= fread($fp, 1);
									} while (strpos($chunksize, "\r\n") === false);
									$chunksize = (int)base_convert($chunksize, 16, 10);

									if ($chunksize === 0) {
										fread($fp, 2); // read trailing "\r\n"
										break;
									} else {
										$data = '';
										$datalen = 0;
										while ($datalen < $chunksize + 2) {
										$data .= fread($fp, $chunksize - $datalen + 2);
										$datalen = strlen($data);
										}
										$server_answer .= substr($data, 0, -2); // -2 to remove the "\r\n" before the next chunk
									}
								}
							}
						$contacts_arr = json_decode($server_answer, true);
						
						if($contacts_arr['type'] == 'EML' && $contacts_arr['vrfStu'] == 'VERIFIED')
							$USER_MAIL = $contacts_arr['value'];
						elseif($contacts_arr['type'] == 'MBT' && $contacts_arr['vrfStu'] == 'VERIFIED')
							$USER_PHONE = $contacts_arr['value'];
						/*
						echo"<pre>";
						print_r($contacts_arr);
						echo"</pre>";
						*/
						endif;
					}

					endif;
					fclose($fp);
				} //end while
				
				$USER_ARR['lastName'] = $USER_LAST_NAME;
				$USER_ARR['firstName'] = $USER_FIRST_NAME;
				$USER_ARR['middleName'] = $USER_MIDDLE_NAME;
				$USER_ARR['birthDate'] = $USER_BDATE;
				$USER_ARR['gender'] = $USER_GENDER;
				$USER_ARR['personEMail'] = $USER_MAIL;
				$USER_ARR['personMobilePhone'] = $USER_PHONE;
				$USER_ARR['trusted'] = $USER_TRUSTED;
				$USER_ARR['userId'] = $oid;
				$USER_ARR['personSNILS'] = $USER_SNILS;
				
				$this->attribute = $USER_ARR;
			}
		}
	}
	
	public function getAttribute()
	{
		return $this->attribute;
	}
	
	// јвторизаци€ пользовател€
	public function auth()
	{		
		if ($this->search_user() !== false)
		{
			$user = new CUser;
			$user->Authorize($this->user_id);
			$this->updateAttribute();			
			return true;
		}
		
		return false;
	}
	
	// ѕроверка учЄтной записи пользовател€ ≈—»ј
	public function check_account()
	{		
		if (isset( $this->attribute['trusted'] ) && $this->attribute['trusted'] != 1)
		{
			return 'ƒл€ доступа вам необходимо пройти <a href="https://esia.gosuslugi.ru/validate">процедуру проверки</a> своих данных. ≈сли ваши личные данные только что прошли проверку, 
			то вам нужно войти в систему повторно.';
		}
		
		return true;
	}
	
	// ѕоиск пользовател€ в системе
	private function search_user()
	{
		$this->user_id = 0;	
		
		// ѕоиск по номеру пользовател€ в ≈—»ј
		$userId = isset( $this->attribute['userId'] ) ?  trim( $this->attribute['userId'] ) : '';
		if ($userId != '')
		{
			$rsUsers = CUser::GetList( ($by="id"), ($order="desc"), Array("=UF_ESIA_ID" => $userId) );
			if ( $arUser = $rsUsers->GetNext() ) 
			{
				$this->user_id = $arUser['ID'];
				return $this->user_id;
			}
		}
		
		// ѕоиск по номеру —Ќ»Ћ—
		$personSNILS = isset( $this->attribute['personSNILS'] ) ?  trim( $this->attribute['personSNILS'] ) : '';		
		if ( !preg_match("/^[0-9]{3}\-[0-9]{3}\-[0-9]{3}\s[0-9]{2}$/iu", $personSNILS) ) $personSNILS = '';
		$personSNILS = str_replace('-', '', $personSNILS);
		$personSNILS = str_replace(' ', '', $personSNILS);
		if ($personSNILS != '')
		{
			$rsUsers = CUser::GetList( ($by="id"), ($order="desc"), Array("=UF_SNILS" => $personSNILS) ); 
			if ( $arUser = $rsUsers->GetNext() ) 
			{
				$this->user_id = $arUser['ID'];
				return $this->user_id;
			}
		}
		
		// ѕоиск EMAIL адресу
		$personEMail = isset( $this->attribute['personEMail'] ) ?  trim( $this->attribute['personEMail'] ) : '';		
		if ($personEMail != '')
		{
			$rsUsers = CUser::GetList( ($by="id"), ($order="desc"), Array("=EMAIL" => $personEMail) ); 
			if ( $arUser = $rsUsers->GetNext() ) 
			{
				$this->user_id = $arUser['ID'];
				return $this->user_id;
			}
		}
		
		return false;
	}
	
	// ќбновлени€ атрибутов пользовател€
	private function updateAttribute()
	{
		$userId = isset( $this->attribute['userId'] ) ?  trim( $this->attribute['userId'] ) : 0;
		$lastName = isset( $this->attribute['lastName'] ) ?  trim( $this->attribute['lastName'] ) : '';		
		$firstName = isset( $this->attribute['firstName'] ) ?  trim( $this->attribute['firstName'] ) : '';		
		$middleName = isset( $this->attribute['middleName'] ) ?  trim( $this->attribute['middleName'] ) : '';	
		$birthDate = isset( $this->attribute['birthDate'] ) ?  trim( $this->attribute['birthDate'] ) : '';	
		$gender = isset( $this->attribute['gender'] ) ?  trim( $this->attribute['gender'] ) : '';		
		if ($gender == 'MALE') $gender = 'M'; else $gender = 'F';
		$personMobilePhone = isset( $this->attribute['personMobilePhone'] ) ?  trim( $this->attribute['personMobilePhone'] ) : '';
		$personSNILS = isset( $this->attribute['personSNILS'] ) ?  trim( $this->attribute['personSNILS'] ) : '';		
		$personEMail = isset( $this->attribute['personEMail'] ) ?  trim( $this->attribute['personEMail'] ) : '';
		if ($personEMail == '') $personEMail = 'esia_' . $userId . '@MY_SECRET_SITE.ru';
		
		$assuranceLevel = isset( $this->attribute['trusted'] ) ?  trim( $this->attribute['trusted'] ) : '';

		$arFields = Array(
		  "NAME"              => $firstName,
		  "LAST_NAME"         => $lastName,
		  "SECOND_NAME"       => $middleName,
		  "PERSONAL_GENDER"   => $gender,
		  "PERSONAL_BIRTHDAY" => str_replace('-', '.', substr($birthDate, 0, strpos($birthDate, ' '))),
		  "UF_SNILS"    	  => $personSNILS,
		  "UF_ESIA_ID"        => $userId,
		  "UF_ATTRIBUTE_ESIA" => json_encode($this->attribute),
		);
		
		$user = new CUser;
		$user->Update($this->user_id, $arFields);
	}
	
}
?>