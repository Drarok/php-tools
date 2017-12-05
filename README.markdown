# PHP Tools

As covered [in my blog post][blog-post], I run multiple versions of PHP on the same machine via Apache and also on the command-line. These scripts are quick and dirty helpers that install (or update) the PHP installs themselves, and also packages such as xdebug.

Note that these will only work if you are on a Mac, and using [Homebrew][homebrew] to manage your PHP binaries.

## Usage

Clone this repo, copy `config.sample.json` to `config.json`, and edit it as required. Open a terminal in the directory where you cloned the repo:

```bash
php installPhpVersions.php # Install all the versions of PHP and their packages
php upgradePhpVersions.php # Upgrade all installed PHP-related packages
```

[blog-post]: https://blog.drarok.com/2017/11/06/all-the-phps.html
[homebrew]: https://brew.sh
