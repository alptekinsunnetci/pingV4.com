<?php
header("Access-Control-Allow-Origin: https://pingv4.com");
if (isset($_SERVER['HTTP_REFERER']) != "https://pingv4.com/") {
    echo 'error';
    exit();
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
        if (
            filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE
            )
        ) {
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
    if (stripos($url, "http") === false) {
        $url = "http://" . $url;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        if ($host = parse_url($url, PHP_URL_HOST)) {
            return $host;
        }
        return $url;
    }
    return false;
}

if (isset($_GET["cmd"]) && isset($_GET["host"])) {
    $cmds = ["ping", "ping6"];
    if (in_array($_GET["cmd"], $cmds)) {
        if ($_GET["host"] = validate($_GET["host"])) {
            $addreses = $_GET["host"];
            $ip = validate($addreses);

            exec("ping -c 1 $ip", $output, $status);
		
            if ($status == 0) {
			$pingmsarray	= ($output);
			$pingmsexplode	= explode("time=",$pingmsarray [1]);
			
                echo "success/" .  $pingmsexplode [1];
                exit();
            } else {
                echo "danger/999 ms";
                exit();
            }
        }
        return false;
    }
}

if (isset($_GET["cmd"]) == "control") {
    echo "pingV4";
    exit();
}
