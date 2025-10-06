# PAC-gen

Dynamic Proxy Auto-Configuration (PAC) generator written in PHP

## Quick start

Simply copy the files from the `www` directory to any location within your PHP web server's web root, and copy the files from the `data` directory to a location outside the web root. Then, make sure that `CONFIG_DIR_PATH_DATA` in `config.php` points to the `pac.config.d` directory.

Add custom hostname list files to the `data/pac.config.d/hostnames/` directory, and add custom rule config files to the `data/pac.config.d/configs/` directory.

### Examples

Check the examples in [`data/pac.config.d/`](./data/pac.config.d/).

The corresponding URL of [`data/pac.config.d/configs/example_1.json`](./data/pac.config.d/configs/example_1.json) would be:

```text
pac.php?config=example_1
```

## Web server

Tested in PHP 7 and PHP 8 environments.

### apache

(optional) To support compression, use `mod_defalte` and modify `/etc/apache2/mods-enabled/deflate.conf`:

```text
AddOutputFilterByType DEFLATE application/x-ns-proxy-autoconfig
```

### nginx

## How to debug

### Google Chrome

1. Add the `debug` URL parameter:

```text
pac.php?config=example_1&debug=1
```

2. Make sure PAC is working
3. Open `chrome://net-export/`, export log file
4. Run command:

```bash
tail -f chrome-net-export-log.json | grep -F '[PAC]'
```

The output will be helpful for debugging.

## FAQ

## License

This repo is licensed under the **GNU General Public License v3.0**.
