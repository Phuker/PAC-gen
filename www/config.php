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
