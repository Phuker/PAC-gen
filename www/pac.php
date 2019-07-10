<?php
$config_dir = 'pac.config.d';
$config_dir_presets = $config_dir . '/presets';
$config_dir_hostnames = $config_dir . '/hostnames';

$preset_key_name = 'pre';
$debug_enabled = false;
$debug_key_name = 'debugpac';
$debug_password = 'debugpac';

$bool_config_rules = [
	'ban' => 'PROXY 127.0.0.1:0',
	'direct' => 'DIRECT'
]; 
// $value_config_rules = [];  // will scan $config_dir_hostnames for more, excluding other hardcoded rules
$default_rule_name = 'default';

$default_defalut_rule_result = 'DIRECT';


// - - - - - - - - - - - - - - - end configuration - - - - - - - - - - - - - - - 

header('Content-Type: application/x-ns-proxy-autoconfig');
header('Content-Disposition: attachment; filename="proxy.pac"');

if($debug_enabled && isset($_GET[$debug_key_name]) && $_GET[$debug_key_name] === $debug_password){
	$debug = true;
} else {
	$debug = false;
}

// scan $config_dir_hostnames for other non-hardcode json files, add to $value_config_rules
// exclude array_keys($bool_config_rules), $default_rule_name, $preset_key_name
$all_json_hostnames = str_replace('.json', '', get_json_filenames($config_dir_hostnames));
$all_json_hostnames = valid_rule_name_filter($all_json_hostnames);
$all_hardcode_rules = array_unique(array_merge(array_keys($bool_config_rules), [$default_rule_name, $preset_key_name, $debug_key_name]));
$all_special_rules = [$preset_key_name, $debug_key_name];
$value_config_rules = array_diff($all_json_hostnames, $all_hardcode_rules);
$all_possible_proxy_rules = array_unique(array_merge(array_keys($bool_config_rules), $value_config_rules, [$default_rule_name]));

if($debug){
	echo 'var __all_json_hostnames = ' . json_encode($all_json_hostnames) . ";\n";
	echo 'var __all_hardcode_rules = ' . json_encode($all_hardcode_rules) . ";\n";
	echo 'var __value_config_rules = ' . json_encode($value_config_rules) . ";\n";
	echo 'var __all_special_rules = ' . json_encode($all_special_rules) . ";\n";
	echo 'var __all_possible_proxy_rules = ' . json_encode($all_possible_proxy_rules) . ";\n";
}

function path_join($part1, $part2){
	$separator = DIRECTORY_SEPARATOR;
	$separators = ['/'];
	$separators = array_unique(array_merge($separators, [$separator]));

	$separators_str = implode('', $separators);
	$part1 = rtrim($part1, $separators_str);
	$part2 = ltrim($part2, $separators_str);
	return implode($separator, [$part1, $part2]);
}

function get_json_filenames($dirpath){
	$filename_pattern = '/\\.json$/i';
	$result = [];
	$dirlist = scandir($dirpath);

	if($dirlist !== FALSE){
	    foreach($dirlist as $filename){
	        if(preg_match($filename_pattern, $filename) === 1){
	        	if(is_file(path_join($dirpath, $filename))){
	        		array_push($result, $filename);	
	        	}
	        }
	    }
	}
	return $result;
}

function get_json_content($filepath, $fallback){
	global $debug;

	$content = file_get_contents($filepath);
	if($content === false){
		if($debug){ echo "var __error_read_file = " . json_encode($filepath) . ";\n"; }
		return $fallback;
	} else {
		$result = json_decode($content, true);
		if($result === null){
			if($debug){ echo "var __error_try_decode_json = " . json_encode($filepath) . ";\n"; }
			return $fallback;
		} else {
			return $content;  // original decodeable content
		}
		
	}
}

function get_json_decoded($filepath, $fallback){
	global $debug;

	$content = file_get_contents($filepath);
	if($content === false){
		if($debug){ echo "var __error_read_file = " . json_encode($filepath) . ";\n"; }
		return $fallback;
	} else {
		$result = json_decode($content, true);
		if($result === null){
			if($debug){ echo "var __error_decode_json = " . json_encode($filepath) . ";\n"; }
			return $fallback;
		} else {
			return $result;	  // decoded array etc.
		}
	}
}

function valid_type($type){
	if($type === 'socks5' || $type === 'http'){
		return true;
	} else {
		return false;
	}
}

