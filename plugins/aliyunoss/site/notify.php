<?php
/**
 * 回调通知
 */
require_once "./config.php";
require_once('./ossUploader.php');
dump_log($_SERVER);

dump_log($_POST);

// 1.获取OSS的签名header和公钥url header
$authorizationBase64    = "";
$pubKeyUrlBase64        = "";
/*
 * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
 * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
    RewriteEngine On
    RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
 * */
if (isset($_SERVER['HTTP_AUTHORIZATION'])){
    $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
}
if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])){
    $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
}
if ($authorizationBase64 == '' || $pubKeyUrlBase64 == ''){
    header("http/1.1 403 Forbidden");exit();
}
// 2.获取OSS的签名
$authorization  = base64_decode($authorizationBase64);
// 3.获取公钥
$pubKeyUrl      = base64_decode($pubKeyUrlBase64);

dump_log($authorization);

dump_log($pubKeyUrl);


$ch             = curl_init();
curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
$pubKey         = curl_exec($ch);
if ($pubKey == ""){
    header("http/1.1 403 Forbidden");exit();
}
// 4.获取回调body
$body = file_get_contents('php://input');


dump_log($body);
// 5.拼接待签名字符串
$authStr    = '';
$path       = $_SERVER['REQUEST_URI'];
$pos        = strpos($path, '?');
if ($pos === false){
    $authStr = urldecode($path)."\n".$body;
}else{
    $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
}
// 6.验证签名
$ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
if ($ok == 1){

    success(save_file($_POST));
}else{
    header("http/1.1 403 Forbidden");
    exit();
}

/**
 * 回调响应
 */
function success($data)
{
    header("Content-Type: application/json");
    echo json_encode($data);die();
}

/**
 * 回调处理
 * @param $data
 */
function save_file($data)
{
    $user_id   = $data['user'];
    $filename  = $data['filename'];
    $object    = $data['objectName'];
    $file_size = $data['size'];
    $file_mime = $data['mimeType'];
    $height    = $data['height'];
    $width     = $data['width'];
    $width     = $data['width'];

    $ou = new ossUploader();
    $info = $ou->ossPost($data);
    $info = [
        'info'=>$info,
    ];
    return $info;
}

/**
 * @param $data
 */
function dump_log($data)
{
    $logDir = "./logs/";
    if(!is_dir($logDir)){
        mkdir($logDir, 0777,true);
    }else{
        chmod($logDir, 0777);
    }
    $file = "./logs/".date("Y-m-d").".notify-log.log";
    file_put_contents($file,date("Y-m-d H:i:s").":【".json_encode($data)."】\n",FILE_APPEND);
}
