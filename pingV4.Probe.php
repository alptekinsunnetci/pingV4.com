<?php
header("Access-Control-Allow-Origin: https://pingv4.com");
if ($_SERVER['HTTP_REFERER'] != "https://pingv4.com/") {
    echo 'error';
    exit();
}

function ping($host, $count = 4)
{
    if ($host = validate($host)) {
        return procExecute('ping -c' . $count . ' -w15', $host);
    }
    return false;
}
function ping6($host, $count = 4)
{
    if ($host = validate($host, 6)) {
        return procExecute('ping6 -c' . $count . ' -w15', $host);
    }
    return false;
}
function traceroute($host, $fail = 4)
{
    if ($host = validate($host)) {
        return procExecute('traceroute -4 -w2', $host, $fail);
    }
    return false;
}
function traceroute6($host, $fail = 2)
{
    if ($host = validate($host, 6)) {
        return procExecute('traceroute -6 -w2', $host, $fail);
    }
    return false;
}
function mtr($host)
{
    if ($host = validate($host)) {
        return procExecute('mtr -4 --report --report-wide', $host);
    }
    return false;
}
function mtr6($host)
{
    if ($host = validate($host, 6)) {
        return procExecute('mtr -6 --report --report-wide', $host);
    }
    return false;
}
function procExecute($cmd, $host, $failCount = 2)
{
    $spec    = array(
        0 => array(
            "pipe",
            "r"
        ),
        1 => array(
            "pipe",
            "w"
        ),
        2 => array(
            "pipe",
            "w"
        )
    );
    $host    = str_replace('\'', '', filter_var($host, FILTER_SANITIZE_URL));
    $process = proc_open("{$cmd} '{$host}'", $spec, $pipes, null);
    if (!is_resource($process)) {
        return false;
    }
    if (strpos($cmd, 'mtr') !== false) {
        $type = 'mtr';
    } elseif (strpos($cmd, 'traceroute') !== false) {
        $type = 'traceroute';
    } else {
        $type = '';
    }
    $fail       = 0;
    $match      = 0;
    $traceCount = 0;
    $lastFail   = 'start';
    while (($str = fgets($pipes[1], 1024)) != null) {
        if (ob_get_level() == 0) {
            ob_start();
        }
        $str = htmlspecialchars(trim($str));
        if ($type === 'mtr') {
            if ($match < 10 && preg_match('/^[0-9]\. /', $str, $string)) {
                $str = preg_replace('/^[0-9]\. /', '&nbsp;&nbsp;' . $string[0], $str);
                $match++;
            } else {
                $str = preg_replace('/^[0-9]{2}\. /', '&nbsp;' . substr($str, 0, 4), $str);
            }
        } elseif ($type === 'traceroute') {
            if ($match < 10 && preg_match('/^[0-9] /', $str, $string)) {
                $str = preg_replace('/^[0-9] /', '&nbsp;' . $string[0], $str);
                $match++;
            }
            if (strpos($str, '* * *') !== false) {
                $fail++;
                if ($lastFail !== 'start' && ($traceCount - 1) === $lastFail && $fail >= $failCount) {
                    echo str_pad($str . '<br />-- Traceroute timed out --<br />', 1024, ' ', STR_PAD_RIGHT);
                    break;
                }
                $lastFail = $traceCount;
            }
            $traceCount++;
        }
        echo str_pad($str . '<br />', 1024, ' ', STR_PAD_RIGHT);
        @ob_flush();
        flush();
    }
    while (($err = fgets($pipes[2], 1024)) != null) {
        if (strpos($err, 'Name or service not known') !== false || strpos($err, 'unknown host') !== false) {
            echo 'Unauthorized request';
            break;
        }
    }
    $status = proc_get_status($process);
    if ($status['running'] == true) {
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        $ppid = $status['pid'];
        $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
        foreach ($pids as $pid) {
            if (is_numeric($pid)) {
                posix_kill($pid, 9);
            }
        }
        proc_close($process);
    }
    return true;
}
function validate($host, $type = 4)
{
    if (validIP($host, $type)) {
        return $host;
    } elseif ($type === 6 && validIP($host, 4)) {
        return false;
    } elseif ($host = validUrl($host)) {
        return $host;
    }
    return false;
}
function validIP($host, $type = 4)
{
    if ($type === 4) {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {
            return true;
        }
    } else {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }
    }
    return false;
}
function validUrl($url)
{
    if (stripos($url, 'http') === false) {
        $url = 'http://' . $url;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        if ($host = parse_url($url, PHP_URL_HOST)) {
            return $host;
        }
        return $url;
    }
    return false;
}



if (isset($_GET['cmd']) && isset($_GET['host'])) {
    $cmds = array(
        'ping',
        'ping6',
        'traceroute',
        'traceroute6',
        'mtr',
        'mtr6'
    );
    if (in_array($_GET['cmd'], $cmds)) {
        $output = $_GET['cmd']($_GET['host']);
        if ($output) {
            exit();
        }
    }
}

if (isset($_GET['cmd']) == "control") {
    echo 'pingV4';
    exit();
}
