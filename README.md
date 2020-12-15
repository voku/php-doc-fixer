
# ***PHP-Documentation-Check***

This is a experiment! Lets check and fix the php documentation automatically.

### install
```bash
git clone https://github.com/php/doc-en.git // optional: only if you want to check it
git clone https://github.com/php/php-src.git // optional: by default we use the PhpStorm Stubs from vendor directory but you can also use different stubs
git clone https://github.com/jetbrains/phpstorm-stubs.git // optional: by default we use the PhpStorm Stubs from vendor directory but you can also use external stubs
git clone https://github.com/vimeo/psalm.git // optional: only if you want to check it
git clone https://github.com/phpstan/phpstan-src.git // optional: only if you want to check it
git clone https://github.com/voku/php-doc-fixer.git
cd php-doc-fixer/
composer update --prefer-dist
```

### command for analysing static code analysis stubs (PHPStan, Psalm, ...)
```
php bin/phpdocfixer static_analysis [--remove-array-value-info="true"] [--stubs-path="vendor/jetbrains/phpstorm-stubs/"] [--stubs-file-extension=".php"] ../phpstan-src/resources/functionMap.php
```

#### example: check types from php-src against static code analysis stubs from PHPStan
```
php bin/phpdocfixer static_analysis --remove-array-value-info="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../phpstan-src/resources/functionMap.php
```

#### example: check types from PhpStorm stubs against static code analysis stubs from Psalm
```
php bin/phpdocfixer static_analysis ../psalm/src/Psalm/Internal/CallMap.php
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
