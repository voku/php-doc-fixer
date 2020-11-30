
# ***PHP-Documentation-Check***

This is a experiment! Lets check and fix the php documentation automatically.

### install
```bash
git clone https://github.com/php/doc-en.git
git clone https://github.com/php/php-src.git // optional: by default we use the PhpStorm Stubs
git clone https://github.com/phpstan/phpstan-src.git // optional: only needed if you will check stubs from phpstan
git clone https://github.com/voku/php-doc-fixer.git
cd php-doc-fixer/
composer update --prefer-dist
```

### command for analysing phpstan stubs
```
php bin/phpdocfixer phpstan [--remove-array-value-info="true"] [--stubs-path="../php-src/"] [--stubs-file-extension=".stub.php"] ../phpstan-src/resources/functionMap.php
```


### command for analysing and fixing the php documentation
```
php bin/phpdocfixer run [--auto-fix="true"] [--remove-array-value-info="true"] [--stubs-path="../php-src/"] [--stubs-file-extension=".stub.php"] ../doc-en/reference/
```

#### example: sync types from PhpStorm Stubs into the php-documentation
```
php bin/phpdocfixer run --auto-fix="true" --remove-array-value-info="true" ../doc-en/reference/
```

#### example: sync types from php-src into the php-documentation, but only for BCMath (bc)
```
php bin/phpdocfixer run --auto-fix="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../doc-en/reference/bc/
```
