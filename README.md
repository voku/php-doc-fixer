
# ***PHP-Documentation-Check***

This is a experiment! Lets check (maybe fix at some point) the php documentation automatically.

```bash
git clone https://github.com/php/doc-en.git
git clone https://github.com/voku/php-doc-fixer.git
cd php-doc-fixer/
composer update --prefer-dist
php bin/phpdocfixer run ../doc-en/reference
```
