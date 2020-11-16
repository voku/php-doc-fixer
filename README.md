
# ***PHP-Documentation-Check***

This is a experiment! Lets check (maybe fix at some point) the php documentation automatically.

```
git clone https://github.com/php/doc-en
cd ..
git clone https://github.com/voku/php-doc-fixer
cd php-doc-fixer
composer install
php bin/phpdocfixer run ../doc-en/reference
```
