<?php
require_once('config.php');

header('Content-Type: application/x-ns-proxy-autoconfig');
header('Content-Disposition: attachment; filename="proxy.pac"');

define('CONFIG_DIR_PATH_PRESETS_DATA', path_join(CONFIG_DIR_PATH_DATA, 'presets'));
define('CONFIG_DIR_PATH_HOSTNAMES_DATA', path_join(CONFIG_DIR_PATH_DATA, 'hostnames'));

define('IS_DEBUG_ENABLED', CONFIG_IS_DEBUG_ALLOWED && isset($_GET[CONFIG_URL_PARAM_KEY_DEBUG]) && $_GET[CONFIG_URL_PARAM_KEY_DEBUG] === CONFIG_DEBUG_PASSWORD);

// scan CONFIG_DIR_PATH_HOSTNAMES_DATA for other non-hardcode json files, add to $value_config_rules
// exclude array_keys(CONFIG_BOOL_CONFIG_PAC_RESULTS), CONFIG_DEFAULT_RULE_NAME, CONFIG_URL_PARAM_KEY_PRESET
$all_json_hostnames = str_replace('.json', '', get_json_filenames(CONFIG_DIR_PATH_HOSTNAMES_DATA));
$all_json_hostnames = valid_rule_name_filter($all_json_hostnames);
$all_hardcode_rules = array_unique(array_merge(array_keys(CONFIG_BOOL_CONFIG_PAC_RESULTS), [CONFIG_DEFAULT_RULE_NAME, CONFIG_URL_PARAM_KEY_PRESET, CONFIG_URL_PARAM_KEY_DEBUG]));
$value_config_rules = array_diff($all_json_hostnames, $all_hardcode_rules);
$all_possible_proxy_rules = array_unique(array_merge(array_keys(CONFIG_BOOL_CONFIG_PAC_RESULTS), $value_config_rules, [CONFIG_DEFAULT_RULE_NAME]));

if (IS_DEBUG_ENABLED) {
    echo 'var __all_json_hostnames = ' . json_encode($all_json_hostnames) . ";\n";
    echo 'var __all_hardcode_rules = ' . json_encode($all_hardcode_rules) . ";\n";
    echo 'var __value_config_rules = ' . json_encode($value_config_rules) . ";\n";
    echo 'var __all_possible_proxy_rules = ' . json_encode($all_possible_proxy_rules) . ";\n";
}

function path_join(...$parts)
{
    $separator = DIRECTORY_SEPARATOR;
    $separators = array_unique([$separator, '/']);
    $separators_str = implode('', $separators);

    $length = count($parts);
    for ($i = 0; $i < $length; $i++) {
        if ($i === 0) {
            $parts[$i] = rtrim($parts[$i], $separators_str);
        } elseif ($i === $length - 1) {
            $parts[$i] = ltrim($parts[$i], $separators_str);
        } else {
            $parts[$i] = trim($parts[$i], $separators_str);
        }
    }

    $result = implode($separator, $parts);

    return $result;
}

function get_json_filenames($dirpath)
{
    $filename_pattern = '/\\.json$/i';
    $result = [];
    $dirlist = scandir($dirpath);

    if ($dirlist !== FALSE) {
        foreach ($dirlist as $filename) {
            if (preg_match($filename_pattern, $filename) === 1) {
                if (is_file(path_join($dirpath, $filename))) {
                    array_push($result, $filename);
                }
            }
        }
    }

    return $result;
}

function get_json_content($filepath, $fallback)
{
    $content = file_get_contents($filepath);
    if ($content === false) {
        if (IS_DEBUG_ENABLED) {
            echo "var __error_read_file = " . json_encode($filepath) . ";\n";
        }

        return $fallback;
    } else {
        $result = json_decode($content, true);
        if ($result === null) {
            if (IS_DEBUG_ENABLED) {
                echo "var __error_try_decode_json = " . json_encode($filepath) . ";\n";
            }

            return $fallback;
        } else {
            // original $content is decodeable
            return trim($content);
        }
    }
}

function get_json_decoded($filepath, $fallback)
{
    $content = file_get_contents($filepath);
    if ($content === false) {
        if (IS_DEBUG_ENABLED) {
            echo "var __error_read_file = " . json_encode($filepath) . ";\n";
        }

        return $fallback;
    } else {
        $result = json_decode($content, true);
        if ($result === null) {
            if (IS_DEBUG_ENABLED) {
                echo "var __error_decode_json = " . json_encode($filepath) . ";\n";
            }

            return $fallback;
        } else {
            return $result;   // decoded array etc.
        }
    }
}

function is_valid_type($type)
{
    return $type === 'socks5' || $type === 'http';
}


function is_valid_str($str, $valid_chars)
{
    if (!is_string($str) || strlen($str) === 0) {
        return false;
    }

    $length = strlen($str);
    for ($i = 0; $i < $length; $i++) {
        if (strpos($valid_chars, $str[$i]) === false) {
            return false;
        }
    }

    return true;
}

