
# ***PHP-Documentation-Check***

This is a experiment! Lets check and fix the php documentation automatically.

```bash
git clone https://github.com/php/doc-en.git
git clone https://github.com/php/php-src.git // optional: by default we use the PhpStorm Stubs
git clone https://github.com/voku/php-doc-fixer.git
cd php-doc-fixer/
composer update --prefer-dist
php bin/phpdocfixer run [--auto-fix="true"] [--remove-array-value-info="true"] [--stubs-path="../php-src/"] [--stubs-file-extension=".stub.php"] ../doc-en/reference/
```
