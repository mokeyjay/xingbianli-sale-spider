<?php
use Curl\Curl;

/**
 * Server酱工具类
 * Class ServerChan
 * @link    http://sc.ftqq.com/
 */
class ServerChan
{
    /**
     * 推送消息到微信
     * @param string $message 两个空行才是换行
     * @return bool
     */
    public static function send($message)
    {
        $c = new Curl();
        $json = $c->get(SC_URL, [
            'desp' => $message,
            'text' => '炉石科技猩便利特价推送',
        ]);
        $json = json_decode($json, TRUE);
        if ($json === NULL){
            return 'Server酱返回格式不正确';
        } else if ($json['errno'] != 0){
            return 'Server酱返回错误：' . $json['errmsg'] . ' - ' . $json['dataset'];
        } else {
            return TRUE;
        }
    }
}