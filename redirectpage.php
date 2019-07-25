<?php
require ("funcfunc20171012.php");
$Domain = 'MY_SECRET_SITE.ru';

$codeAuth = '';
if (isset($_GET['code'])) {
    $codeAuth = $_GET['code'];
}
$state = ''; //нужно сравнивать отправленный state с полученным.
$stateOld = '';
if (isset($_GET['state'])) {
    $stateOld = $_GET['state'];
}

if ($codeAuth === '' or $stateOld === '') {
    header("Location: https://MY_SECRET_SITE.ru/");
    return;
}
/* получение Токена .(+сохранение ...)*/

//https://esia-portal1.test.gosuslugi.ru/aas/oauth2/te

/* $QUERY_STRING = $_SERVER['QUERY_STRING']; //получаем get параметры
$request_params = "";
parse_str($QUERY_STRING, $request_params); //получаем массив(ключ-знач) из параметров

$codeAuth = $request_params['code'];*/

//************************************************ЗАПРОС НА все нужные СКОУПы *****************************************

//Post-запрос
$serv_addr = 'esia.gosuslugi.ru';
$serv_port = 443;
$serv_page = 'aas/oauth2/te';
$timelimit = 20;
$errstr = '';
$errno = '';

//$scope = 'openid';
$scope = 'fullname email mobile birthdate gender snils id_doc';
//$scope = 'fullname';
//$scope = 'usr_org';
//$scope = 'snils';
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

