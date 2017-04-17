<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/7/17
 * Time: 1:18 PM
 */

require_once 'config.inc.php';
require_once 'crawler.php';
require_once 'info.php';
require_once 'weixin.php';
require_once __DIR__ . '/../vendor/autoload.php';

global $_CONFIG;
date_default_timezone_set('Asia/Shanghai');

function getDuration(\DateTime $datetime) {
    if ($datetime->format('N') > 5) {
        return new \DateInterval('PT2H');
    } else if (in_array($datetime->format('H:i'), array('13:30', '15:20', '17:05'))) {
        return new \DateInterval('PT1H35M');
    } else {
        return new \DateInterval('PT2H');
    }
}

$timezone = new \DateTimeZone('Asia/Shanghai');
$crawler = new Crawler("http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more", true);

$lectures = file_exists(__DIR__ . '/../data/lectures.json') ? json_decode(file_get_contents(__DIR__ . '/../data/lectures.json'), true) : array();

$links = array();

for ($page = 0; $page < 2; $page++) {  // Crawl two pages is enough
    $url = "http://postinfo.tsinghua.edu.cn/f/jiaowugonggao/more?page=$page";
    $crawler->setUrl($url);
    $links = array_merge($links, $crawler->crawl());
}

foreach ($links as $link) {
    if ((preg_match('/[0-9]{1,2}周/u', $link['title']) != false) && (mb_strpos($link['title'], '预告') != false)) {
        $info = getLectureInfo($link);
        $seen = false;
        foreach ($lectures as $lecture) {
            if ($lecture['link'] == $link['url']) {
                $seen = true;
                break;
            }
        }
        if (!$seen) {
            $lectures[] = $info;
            if ($_CONFIG['enableWechat']) {
                if ($info['datetime'] != '') {
                    $lectureTime = new \DateTime($info['datetime'], new \DateTimeZone('Asia/Shanghai'));
                    $nowTime = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
                    if ($lectureTime >= $nowTime) sendLectureByWeixin($info); // Prevent send lecture info in the past
                } else {
                    sendLectureByWeixin($info);
                }
            }
        }
    }
}

$fp = fopen(__DIR__ . '/../data/lectures.json', 'w');
fwrite($fp, json_encode($lectures));
fclose($fp);

if ($_CONFIG['enableIcal']) {
    $vCalendar = new \Eluceo\iCal\Component\Calendar('清华大学文化素质教育讲座');
    foreach ($lectures as $lecture) {
        if ($lecture['title'] != '') {
            $vEvent = new \Eluceo\iCal\Component\Event();

            $vEvent
                ->setSummary($lecture['title'])
                ->setLocation($lecture['location'])
                ->setDescription($lecture['speaker'])
                ->setUrl($lecture['link']);

            $haveTime = true;
            try {
                $datetime = new \DateTime($lecture['datetime'], $timezone);
            } catch (Exception $e) {
                $lecture['datetime'] = '';
                $haveTime = false;
            }

            if ($haveTime && ($datetime instanceof \DateTime)) {
                $vEvent
                    ->setUseTimezone(true)
                    ->setDtStart($datetime)
                    ->setDuration(getDuration($datetime))
                    ->setNoTime(false);
            } else {
                $vEvent->setNoTime(true);
            }

            $vCalendar->addComponent($vEvent);
        }
    }


    $cal = fopen(__DIR__ . '/../data/cal.ics', 'w');
    fwrite($cal, $vCalendar->render());
    fclose($cal);
}
