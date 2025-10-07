# PAC-gen

Dynamic Proxy Auto-Configuration (PAC) generator written in PHP

## Quick start

Simply copy the files from the `www` directory to any location within your PHP web server's web root, and copy the files from the `data` directory to a location outside the web root. Then, make sure that `CONFIG_DIR_PATH_DATA` in `config.php` points to the `pac.config.d` directory.

Add custom hostname list files to the `data/pac.config.d/hostnames/` directory, and add custom rule config files to the `data/pac.config.d/configs/` directory.

### Examples

You can find configuration examples in [`data/pac.config.d/`](./data/pac.config.d/), along with their corresponding generated PAC files in [`example_results/`](./example_results/).

| config file                        | URL                                                              | PAC file                                                                     |
| ---------------------------------- | ---------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| [example_1.json][config_example_1] | `pac.php?config=example_1`<br>`pac.php?config=example_1&debug=1` | [example_1.pac][pac_example_1]<br>[example_1.debug.pac][pac_example_1_debug] |
| [example_2.json][config_example_2] | `pac.php?config=example_2`<br>`pac.php?config=example_2&debug=1` | [example_2.pac][pac_example_2]<br>[example_2.debug.pac][pac_example_2_debug] |
| [example_3.json][config_example_3] | `pac.php?config=example_3`<br>`pac.php?config=example_3&debug=1` | [example_3.pac][pac_example_3]<br>[example_3.debug.pac][pac_example_3_debug] |

[config_example_1]: ./data/pac.config.d/configs/example_1.json
[config_example_2]: ./data/pac.config.d/configs/example_2.json
[config_example_3]: ./data/pac.config.d/configs/example_3.json
[pac_example_1]: ./example_results/example_1.pac
[pac_example_1_debug]: ./example_results/example_1.debug.pac
[pac_example_2]: ./example_results/example_2.pac
[pac_example_2_debug]: ./example_results/example_2.debug.pac
[pac_example_3]: ./example_results/example_3.pac
[pac_example_3_debug]: ./example_results/example_3.debug.pac

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
