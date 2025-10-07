var default_rule_pac_result = 'PROXY 127.0.0.1:8080';


function myDnsDomainIs(host, domain) {
    if (domain[0] === '.') {
        domain = domain.substr(1);
    }

    var idx = host.length - domain.length;

    return (host === domain) || (idx > 0 && host.lastIndexOf('.' + domain) == idx - 1);
}


function FindProxyForURL(url, host) {
    alert('[PAC] url: ' + url + ', host: ' + host + ', default PAC result: ' + default_rule_pac_result);
    return default_rule_pac_result;
}
