var hostname_rule_ban_pac_result = 'PROXY 127.0.0.1:0';
var hostname_rule_ban_hostname_list = [
    "hm.baidu.com",
    "ubmcmm.baidustatic.com"
];

var hostname_rule_direct_pac_result = 'DIRECT';
var hostname_rule_direct_hostname_list = [];

var hostname_rule_list1_pac_result = 'PROXY 127.0.0.1:8080';
var hostname_rule_list1_hostname_list = [
    ".example.com",
    ".example.org"
];

var hostname_rule_list2_pac_result = 'SOCKS5 127.0.0.1:1080; SOCKS 127.0.0.1:1080';
var hostname_rule_list2_hostname_list = [
    ".google.com",
    ".googleusercontent.com"
];

var default_rule_pac_result = 'DIRECT';


function myDnsDomainIs(host, domain) {
    if (domain[0] === '.') {
        domain = domain.substr(1);
    }

    var idx = host.length - domain.length;

    return (host === domain) || (idx > 0 && host.lastIndexOf('.' + domain) == idx - 1);
}


function FindProxyForURL(url, host) {
    for (var i = 0; i < hostname_rule_ban_hostname_list.length; i++) {
        if (myDnsDomainIs(host, hostname_rule_ban_hostname_list[i])) {
            return hostname_rule_ban_pac_result;
        }
    }

    for (var i = 0; i < hostname_rule_direct_hostname_list.length; i++) {
        if (myDnsDomainIs(host, hostname_rule_direct_hostname_list[i])) {
            return hostname_rule_direct_pac_result;
        }
    }

    for (var i = 0; i < hostname_rule_list1_hostname_list.length; i++) {
        if (myDnsDomainIs(host, hostname_rule_list1_hostname_list[i])) {
            return hostname_rule_list1_pac_result;
        }
    }

    for (var i = 0; i < hostname_rule_list2_hostname_list.length; i++) {
        if (myDnsDomainIs(host, hostname_rule_list2_hostname_list[i])) {
            return hostname_rule_list2_pac_result;
        }
    }

    return default_rule_pac_result;
}
