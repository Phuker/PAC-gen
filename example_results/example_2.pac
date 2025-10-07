var default_rule_pac_result = 'DIRECT';


function myDnsDomainIs(host, domain) {
    if (domain[0] === '.') {
        domain = domain.substr(1);
    }

    var idx = host.length - domain.length;

    return (host === domain) || (idx > 0 && host.lastIndexOf('.' + domain) == idx - 1);
}


function FindProxyForURL(url, host) {
    return default_rule_pac_result;
}
