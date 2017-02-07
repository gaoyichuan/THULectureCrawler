<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/7/17
 * Time: 1:18 PM
 */

require_once 'crawler.php';

function getWeekday(string $str){
    switch ($str){
        case '一': return 1;
        case '二': return 2;
        case '三': return 3;
        case '四': return 4;
        case '五': return 5;
        case '六': return 6;
        case '日': return 7;
    }
}

$crawler = new Crawler("http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more", false);

$links = array();

for ($page = 0; $page < 1; $page++){
    echo "Crawling page $page.\n";

    $url = "http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more?page=$page";
    $crawler->setUrl($url);

    $links = array_merge($links, $crawler->crawl());
}

foreach ($links as $link) {
    $matches = array();
    $info = array();

    if(preg_match('/[0-9]{1,2}周/', $link['title'], $matches)) {
        preg_match('/[0-9]{1,2}/', $matches[0], $matches);
        $info['week'] = intval($matches[0]);
        preg_match('/周[一二三四五六日]/', $link['title'], $matches);
        preg_match('/[一二三四五六日]/', $matches[0], $matches);
        echo $matches[0];
        $info['weekday'] = getWeekday($matches[0]);
        var_dump($info);
    }
}
