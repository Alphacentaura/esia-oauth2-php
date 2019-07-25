<?php
function generate_uuid() {
return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
mt_rand( 0, 0xffff ),
mt_rand( 0, 0x0fff ) | 0x4000,
mt_rand( 0, 0x3fff ) | 0x8000,
mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
);
}
function generateFileName() {
return sprintf( '%04x%04x-%04x',
mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
mt_rand( 0, 0xffff )
);
}

function urlsafe_b64encode($string) {
$data = base64_encode($string);
$data = str_replace(array('+','/','='),array('-','_',''),$data);
return $data;
}

function base64UrlSafeDecode($string) //encode наоборот
{
$base64 = strtr($string, '-', '+');
$base64 = strtr($base64, '_', '/');
//? есть ещё подстановка знаков "=" в конец
return base64_decode($base64);
}
?>