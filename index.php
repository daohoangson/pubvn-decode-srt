<?php

function parse($source)
{
    $resultArray = array();

    $i = 0;
    $sourceArray = preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY);
    $sourceArray = array_map('unicodeOrd', $sourceArray);
    $len = count($sourceArray);

    while ($i < $len) {
        if ($sourceArray[$i] < 0x80) {
            if (in_array($sourceArray[$i], array(10, 13), true)) {
                $resultArray[] = $sourceArray[$i];
            } else {
                $resultArray[] = $sourceArray[$i] - 1;
            }
        } else {
            if ($sourceArray[$i] <= 0xBF || $sourceArray[$i] >= 0xE0) {
                $resultArray[] = $sourceArray[$i];
                $resultArray[] = $sourceArray[$i + 1];
                $resultArray[] = $sourceArray[$i + 2] - 1;
                $i += 2;
            } else {
                $resultArray[] = $sourceArray[$i];
                $resultArray[] = $sourceArray[$i + 1] - 1;
                $i++;
            }
        }

        $i++;
    }

    unset($source);
    unset($sourceArray);
    $result = call_user_func_array('pack', array_merge(array('C*'), $resultArray));

    return $result;
}

function unicodeOrd($ch)
{
    list(, $ord) = unpack('N', mb_convert_encoding($ch, 'UCS-4BE', 'UTF-8'));
    return $ord;
}

function output($parsed)
{
    return $parsed;
}

$url = null;
if ($url === null && isset($_GET['url'])) {
    // query parameter mode
    $url = $_GET['url'];
}
if ($url === null && $argc === 2) {
    // cli mode
    $url = $argv[1];
}

if (!empty($url)) {
    $parsed = parse(file_get_contents($url));
    die(output($parsed));
} else {
    die('Hello World!');
}