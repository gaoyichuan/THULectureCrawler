<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/7/17
 * Time: 1:18 PM
 */

require_once 'crawler.php';
require_once 'info.php';

$crawler = new Crawler("http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more", false);

$links = array();

for ($page = 0; $page < 1; $page++){
    $url = "http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more?page=$page";
    $crawler->setUrl($url);
    $links = array_merge($links, $crawler->crawl());
}

$lectures = array();

foreach ($links as $link) {
    if((preg_match('/[0-9]{1,2}周/u', $link['title']) != false) && (mb_strpos($link['title'], '预告') != false)) {
        $info = getLectureInfo($link);
        $lectures[] = $info;
    }
}
