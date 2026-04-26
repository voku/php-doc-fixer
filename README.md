
# ***PHP-Documentation-Check***

This is a experiment! Lets check and fix the php documentation automatically.

### install
```bash
git clone https://github.com/php/doc-en.git // required for the doc-en sync workflow
git clone https://github.com/php/php-src.git // required for the doc-en sync workflow; this is the source of truth for synopsis updates
git clone https://github.com/jetbrains/phpstorm-stubs.git // optional: useful for static analysis comparisons or alternative stub sources
git clone https://github.com/vimeo/psalm.git // optional: only if you want to check it
git clone https://github.com/phpstan/phpstan-src.git // optional: only if you want to check it
git clone https://github.com/voku/php-doc-fixer.git
cd php-doc-fixer/
composer install --prefer-dist
```

### project shortcuts
```bash
composer test
composer scan-docs -- ../doc-en/reference/
composer fix-docs -- ../doc-en/reference/
```

`scan-docs` and `fix-docs` use `../php-src` with `.stub.php` files so `php-src` is the source of truth for `php/doc-en` pull requests.

The `run` command normalizes refined and pseudo-types back to manual-safe synopsis types by default, including collapsing array value info such as `int[]` to `array`. Pass `--remove-array-value-info="false"` if you need to keep that information for a custom comparison.

`fix-docs` re-checks the XML after updating files, so it exits successfully when all detected mismatches were applied automatically and reports the remaining count if manual follow-up is still needed. It can update both single types and union types in either direction when the XML synopsis and stub signature disagree.

If the full `reference/` diff is too large for one review, run the same commands against extension or module subdirectories and open smaller pull requests, for example `../doc-en/reference/bc/` or `../doc-en/reference/mysqli/`.

### command for analysing static code analysis stubs (PHPStan, Psalm, ...)
```
php bin/phpdocfixer static_analysis [--remove-array-value-info="true"] [--stubs-path="vendor/jetbrains/phpstorm-stubs/"] [--stubs-file-extension=".php"] ../phpstan-src/resources/functionMap.php
```

#### example: check types from php-src against static code analysis stubs from PHPStan
```
php bin/phpdocfixer static_analysis --remove-array-value-info="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../phpstan-src/resources/functionMap.php
```

#### example: check types from phpstorm-stubs (mysqli) against static code analysis stubs from PHPStan
```
php bin/phpdocfixer static_analysis --stubs-path="../phpstorm-stubs/mysqli/" ../phpstan-src/resources/functionMap.php
```

#### example: check types from PhpStorm stubs against static code analysis stubs from Psalm
```
php bin/phpdocfixer static_analysis ../psalm/dictionaries/CallMap_84.php
```


### command for analysing and fixing the php documentation
```
php bin/phpdocfixer run [--auto-fix="true"] [--remove-array-value-info="true"] [--stubs-path="../php-src/"] [--stubs-file-extension=".stub.php"] ../doc-en/reference/
```

#### example: sync types from php-src stubs into the php-documentation
```
php bin/phpdocfixer run --auto-fix="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../doc-en/reference/
```

#### example: run the full scan and then apply all directly fixable updates
```
composer scan-docs -- ../doc-en/reference/
composer fix-docs -- ../doc-en/reference/
```

#### example: sync types from php-src into the php-documentation, but only for BCMath (bc)
```
php bin/phpdocfixer run --auto-fix="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../doc-en/reference/bc/
```
