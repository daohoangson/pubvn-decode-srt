<?php

define('URL_PREFIX_ENCODED', 'encoded');
define('URL_FILENAME', 'sub.vtt');

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
    header('Access-Control-Allow-Origin: *');
    header('Content-type: text/vtt; charset=utf-8');
    header('Expires: Wed, 01 Jan 2100 00:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
    header('Cache-Control: public');
    header('Content-Length: ' . strlen($parsed));

    if (substr(URL_FILENAME, -4) === '.vtt') {
        $parsed = "WEBVTT\n\n"
            . preg_replace('#(\d+:\d+),(\d+)#', '$1.$2', $parsed);
    }

    return $parsed;
}

if (isset($_POST['url'])) {
    $urlEncoded = base64_encode($_POST['url']);
    $location = sprintf('%s/%s/%s', URL_PREFIX_ENCODED, $urlEncoded, URL_FILENAME);
    header('Location: ' . $location);
    exit(0);
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
if ($url === null
    && !empty($_SERVER['SCRIPT_NAME'])
    && !empty($_SERVER['REQUEST_URI'])
) {
    $dirName = dirname($_SERVER['SCRIPT_NAME']);
    $prefix = trim(sprintf('%s/%s', $dirName, URL_PREFIX_ENCODED), '/');
    $pattern = '#^/' . preg_quote($prefix, '#')
        . '/(?<encoded>.+)/'
        . preg_quote(URL_FILENAME, '#') . '$#';
    if (preg_match($pattern, $_SERVER['REQUEST_URI'], $matches)) {
        $url = base64_decode($matches['encoded']);
    }
}

if (!empty($url)) {
    $parsed = parse(file_get_contents($url));
    die(output($parsed));
}

?>

<!doctype html>
<html lang=en>
<head>
    <meta charset=utf-8>
    <title>pubvn-decode-srt</title>
    <meta name="SCRIPT_NAME" content="<?php echo htmlentities($_SERVER['SCRIPT_NAME']); ?>"/>
    <meta name="REQUEST_URI" content="<?php echo htmlentities($_SERVER['REQUEST_URI']); ?>"/>
</head>
<body>
<form action="index.php" method="POST">
    <input name="url" placeholder="URL"/>
    <input type="submit" value="Submit"/>
</form>
</body>
</html>