$Zashifrovano = False;
if (openssl_pkcs7_sign($msgFile, $signFile, $pathOpen,

    array($pathPrive, "qwerty123!"),
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

// Передаваемые POST переменные в формате: название переменной => значение
$post_data = array('client_id' => $clientId,
    'code' => $codeAuth,
    'grant_type' => 'authorization_code',
    'client_secret' => $sign,
    'state' => $state,
    'redirect_uri' => "https://MY_SECRET_SITE.ru/esia2/redirectpage.php",
    'scope' => $scope,
    'timestamp' => $timestamp,
    'token_type' => 'Bearer');
// Генерируем строку с POST запросом
$post_data_text = '';
foreach ($post_data AS $key => $val)
    $post_data_text .= $key.'='.urlencode($val).'&'; //может быть здесь не нужно URL-кодировать

// Убираем последний символ & из строки $post_data_text
$post_data_text = substr($post_data_text, 0, -1);


$headers = array('POST /'.$serv_page.' HTTP/1.1',
    'Host: '.$serv_addr,
    'Content-type: application/x-www-form-urlencoded',
    'Content-length: '.strlen($post_data_text),
    'Accept: */*',
//'Accept-Encoding: gzip, deflate',
    '');

// Создание строки заголовков
$headers_txt = '';
foreach ($headers AS $val)
{
    $headers_txt .= $val.chr(13).chr(10);
}

// Создание общего запроса (заголовки и тело запроса)
// chr(13).chr(10) равно "\r\n" - перевод каретки
$request_body = $headers_txt.$post_data_text.chr(13).chr(10).chr(13).chr(10);

// Открытие сокета
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
// проверка полученного ответа
//..

//пример заголовков ответа $server_header
/*
HTTP/1.1 200 OK
Server: nginx
Date: Thu, 28 Dec 2017 13:22:16 GMT
Content-Type: application/json;charset=UTF-8
Transfer-Encoding: chunked
Connection: keep-alive
Vary: Accept-Encoding
com.coradiant.appvis: nulltid=1514467336504;4696328222695907644
X-XSS-Protection: 1; mode=block;
X-Content-Type-Options: nosniff
*/
//echo $server_answer;
// {"state":"cdaeec62-a717-43f7-95ce-f34916644762","token_type":"Bearer","expires_in":3600,"refresh_token":"18e55fac-69b6-474c-a613-d0aac20a79e0","id_token":"eyJhbGciOiJSUzI1NiIsInNidCI6ImlkIiwidHlwIjoiSldUIiwidmVyIjowfQ.eyJhdXRoX3RpbWUiOjE1MTQ0NjczMzU5MjksImV4cCI6MTUxNDQ3ODEzNiwic3ViIjoxMDAwMjk5Njg2LCJhdWQiOiJDbGllbnRJZE8wMSIsImlzcyI6Imh0dHA6XC9cL2VzaWEuZ29zdXNsdWdpLnJ1XC8iLCJuYmYiOjE1MTQ0NjczMzYsInVybjplc2lhOnNpZCI6ImQxNGQzY2ExOGFlNTVkYWUzODRjOTljYTQ3MmZhODkyZjNmMmVjODEyNWRlZGE2ZjdlNzNkOWZkZjRlMTVjMWEiLCJ1cm46ZXNpYTpzYmoiOnsidXJuOmVzaWE6c2JqOm5hbSI6Ik9JRC4xMDAwMjk5Njg2IiwidXJuOmVzaWE6c2JqOm9pZCI6MTAwMDI5OTY4NiwidXJuOmVzaWE6c2JqOnR5cCI6IlAiLCJ1cm46ZXNpYTpzYmo6aXNfdHJ1Ijp0cnVlfSwidXJuOmVzaWE6YW1kIjoiUFdEIiwiaWF0IjoxNTE0NDY3MzM2LCJhbXIiOiJQV0QifQ.cE8GFbwjrbEn6xEwDngL5b5pJ_Rw3hDAA7LqShYAL6TG18ps_35xfFhPizpRt8aauMx7C4OsrrnqdQFnyhOTBt-JwqwimJcfqRFWz0Fv4VCmjIvGksgde7LOE18PfkaOqMQgMzM-AFpJ1AKSA51Srk6_5BD3YEh9cvsTb5ZfxCtTdtQeIV3OmBy9ymclFd_0AM9KBHm6A6YYG748hEAH1Sv-Eb2WyG-kIuY-V8eFNk_eMwj9e2prn2LX8Lq6DiUcAwRWPe-AZE4As1hp-HlfJMO_AFz2FHW6MbIGCIdfhR0zMp9_nVUcVOK94fqddY4xB_peGh9GJI1Eg89pKS1Ygg","access_token":"eyJhbGciOiJSUzI1NiIsInNidCI6ImFjY2VzcyIsInR5cCI6IkpXVCIsInZlciI6MX0.eyJleHAiOjE1MTQ0NzA5MzYsInNjb3BlIjoidXNyX29yZz9vaWQ9MTAwMDI5OTY4NiBvcGVuaWQgZnVsbG5hbWU_b2lkPTEwMDAyOTk2ODYiLCJpc3MiOiJodHRwOlwvXC9lc2lhLmdvc3VzbHVnaS5ydVwvIiwibmJmIjoxNTE0NDY3MzM2LCJ1cm46ZXNpYTpzaWQiOiJkMTRkM2NhMThhZTU1ZGFlMzg0Yzk5Y2E0NzJmYTg5MmYzZjJlYzgxMjVkZWRhNmY3ZTczZDlmZGY0ZTE1YzFhIiwidXJuOmVzaWE6c2JqX2lkIjoxMDAwMjk5Njg2LCJjbGllbnRfaWQiOiJGSVRPMDEiLCJpYXQiOjE1MTQ0NjczMzZ9.KvDuV7MqF9p-JXvPud98_jU0YEMaLGdNoE_r0sNr-vzO2SFDrrlq6YCoJRwEmkmajnAjD8jBx_uSLdPGc1ryS_FuO-0csL-Nr-UYIotap_JsNooAy_7V5Z1iji6JbLHCX5xUvop62pgEtDXs_yTcex7T5Y4cHbtY4M7QVsJfA_DB5Ark3q2L6mVuH3FAYPN7MRWviR8IJSXR8dAG4i9CB6BxaWj_XEaUVGNvKzWVoUyKMLhboVXHqUIhORBnRN_-ix1oL8Uv7UvCDXZEZZwdM7G70gDdT3tkSGbQtHic7q5qoF6jVI716gMjkT721ykV46Wn9DCKT0wuHyqz5eteKw"}
$answerJson = json_decode($server_answer);

//print_r($answerJson);

$access_token = $answerJson->{"access_token"};

//**********закомментировать н ******************
$PartsOfAnswer = explode('.', $access_token);

$header = base64UrlSafeDecode($PartsOfAnswer[0]);
$payload = base64UrlSafeDecode($PartsOfAnswer[1]);
$signature = $PartsOfAnswer[2];

$payloadJson = json_decode($payload);
/*
echo"<pre>";
print_r($payloadJson);
echo"</pre>";
*/
$oid = $payloadJson->{"urn:esia:sbj_id"}; // ESIA_ID !!!!!!!!!!!
$exp = $payloadJson->{"exp"}; //время прекращения действия маркера в секундах с 1 января 1970 г. 00:00:00 GMT
//date("Y-m-d H:i:s O", $exp) вернет 2017-12-28 17:52:14 +0400
$nbf = $payloadJson->{"nbf"}; //время начала действия маркера в секундах с 1 января 1970 г. 00:00:00 GMT

//**********закомментировать кон ******************

//****************************************получение полного имени пользователя ********************************

$serv_addr = 'esia.gosuslugi.ru';
$serv_port = 443;
$serv_page = 'aas/oauth2/te';
$timelimit = 20;
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
    //$out .= "Accept: application/json; schema=\"https://esia-portal1.test.gosuslugi.ru/rs/model/prn/Person-3\"\r\n\r\n";
    //$out .= "Accept: application/json; schema=\"https://esia-portal1.test.gosuslugi.ru/rs/model/orgs/Organizations-1\"\r\n\r\n";
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
    // проверка полученного ответа

    //пример заголовков ответа:
    /*
    HTTP/1.1 200 OK
    Server: nginx
    Date: Thu, 28 Dec 2017 13:02:14 GMT
    Content-Type: application/json; schema="https://esia-portal1.test.gosuslugi.ru/rs/model/prn/Person-3"
    Transfer-Encoding: chunked
    Connection: keep-alive
    Location: https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/>;rel=parent;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/prns/Persons-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/prmns>;rel=permissions;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/prmns/PersonPermissions-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/ctx>;rel=context;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/ctx/RegContext-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/events>;rel=events;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/events/AuditEvents-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/reqs>;rel=requests;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/reqs/Requests-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/sec>;rel=sec;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/sec/SecuritySettings-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/addrs>;rel=addresses;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/addrs/Addresses-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/roles>;rel=roles;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/roles/Roles-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/ctts>;rel=contacts;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/ctts/Contacts-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/docs>;rel=documents;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/docs/Documents-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/vhls>;rel=vehicles;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/vhls/Vehicles-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/kids>;rel=kids;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/kids/Kids-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/trmnls>;rel=terminals;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/trmnls/Terminals-1"
    Link: <https://esia-portal1.test.gosuslugi.ru:443/rs/prns/1000299686/orgs>;rel=organizations;schema="https://esia-portal1.test.gosuslugi.ru/rs/model/orgs/Organizations-1"
    com.coradiant.appvis: nulltid=1514466134701;-810712349028523703
    ETag: B2139609D990C4F140CBF352217DB15CF3BFE79C
    Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0
    Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0
    */
    // пример тела ответа:
    // {"stateFacts":["EntityRoot"],"eTag":"B2139609D990C4F140CBF352217DB15CF3BFE79C","firstName":"Сергей","lastName":"Пинин","middleName":"Петрович","trusted":true,"updatedOn":1511162767,"status":"REGISTERED","verifying":false,"rIdDoc":42587,"containsUpCfmCode":false}

    $obj = json_decode($server_answer);
    $USER_FIRST_NAME = $obj->{"firstName"};
    $USER_LAST_NAME = $obj->{"lastName"};
    $USER_MIDDLE_NAME = $obj->{"middleName"};
    $USER_TRUSTED = $obj->{"trusted"}; //<trusted> –тип  учетной  записи  (подтверждена (“true”) / не подтверждена (“false”));
    $USER_BDATE = $obj->{"birthDate"};
    $USER_GENDER = $obj->{"gender"};
    $USER_SNILS = $obj->{"snils"};
    //print_r($obj);
} //end while

//****************************************(1. доступ к перечню контактов "ctts" 2. получение контактов)********************************

$USER_PHONE = '';
$USER_MAIL = '';

$serv_addr = 'esia.gosuslugi.ru';
$serv_port = 443;
$serv_page = 'aas/oauth2/te';
$timelimit = 20;
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

        // Переберем все контакты пользователя
        /*
    Контактные данные:
    <type> – тип записи, может иметь значения:
    ­“MBT” – мобильный телефон;
    ­“PHN” – домашний телефон;
    ­“EML” – электронная почта;
    ­“CEM” –служебная электронная почта.
    <vrfStu> – сведения  о  «подтвержденности» контактов, может иметь значения:
    ­ “NOT_VERIFIED” –не подтвержден;
    ­“VERIFIED” –подтвержден.
    В  настоящее время  статус  “VERIFIED” может  быть  только  у  мобильного  телефона (“MBT”)  и  адреса  электронной  почты (“EML”).
    <value> –значение контакта.
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

//*************************scope openid fullname usr_org email****************************************************************************************

echo "ESIA_ID = ".$oid."<br/>";
echo "USER_FIRST_NAME = ".$USER_FIRST_NAME."<br/>";
echo "USER_LAST_NAME = ".$USER_LAST_NAME."<br/>";
echo "USER_MIDDLE_NAME = ".$USER_MIDDLE_NAME."<br/>";
echo "USER_TRUSTED = ".$USER_TRUSTED."<br/>";
echo "USER_BDATE = ".$USER_BDATE."<br/>";
echo "USER_GENDER = ".$USER_GENDER."<br/>";
echo "USER_SNILS = ".$USER_SNILS."<br/>";
echo "USER_MAIL = ".$USER_MAIL."<br/>";
echo "USER_PHONE = ".$USER_PHONE."<br/>";

//********дальше после проверки полученных данных что-нибудь делаем*******************************************

?>