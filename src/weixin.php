<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/28/17
 * Time: 1:23 PM
 */

require_once 'config.inc.php';
global $_CONFIG;

$app = $_CONFIG['app'];
$appkey = $_CONFIG['appkey'];

function httpGet($url)
{
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_ENCODING ,"");

    $output=curl_exec($ch);

    curl_close($ch);
    return $output;
}

function httpPost($url,$params)
{
    $postData = '';
    //create name value pairs seperated by &
    foreach($params as $k => $v)
    {
        $postData .= $k . '='.$v.'&';
    }
    $postData = rtrim($postData, '&');

    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_ENCODING ,"");
    curl_setopt($ch,CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, count($postData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $output=curl_exec($ch);

    curl_close($ch);
    return $output;
}

function sendLectureByWeixin($lecture) {
    global $app, $appkey;
    $timestamp = httpGet('http://weixin.cic.tsinghua.edu.cn/cop/getTimestamp.php');
    $key = md5($app . $timestamp . $appkey);

    $msgarr = array(
        array(
            'title' => '[第' . $lecture['week'] . '周] 文化素质讲座预告：' . $lecture['title'],
            'description' => "演讲人：{$lecture['speaker']}，讲座主题：{$lecture['title']}，时间：{$lecture['datetime_str']}，地点：{$lecture['location']}",
            'url' => $lecture['link'],
            'picurl' => '',
        )
    );

    $msg = json_encode($msgarr);

    $posturl = "https://weixin.cic.tsinghua.edu.cn/cop/sendmsg.php?app=$app&key=$key&timestamp=$timestamp&type=news&msgtype=lecture";
    $postdata = array(
        'userlist' => '',
        'party' => '301',
        'safe' => 0,
        'msg' => $msg,
    );
    httpPost($posturl, $postdata);
}

