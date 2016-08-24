<?php
require "./config.php";
/**
 * @param $time
 * @return string
 */
function gmt_iso8601($time) {
    $dtStr = date("c", $time);
    $date_time = new DateTime($dtStr);
    $expiration = $date_time->format(DateTime::ISO8601);
    $pos = strpos($expiration, '+');
    $expiration = substr($expiration, 0, $pos);
    return $expiration."Z";
}
$callback_param = array(
    'callbackUrl'       => ALI_OSS_CALLBACK_URL,
    'callbackBody'      => ALI_OSS_CALLBACK_BODY,
    'callbackBodyType'  => ALI_OSS_CALLBACK_BODY_TYPE
);
$expire_time            = time()  + intval(ALI_OSS_EXPIRE);
$policy_param = array(
    'expiration' => gmt_iso8601($expire_time),
    'conditions' => array(
        array( '0' => 'content-length-range', '1' => 0, '2' => ALI_OSS_MAX_UPLOAD_BYTES ),
        array( '0' => 'starts-with', '1' => '$key', '2' => ALI_OSS_UPLOAD_DIR ),
    ),
);
$base64_callback_body   = base64_encode( json_encode( $callback_param ) );
$base64_policy          = base64_encode( json_encode( $policy_param ) );
$signature              = base64_encode( hash_hmac('sha1', $base64_policy, ALI_OSS_ACCESS_KEY_SECRET, true) );
$response = array();
$response['accessid']   = ALI_OSS_ACCESS_KEY_ID;    //指的用户请求的accessid,注意单知道accessid, 对数据不会有影响。
$response['host']       = ALI_OSS_HOST;             //指的是用户要往哪个域名发往上传请求
$response['policy']     = $base64_policy;           //指的是用户表单上传的策略policy, 是经过base64编码过的字符串
$response['signature']  = $signature;               //是对上述第三个变量policy签名后的字符串
$response['expire']     = $expire_time;             //指的是当前上传策略失效时间
$response['callback']   = $base64_callback_body;
$response['dir']        = ALI_OSS_UPLOAD_DIR;       //这个参数是设置用户上传指定的前缀
echo json_encode($response);die();