<?php
/**
 * Created by PhpStorm.
 * User: gaoyichuan
 * Date: 2/7/17
 * Time: 7:10 PM
 */

/**
 * @param string $str
 * @return int|null
 */
function getWeekday($str) {
    switch ($str) {
        case '周一':
            return 1;
        case '周二':
            return 2;
        case '周三':
            return 3;
        case '周四':
            return 4;
        case '周五':
            return 5;
        case '周六':
            return 6;
        case '周日':
            return 7;
        default:
            return null;
    }
}

function file_get_contents_utf8($fn) {
    $content = file_get_contents($fn);
    return mb_convert_encoding($content, 'UTF-8',
        mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
}

function strpos_array($haystack, $needles) {
    if (is_array($needles)) {
        foreach ($needles as $str) {
            if (is_array($str)) {
                $pos = strpos_array($haystack, $str);
            } else {
                $pos = strpos($haystack, $str);
            }
            if ($pos !== false) {
                return $pos;
            }
        }
    } else {
        return strpos($haystack, $needles);
    }
    return false;
}

/**
 * @param mixed $str
 * @return mixed
 */
function trimStr($str) {
    if ($str == NULL) {
        return '';
    } else {
        $trim = preg_replace('/(\s+$|^\s+)/u', '', $str);
        return preg_replace('/\s+/u', ' ', $trim);
    }
}

function findTitle($strings) {
    $title_blacklist = array('简介', '单位', '主办', '嘉宾', '提示', '特别', '演讲人', '内容', '介绍', '课程预告', '讲堂');
    if(!is_array($strings)) {
        if(strpos_array($strings, $title_blacklist) === false) {
            if(strlen(trimStr(strip_tags($strings))) > 8) return trimStr(strip_tags($strings));
        }
    } else {
        foreach ($strings as $string) {
            if(strpos_array($string, $title_blacklist) === false) {
                if(strlen(trimStr(strip_tags($string))) > 8) return trimStr(strip_tags($string));
            }
        }
    }
    return false;
}

/**
 * @param array $link
 * @return array
 */
function getLectureInfo($link) {
    $info = array();
    $matches = array();

    preg_match('/([1-9]|1[0-8])周/u', $link['title'], $matches);
    $info['week'] = intval($matches[1]);

    preg_match('/(周|星期)[一二三四五六日]/u', $link['title'], $matches);
    $info['weekday'] = getWeekday($matches[0]);

    $dom = new DOMDocument;
    $mock = new DOMDocument;
    @$dom->loadHTML(file_get_contents_utf8($link['url']));

    $bodyDom = $dom->getElementsByTagName('body')->item(0);
    foreach ($bodyDom->childNodes as $child) {
        $mock->appendChild($mock->importNode($child, true));
    }
    $body = html_entity_decode(strip_tags($mock->saveHTML()), ENT_COMPAT | ENT_HTML401, 'UTF-8');
    $bodyRaw = html_entity_decode($mock->saveHTML(), ENT_COMPAT | ENT_HTML401, 'UTF-8');

    if (preg_match('/(演讲|讲座|演出)(标题|题目|主题|名称)[:：]\s{0,2}(\S{1,})\n{1,}/u', $body, $matches)) {
        @$info['title'] = trimStr($matches[3]);
    } else if (preg_match_all('/<strong>(.*[^:][^：])<\/strong>/u', $bodyRaw, $matches)) {
        if ((count($matches[1]) > 0) && (findTitle($matches[1]) !== false)) {
            @$info['title'] = findTitle($matches[1]);
        } else {
            echo 'Cannot get accurate title, using possibly wrong title for ' . $link['url'] . PHP_EOL;
            if (preg_match('/\s*(.*)\n\s*\n(演讲人|讲述人|嘉\s*宾|主持人)[:：]/u', $body, $matches)) {
                @$info['title'] = trimStr($matches[1]);
            } else {
                preg_match('/\n\s+(\S+)\n+(简介|讲座简介|讲座介绍)/u', $body, $matches);
                @$info['title'] = trimStr($matches[1]);
            }
        }
    }

    preg_match('/(演\s{0,2}讲\s{0,2}人|讲\s{0,2}述\s{0,2}人|嘉\s{0,6}宾)[:：]\s{0,2}(.+)\s?\n+/u', $body, $matches);
    @$info['speaker'] = trimStr($matches[2]);

    preg_match('/(时\s*间|日\s*期)[:：](.+)\n+/u', $body, $matches);
    @$info['datetime_str'] = trimStr($matches[2]);

    if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日\s{0,4}[(（]?周?\S?[）)]?\s{0,4}(上午|下午)?(\d{1,2})[:：](\d{1,2})/u', $body, $matches)) {
        if ($matches[4] == '下午') {
            if(intval($matches[5] <= 12)){
                @$info['datetime'] = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . (intval($matches[5]) + 12) . ':' . $matches[6] . ':00';
            } else {
                @$info['datetime'] = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[5] . ':' . $matches[6] . ':00';
            }
        } else {
            @$info['datetime'] = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[5] . ':' . $matches[6] . ':00';
        }
    } else {
        $info['datetime'] = '';
    }

    preg_match('/(地\s*点|地\s*址)[:：](.+)\n+/u', $body, $matches);
    @$info['location'] = trimStr($matches[2]);

    $info['link'] = $link['url'];

    return $info;
}
