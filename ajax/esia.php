<?php
// ВХОД ЧЕРЕЗ ЕСИА
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/include/functions.php");
require($_SERVER["DOCUMENT_ROOT"]."/esia2/include/functions.php");
CModule::IncludeModule("iblock");

$action = isset( $_POST['action'] ) ? trim( $_POST['action'] ) : '';

// Регистрации
if ($action == 'reg')
{	
	$authEsia = new AuthEsia();
	if ( ($res = $authEsia->check_account()) !== true )
	{
		echo json_encode( array('answer' => 'auth', 'text' => 'Для доступа вам необходимо пройти <a href="https://esia.gosuslugi.ru/validate">процедуру проверки</a> своих данных. Если ваши личные данные только что прошли проверку, 
			то вам нужно войти в систему повторно.'));
		exit;
	}
	elseif ( $authEsia->auth() )
	{
		echo json_encode( array('answer' => 'auth', 'text' => ''));
		exit;
	}
	
	//$attribute = $authEsia->getAttribute();
	$attribute['userId'] = $_SESSION['userId'];
	$attribute['lastName'] = $_SESSION['lastName'];
	$attribute['firstName'] = $_SESSION['firstName'];
	$attribute['middleName'] = $_SESSION['middleName'];
	$attribute['birthDate'] = $_SESSION['birthDate'];
	$attribute['gender'] = $_SESSION['gender'];
	$attribute['personEMail'] = $_SESSION['personEMail'];
	$attribute['personMobilePhone'] = $_SESSION['personMobilePhone'];
	$attribute['personSNILS'] = $_SESSION['personSNILS'];
	$attribute['assuranceLevel'] = $_SESSION['assuranceLevel'];
	
	
	
	$error = array();	
	$userId = isset( $attribute['userId'] ) ?  trim( $attribute['userId'] ) : 0;
	$lastName = isset( $attribute['lastName'] ) ?  trim( $attribute['lastName'] ) : '';		
	$firstName= isset( $attribute['firstName'] ) ?  trim( $attribute['firstName'] ) : '';		
	$middleName = isset( $attribute['middleName'] ) ?  trim( $attribute['middleName'] ) : '';	
	$birthDate = isset( $attribute['birthDate'] ) ?  trim( $attribute['birthDate'] ) : '';	
	$gender = isset( $attribute['gender'] ) ?  trim( $attribute['gender'] ) : '';
	if ($gender == 'MALE') $gender = 'M'; else $gender = 'F';	
	$personEMail = isset( $attribute['personEMail'] ) ?  trim( $attribute['personEMail'] ) : '';
	$personMobilePhone = isset( $attribute['personMobilePhone'] ) ?  trim( $attribute['personMobilePhone'] ) : '';
	$personSNILS = isset( $attribute['personSNILS'] ) ?  trim( $attribute['personSNILS'] ) : '';
	if ( !preg_match("/^[0-9]{3}\-[0-9]{3}\-[0-9]{3}\s[0-9]{2}$/iu", $personSNILS) ) $personSNILS = '';
	
	$assuranceLevel = isset( $attribute['assuranceLevel'] ) ?  trim( $attribute['assuranceLevel'] ) : '';

	$u_mo = isset( $_POST['u_mo'] ) ? intval( $_POST['u_mo'] ) : 0;
	if ($u_mo == 0) $error[] = array('field' => 'u_mo', 'text' => 'Поле обязательно для заполения');
		
	if (count($error) == 0)
	{			
		if ($personEMail == '') $personEMail = 'esia_' . $userId . '@open.krasnodar.ru';
		if ($personMobilePhone != '')
		{
			if ( preg_match('/^(8|\+7|7)/iu', $personMobilePhone) )
			{
				$personMobilePhone = preg_replace('/^(8|\+7|7)/iu', '', $personMobilePhone); 		
				$personMobilePhone = preg_replace('/[^0-9]/iu', '', $personMobilePhone);						
				$personMobilePhone = '+7' . ' ' . '(' . substr($personMobilePhone, 0, 3) . ')' . ' ' . substr($personMobilePhone, 3, 3) . '-' . substr($personMobilePhone, 6, 2) . '-' . substr($personMobilePhone, 8, 2);
			}	
		}
		
		$user = new CUser;
		$arFields = Array(
			  "NAME"              => $firstName,
			  "LAST_NAME"         => $lastName,
			  "SECOND_NAME"       => $middleName,
			  "EMAIL"             => $personEMail,
			  "LOGIN"             => $personEMail,
			  "LID"               => "ru",
			  "ACTIVE"            => "Y",
			  "PERSONAL_GENDER"   => $gender,
			  "GROUP_ID"          => array(2, 13),
			  "PASSWORD"          => md5($personEMail),
			  "CONFIRM_PASSWORD"  => md5($personEMail),
			  "PERSONAL_PHONE"    => $personMobilePhone,
			  "PERSONAL_BIRTHDAY" => str_replace('-', '.', substr($birthDate, 0, strpos($birthDate, ' '))),
			  "UF_DEPARTMENT"	  => 1,
			  "UF_SNILS"    	  => $personSNILS,
			  "UF_USER_MO"    	  => $u_mo,
			  "UF_USER_SIGNUP"    => 'Y',
			  "UF_ESIA_ID"        => $userId,
			  "UF_ATTRIBUTE_ESIA" => json_encode($attribute),
			  "UF_REG_FROM_ESIA" => 1,
			);

		$ID = $user->Add($arFields);
		if (intval($ID) > 0)
		{
			// Авторизуем пользователя
			$user->Authorize($ID);
			
			echo json_encode( array('answer' => 'success', 'text' => 'Вы успешно зарегестрированы и авторизованы в системе.') );
			exit;
		}
		else
		{
			echo json_encode( array('answer' => 'fatal', 'text' => $user->LAST_ERROR));
			exit;
		}
	}
	
	echo json_encode( array('answer' => 'error', 'text' => $error));
	exit;
}
?>