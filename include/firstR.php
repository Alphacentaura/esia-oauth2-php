<?php
require ("func.php");

////////////////
$scope = 'fullname email mobile birthdate gender snils id_doc';
$timestamp = date('Y.m.d H:i:s O');
$clientId = "OPKK02231";
$state = generate_uuid();

$data = $scope.$timestamp.$clientId.$state;

$msgFile = $_SERVER["DOCUMENT_ROOT"].'/1/'.generateFileName();
$fp = fopen($msgFile, "w");
fwrite($fp, $data);
fclose($fp);

$pathPrive = "file://sever.key";
$pathOpen = "file://sever.crt";
$signFile = $_SERVER["DOCUMENT_ROOT"].'/1/'.generateFileName();

// encrypt it
$Zashifrovano = False;
/**/ if (openssl_pkcs7_sign($msgFile, $signFile, $pathOpen,

array($pathPrive, "ertdf"), //qwerty123!
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

///////////////

header("Location: https://esia.gosuslugi.ru/aas/oauth2/ac?timestamp=".urlencode($timestamp)."&client_id=$clientId&client_secret=".urlencode($sign)."&redirect_uri=".urlencode("https://MY_SECRET_SITE.ru/1/redirectpage.php")."&scope=$scope&response_type=code&state=$state&access_type=online");

?>
