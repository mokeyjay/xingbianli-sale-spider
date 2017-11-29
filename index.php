<?php
// 自定义配置
define('DECLINE', 0.1); // 降幅阈值。即商品减免金额(元)>=此值时触发推送
// 猩便利接口所需数据配置
define('XBL_ID', 'abcd9e80-ab4d-a66d-a7cd-abcd377abcdb'); // 货架id
define('TOKEN', ''); // Cookie里的token值
define('XBL_UUID', ''); // Cookie里的UUID值
// Server酱微信推送url
define('SC_URL', 'http://sc.ftqq.com/XXX.send');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/ServerChan.php';
use Curl\Curl;

// 初始化Curl
$curl = new Curl();
$curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36 MicroMessenger/6.5.2.501 NetType/WIFI WindowsWechat QBCore/3.43.691.400 QQBrowser/9.0.2524.400'); // 微信PC版浏览器UA
$curl->setCookies([
    'token' => TOKEN,
    'xbl_uuid' => XBL_UUID,
]);
$curl->get('https://www.xingbianli.com/openrack/'. XBL_ID .'/commodityList');

// 解析接口返回值
$error = '';
if ($curl->error) {
    $error = 'cURL错误: #' . $curl->errorCode . ' - ' . $curl->errorMessage;
} else {
    if($curl->response->code != 200){
        $error = '接口错误: #' . $curl->response->code . ' - ' . $curl->response->msg;
    } else {
        $data = $curl->response->data;
        $curl->close();
    }
}
if($error){
    ServerChan::send($error);
    die($error);
}

/**
 * 分析特价商品降幅
 */
$discount = []; // [特价商品id => 特价价格] 数组
foreach ($data->discount as $v){
    $discount[$v->commodityId] = $v->currentPrice;
}

$commodity_list = []; // [特价商品id => [商品信息]] 数组
$diff_list = []; // [特价商品id => 降幅] 数组

foreach ($data->commodityList as $commodity){
    if(isset($discount[$commodity->commodityId])){
        $commodity = (array)$commodity;
        $diff_price = $commodity['basicPrice'] - $discount[$commodity['commodityId']];

        // 只收满足阈值的特价商品
        if($diff_list >= DECLINE){
            $commodity_list[$commodity['commodityId']] = $commodity;
            $diff_list[$commodity['commodityId']] = $diff_price;
        }
    }
}

// 重新排序，降幅越大越靠前
arsort($diff_list);
$push_list = [];
foreach ($diff_list as $cid => $diff){
    $push_list[$cid] = $commodity_list[$cid];
}

/**
 * 推送
 */
if(!empty($push_list)){
    $msg = '';
    foreach ($push_list as $cid => $commodity){
        $msg .= '![](' . $commodity['picUrl'] . ')' . "\n\n";
        $msg .= '#### ¥' . $discount[$cid] . ' - ' . $commodity['name'] . "\n\n";
        $msg .= '-' . $diff_list[$cid] . '元   ~~原价' . $commodity['basicPrice'] .'~~';
        $msg .= "\n\n" . '-----' . "\n\n";
    }
    echo $msg;
    ServerChan::send($msg);
}