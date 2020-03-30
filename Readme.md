# PAC-gen

Dynamic Proxy Auto-Configuration (PAC) generator in PHP

## Quick start

Just copy files in `www` to a PHP web server.

Add your custom hosts json files to `pac.config.d/hostnames/`, and add your custom preset rules to `pac.config.d/presets/`.

## config and examples

```
pac.config.d/
├── hostnames
│   ├── ban.json
│   ├── direct.json
│   ├── example.json
│   └── test.json
└── presets
    └── office.json
```

### URL examples

If you want to `ban` and/or `direct` access hosts in corresponding json files, just simply set them in your URL.

ban hosts in `pac.config.d/hostnames/ban.json`:

    pac.php?ban=1

similarly, directly access hosts in `pac.config.d/hostnames/direct.json`:   

    pac.php?direct=1

You need to set `type` and `proxy` for `default` and other hosts in `pac.config.d/hostnames/`. Possible values for type are `http` and `socks5`.

The default config of default proxy is `DIRECT`. If you want to set a default proxy:

    pac.php?default[type]=http&default[proxy]=127.0.0.1:8080

similarly, set proxy for hosts in `pac.config.d/hostnames/`:

    pac.php?test[type]=http&test[proxy]=127.0.0.1:8080
    pac.php?test[type]=socks5&test[proxy]=127.0.0.1:1080

Example result:

    pac.php?ban=1&test[type]=http&test[proxy]=127.0.0.1:8080&example[type]=socks5&test[proxy]=127.0.0.1:1080

In general, your final query string might be long and complicated, and it is troublesome to change the same proxy settings of multiple devices. Try to save these rules in `pac.config.d/presets/` instead, and use preset rules like this (recommended):

    pac.php?pre=office

you can overwrite part of preset rules in  like this:

    pac.php?pre=office&test[proxy]=127.0.0.1:12345
    pac.php?pre=office&test[type]=http&test[proxy]=127.0.0.1:8080


## Web server

Only tested in PHP 7 (I guess PHP 5 should work too).

You can edit `config.php` for more config.

### apache

(optional) To support compression, use `mod_defalte` and modify `/etc/apache2/mods-enabled/deflate.conf`:

    AddOutputFilterByType DEFLATE application/x-ns-proxy-autoconfig

### nginx

## How to debug your .pac

you can edit `config.php`, modify `$debug_enabled = false;` to `$debug_enabled = true;`, and the extra output will be helpful for debugging:

    pac.php?debugpac=debugpac
 
How to debug in chrome（Old version）:

1. disable all proxy extensions
2. run `chrome --proxy-pac-url="http://example.com/pac.php"`
3. open `chrome://net-internals/#events`
4. filter `PAC_JAVASCRIPT_ALERT`
5. click `event` to see logs

How to debug in chrome（New version）:

1. disable all proxy extensions
2. run `chrome --proxy-pac-url="http://example.com/pac.php"`
3. open `chrome://net-export/`, export log file to  `/path/to/log.json`
4. run `grep '_debug_pac.php_' /path/to/log.json`

## FAQ

## License

This repo is licensed under the **GNU General Public License v3.0**