function is_valid_proxy($str)
{
    $valid_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-:.';

    return is_valid_str($str, $valid_chars);
}

function is_valid_rule_name($str)
{
    $valid_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';

    return is_valid_str($str, $valid_chars);
}

function valid_rule_name_filter($rules)
{
    return array_values(array_filter($rules, 'is_valid_rule_name'));
}


$config = [];
if (isset($_GET[CONFIG_URL_PARAM_KEY_PRESET])) {
    $preset = $_GET[CONFIG_URL_PARAM_KEY_PRESET];
    if (is_valid_rule_name($preset)) {
        $filepath = path_join(CONFIG_DIR_PATH_PRESETS_DATA, "{$preset}.json");
        $config = get_json_decoded($filepath, []);
        $config = array_intersect_key($config, array_flip($all_possible_proxy_rules));
        if (IS_DEBUG_ENABLED) {
            echo "var __preset = " . json_encode($config) . ";\n";
        }
    }
}

if (IS_DEBUG_ENABLED) {
    echo 'var __config = ' . json_encode($config) . ";\n\n";
}


$output_rule = [];
foreach (CONFIG_BOOL_CONFIG_PAC_RESULTS as $rule => $result) {
    if (isset($config[$rule])) {
        array_push($output_rule, $rule);

        $filepath = path_join(CONFIG_DIR_PATH_HOSTNAMES_DATA, "{$rule}.json");
        $domains = get_json_content($filepath, '[]');
        echo <<<EOD
var {$rule}_result = '{$result}';
var {$rule}_domains = {$domains};


EOD;
    }
}

if (
    isset($config[CONFIG_DEFAULT_RULE_NAME]['type']) && isset($config[CONFIG_DEFAULT_RULE_NAME]['proxy']) &&
    is_valid_type($config[CONFIG_DEFAULT_RULE_NAME]['type']) && is_valid_proxy($config[CONFIG_DEFAULT_RULE_NAME]['proxy'])
) {
    $type = $config[CONFIG_DEFAULT_RULE_NAME]['type'];
    $proxy = $config[CONFIG_DEFAULT_RULE_NAME]['proxy'];

    if ($type === 'socks5') {
        $defalut_rule_result = sprintf('SOCKS5 %s; SOCKS %s', $proxy, $proxy);
    } elseif ($type === 'http') {
        $defalut_rule_result = sprintf('PROXY %s', $proxy);
    } else {
        $defalut_rule_result = CONFIG_DEFAULT_DEFALUT_RULE_PAC_RESULT;
    }
} else {
    $defalut_rule_result = CONFIG_DEFAULT_DEFALUT_RULE_PAC_RESULT;
}

foreach ($value_config_rules as $rule) {
    if (
        isset($config[$rule]['type']) && isset($config[$rule]['proxy']) &&
        is_valid_type($config[$rule]['type']) && is_valid_proxy($config[$rule]['proxy'])
    ) {
        array_push($output_rule, $rule);

        $type = $config[$rule]['type'];
        $proxy = $config[$rule]['proxy'];

        if ($type === 'socks5') {
            $result = sprintf('SOCKS5 %s; SOCKS %s', $proxy, $proxy);
        } elseif ($type === 'http') {
            $result = sprintf('PROXY %s', $proxy);
        }
        $filepath = path_join(CONFIG_DIR_PATH_HOSTNAMES_DATA, "{$rule}.json");
        $domains = get_json_content($filepath, '[]');
        echo <<<EOD
var {$rule}_result = '{$result}';
var {$rule}_domains = {$domains};


EOD;
    }
}

echo <<<EOD

function myDnsDomainIs(host, domain) {
    if (domain[0] === '.') {
        domain = domain.substr(1);
    }

    var idx = host.length - domain.length;

    return (host === domain) || (idx > 0 && host.lastIndexOf('.' + domain) == idx - 1);
}



EOD;

echo "function FindProxyForURL(url, host) {\n";
foreach ($output_rule as $rule) {
    if (IS_DEBUG_ENABLED) {
        $debug_cmd = "alert('_debug_pac.php_ host: ' + host + ', url: ' + url + ', rule: {$rule}, result: ' + {$rule}_result);\n            ";
    } else {
        $debug_cmd = '';
    }

    echo <<<EOD
    for (var i = {$rule}_domains.length - 1; i >= 0; i--) {
        if (myDnsDomainIs(host, {$rule}_domains[i])) {
            {$debug_cmd}return {$rule}_result;
        }
    }


EOD;
}

if (IS_DEBUG_ENABLED) {
    echo "    alert('_debug_pac.php_ host: ' + host + ', url: ' + url + ', rule: " . CONFIG_DEFAULT_RULE_NAME . "');\n";
}

echo "    return \"{$defalut_rule_result}\";\n";
echo "}\n";
