<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/27/17
 * Time: 7:03 PM
 */

require_once __DIR__ . '/../vendor/autoload.php';

$vCalendar = new \Eluceo\iCal\Component\Calendar('清华大学文化素质教育讲座');

$timezone = new \DateTimeZone('Asia/Shanghai');

$lectures = file_exists(__DIR__ . '/../data/lectures.json') ? json_decode(file_get_contents(__DIR__ . '/../data/lectures.json'), true) : array();

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

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="cal.ics"');

echo $vCalendar->render();