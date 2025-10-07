<?php
require_once('config.php');

define('DIR_PATH_CONFIGS_DATA', join_path(CONFIG_DIR_PATH_DATA, 'configs'));
define('DIR_PATH_HOSTNAMES_DATA', join_path(CONFIG_DIR_PATH_DATA, 'hostnames'));

define('IS_DEBUG_ENABLED', isset($_GET[CONFIG_URL_PARAM_KEY_DEBUG]));


function my_assert($expr, $msg)
{
    if (!$expr) {
        throw new Exception($msg);
    }
}


function join_path(...$parts)
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


function read_json_file($file_path)
{
    $content = file_get_contents($file_path);
    my_assert($content !== false, "read_json_file(): failed to read file: {$file_path}");

    // Make sure $content is decodeable
    $decode_result = json_decode($content, true);
    my_assert($decode_result !== null, "read_json_file(): invalid JSON content: {$file_path}");

    $result = trim($content);

    return $result;
}


function decode_json_file($file_path)
{
    $content = file_get_contents($file_path);
    my_assert($content !== false, "decode_json_file(): failed to Read file: {$file_path}");

    $result = json_decode($content, true);
    my_assert($result !== null, "decode_json_file(): invalid JSON content: {$file_path}");

    return $result;
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


function is_valid_rule_name($str)
{
    $valid_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';

    return is_valid_str($str, $valid_chars);
}


function is_valid_rule_proxy($str)
{
    $valid_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-:.';

    return is_valid_str($str, $valid_chars);
}


function get_pac_result($rule)
{
    my_assert(is_array($rule), 'get_pac_result(): invalid $rule');

    my_assert(isset($rule['type']), 'get_pac_result(): invaild $rule.type');
    $type = $rule['type'];
    if ($type === 'ban') {
        return CONFIG_PAC_RESULT_BAN;
    } elseif ($type === 'direct') {
        return CONFIG_PAC_RESULT_DIRECT;
    } else {
        my_assert(isset($rule['proxy']), 'get_pac_result(): invalid $rule.proxy');
        $proxy = $rule['proxy'];
        my_assert(is_valid_rule_proxy($proxy), 'get_pac_result(): invalid $rule.proxy');

        if ($type === 'socks5') {
            return sprintf('SOCKS5 %s; SOCKS %s', $proxy, $proxy);
        } elseif ($type === 'http') {
            return sprintf('PROXY %s', $proxy);
        } else {
            throw new Exception('get_pac_result(): invalid $rule.type');
        }
    }
}


my_assert(isset($_GET[CONFIG_URL_PARAM_KEY_CONFIG]) && is_valid_rule_name($_GET[CONFIG_URL_PARAM_KEY_CONFIG]), 'Invalid URL param');

$config_name = $_GET[CONFIG_URL_PARAM_KEY_CONFIG];
$file_path_config = join_path(DIR_PATH_CONFIGS_DATA, "{$config_name}.json");
$config = decode_json_file($file_path_config);
my_assert(is_array($config) && isset($config['hostname_rule_list']) && isset($config['default_rule']), 'Invalid $config');

foreach ($config['hostname_rule_list'] as &$hostname_rule) {
    my_assert(is_array($hostname_rule) && isset($hostname_rule['name']) && isset($hostname_rule['rule']), 'Invalid $hostname_rule');

    my_assert(is_valid_rule_name($hostname_rule['name']), 'Invalid $hostname_rule.name');
    $file_path_hostname_list = join_path(DIR_PATH_HOSTNAMES_DATA, "{$hostname_rule['name']}.json");
    $hostname_rule['hostname_list_json_str'] = read_json_file($file_path_hostname_list);

    $hostname_rule['pac_result'] = get_pac_result($hostname_rule['rule']);
}
unset($hostname_rule);

$config['default_pac_result'] = get_pac_result($config['default_rule']);


header('Content-Type: application/x-ns-proxy-autoconfig');
header('Content-Disposition: attachment; filename="proxy.pac"');

foreach ($config['hostname_rule_list'] as $hostname_rule) {
    $rule_name = $hostname_rule['name'];
    echo <<<EOD
var hostname_rule_{$rule_name}_pac_result = '{$hostname_rule['pac_result']}';
var hostname_rule_{$rule_name}_hostname_list = {$hostname_rule['hostname_list_json_str']};


EOD;
}

echo <<<EOD
var default_rule_pac_result = '{$config['default_pac_result']}';


function myDnsDomainIs(host, domain) {
    if (domain[0] === '.') {
        domain = domain.substr(1);
    }

    var idx = host.length - domain.length;

    return (host === domain) || (idx > 0 && host.lastIndexOf('.' + domain) == idx - 1);
}



EOD;

echo "function FindProxyForURL(url, host) {\n";
foreach ($config['hostname_rule_list'] as $hostname_rule) {
    $rule_name = $hostname_rule['name'];

    if (IS_DEBUG_ENABLED) {
        $debug_cmd = "alert('[PAC] url: ' + url + ', host: ' + host + ', rule name: {$rule_name}, PAC result: ' + hostname_rule_{$rule_name}_pac_result);\n            ";
    } else {
        $debug_cmd = '';
    }

    echo <<<EOD
    for (var i = 0; i < hostname_rule_{$rule_name}_hostname_list.length; i++) {
        if (myDnsDomainIs(host, hostname_rule_{$rule_name}_hostname_list[i])) {
            {$debug_cmd}return hostname_rule_{$rule_name}_pac_result;
        }
    }


EOD;
}

if (IS_DEBUG_ENABLED) {
    echo "    alert('[PAC] url: ' + url + ', host: ' + host + ', default PAC result: ' + default_rule_pac_result);\n";
}

echo "    return default_rule_pac_result;\n";
echo "}\n";