function valid_proxy($proxy){
	$valid_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-:.';
	if(!is_string($proxy) || strlen($proxy) === 0){
		return false;
	}
	$length = strlen($proxy);
	for ($i=0; $i < $length; $i++) { 
		if(strpos($valid_chars, $proxy[$i]) === false){
			return false;
		}
	}
	return true;
}

function valid_rule_name($rule){
	$valid_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
	if(!is_string($rule) || strlen($rule) === 0){
		return false;
	}
	$length = strlen($rule);
	for ($i=0; $i < $length; $i++) { 
		if(strpos($valid_chars, $rule[$i]) === false){
			return false;
		}
	}
	return true;
}

function valid_rule_name_filter($rules){
	return array_values(array_filter($rules, 'valid_rule_name'));
}


// merge preset and url options
$config = [];
if(isset($_GET[$preset_key_name])){
	$preset = $_GET[$preset_key_name];
	if(valid_rule_name($preset)){
		$filepath = path_join($config_dir_presets, $preset . '.json');
		$config = get_json_decoded($filepath, []);
		$config = array_intersect_key($config, array_flip($all_possible_proxy_rules));
		if($debug){ echo "var __preset = " . json_encode($config) . ";\n"; }
	}
}
foreach ($all_possible_proxy_rules as $rule) {
	if(isset($_GET[$rule])){
		$config = array_replace_recursive($config, [$rule => $_GET[$rule]]);
	}
}
if($debug){
	echo 'var __config = ' . json_encode($config) . ";\n\n";
}


$output_rule = [];
foreach ($bool_config_rules as $rule => $result) {
	if(isset($config[$rule])){
		array_push($output_rule, $rule);

		$filepath = path_join($config_dir_hostnames, $rule . '.json');
		$domains = get_json_content($filepath, '[]');
		echo <<<EOD
var ${rule}_result = '$result';
var ${rule}_domains = $domains;


EOD;
	}
}

if(isset($config[$default_rule_name]['type']) && isset($config[$default_rule_name]['proxy']) &&
	valid_type($config[$default_rule_name]['type']) && valid_proxy($config[$default_rule_name]['proxy'])){
	$type = $config[$default_rule_name]['type'];
	$proxy = $config[$default_rule_name]['proxy'];

	if($type === 'socks5'){
		$defalut_rule_result = sprintf('SOCKS5 %s; SOCKS %s', $proxy, $proxy);
	} elseif ($type === 'http') {
		$defalut_rule_result = sprintf('PROXY %s', $proxy);
	} else {
		$defalut_rule_result = $default_defalut_rule_result;
	}
} else {
	$defalut_rule_result = $default_defalut_rule_result;
}

foreach ($value_config_rules as $rule) {
	if(isset($config[$rule]['type']) && isset($config[$rule]['proxy']) &&
		valid_type($config[$rule]['type']) && valid_proxy($config[$rule]['proxy'])){
		array_push($output_rule, $rule);

		$type = $config[$rule]['type'];
		$proxy = $config[$rule]['proxy'];

		if($type === 'socks5'){
			$result = sprintf('SOCKS5 %s; SOCKS %s; %s', $proxy, $proxy, $defalut_rule_result);
		} elseif ($type === 'http') {
			$result = sprintf('PROXY %s; %s', $proxy, $defalut_rule_result);
		} 
		$filepath = path_join($config_dir_hostnames, $rule . '.json');
		$domains = get_json_content($filepath, '[]');
		echo <<<EOD
var ${rule}_result = '$result';
var ${rule}_domains = $domains;


EOD;
	}
}

echo <<<EOD
function myDnsDomainIs(host,domain) {
    if(domain[0] === '.'){
        domain = domain.substr(1);
    }
    var idx = host.length - domain.length;
    return (host === domain) || (idx > 0 && host.lastIndexOf('.' + domain) == idx - 1);
}

EOD;

echo "function FindProxyForURL(url, host){\n";
foreach ($output_rule as $rule) {
	if($debug){
		$debug_cmd = "alert('_debug_pac.php_ host: ' + host + ' url: ' + url + ' rule: $rule result: ' + ${rule}_result);";
	} else {
		$debug_cmd = '';
	}
	echo <<<EOD
	for(var i = ${rule}_domains.length - 1; i>=0; i--){
		if(myDnsDomainIs(host, ${rule}_domains[i])){
			$debug_cmd
			return ${rule}_result;
		}
	}

EOD;
}

if($debug){
	echo "	alert('_debug_pac.php_ host: ' + host + ' url: ' + url + ' rule: default');\n";
}


echo "	return \"$defalut_rule_result\";";
echo "\n}";
