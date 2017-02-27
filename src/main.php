<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/7/17
 * Time: 1:18 PM
 */

require_once 'crawler.php';
require_once 'info.php';
require_once '../vendor/autoload.php';

$vCalendar = new \Eluceo\iCal\Component\Calendar('清华大学文化素质教育讲座');
$timezone = new \DateTimeZone('Asia/Shanghai');
$crawler = new Crawler("http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more", false);

$lectures = file_exists('../data/lectures.json') ? json_decode(file_get_contents('../data/lectures.json'), true) : array();

$links = array();

for ($page = 0; $page < 2; $page++) {  // Crawl two pages is enough
    $url = "http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more?page=$page";
    $crawler->setUrl($url);
    $links = array_merge($links, $crawler->crawl());
}

foreach ($links as $link) {
    if((preg_match('/[0-9]{1,2}周/u', $link['title']) != false) && (mb_strpos($link['title'], '预告') != false)) {
        $info = getLectureInfo($link);
        $seen = false;
        foreach ($lectures as $lecture) {
            if($lecture['link'] == $link['url']) {
                $seen = true;
                break;
            }
        }
        if(!$seen) $lectures[] = $info;
    }
}

$fp = fopen('../data/lectures.json', 'w');
fwrite($fp, json_encode($lectures));
fclose($fp);

foreach ($lectures as $lecture) {
    $vEvent = new \Eluceo\iCal\Component\Event();

    $datetime = new \DateTime($lecture['datetime'], $timezone);

    $vEvent
        ->setUseTimezone(true)
        ->setSummary($lecture['title'])
        ->setDtStart($datetime)
        ->setDuration(new \DateInterval('PT2H'))
        ->setNoTime(false)
        ->setLocation($lecture['location'])
        ->setDescription($lecture['speaker'])
        ->setUrl($lecture['link']);

    $vCalendar->addComponent($vEvent);
}

$cal = fopen('../data/cal.ics', 'w');
fwrite($cal, $vCalendar->render());
fclose($cal);