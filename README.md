
# ***PHP-Documentation-Check***

This project compares PHP function and method signatures from different sources and can automatically update XML synopsis types in `php/doc-en`.

The main goal is to create small, reviewable pull requests for the `php/doc-en` repository, using `php-src` stubs as the source of truth for manual pages.

### install
```bash
mkdir php-doc-workspace
cd php-doc-workspace/

git clone https://github.com/php/doc-en.git # required for the doc-en sync workflow
git clone https://github.com/php/php-src.git # required for the doc-en sync workflow; this is the source of truth for synopsis updates
git clone https://github.com/jetbrains/phpstorm-stubs.git # optional: useful for static analysis comparisons or alternative stub sources
git clone https://github.com/vimeo/psalm.git # optional: only if you want to check it
git clone https://github.com/phpstan/phpstan-src.git # optional: only if you want to check it
git clone https://github.com/voku/php-doc-fixer.git

cd php-doc-fixer/
composer install --prefer-dist
```

Recommended directory layout:

```text
php-doc-workspace/
├── doc-en/
├── php-src/
├── php-doc-fixer/
├── phpstorm-stubs/   # optional
├── phpstan-src/      # optional
└── psalm/            # optional
```

### supported checks and sources

This repository supports two comparison modes:

1. **Documentation sync (`run`)**
   - compares XML synopsis types from `php/doc-en/reference/`
   - against PHP stub files from a selected stub source
   - can optionally auto-fix directly safe XML updates

2. **Static analysis comparison (`static_analysis`)**
   - compares static-analysis call maps such as PHPStan or Psalm
   - against a selected stub source
   - reports mismatches, but does not edit files

Recommended source choices:

| Use case | Compare | Recommended source of truth |
| --- | --- | --- |
| Update `php/doc-en` manual pages | `doc-en/reference/` vs stubs | `php-src/*.stub.php` |
| Compare PHPStan call maps | `phpstan-src/resources/functionMap.php` vs stubs | `php-src/*.stub.php` or PhpStorm stubs |
| Compare Psalm call maps | `psalm/dictionaries/CallMap_84.php` vs stubs | PhpStorm stubs or `php-src/*.stub.php` |
| Explore alternative stub differences | static-analysis maps vs stubs | `phpstorm-stubs/*.php` |

For `php/doc-en` pull requests, use `php-src` as the source of truth.

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

### recommended workflow for reviewable `php/doc-en` pull requests

Use this workflow when the goal is to fix the PHP manual step by step:

1. work in a shared parent directory with `doc-en`, `php-src`, and `php-doc-fixer` as sibling directories
2. run `composer install --prefer-dist`
3. run `composer test`
4. pick one manageable target:
   - all of `../doc-en/reference/` for a broad audit
   - or one module such as `../doc-en/reference/bc/`, `../doc-en/reference/mysqli/`, `../doc-en/reference/array/`
5. run `composer scan-docs -- <target>`
6. run `composer fix-docs -- <target>`
7. run `composer scan-docs -- <target>` again
8. review the XML diff in `../doc-en`
9. keep only a reviewable scope in the `php/doc-en` branch
10. open the pull request in `php/doc-en`, not in `voku/php-doc-fixer`

Practical guidance:

- prefer one extension or one focused topic per PR
- let `fix-docs` apply the straightforward synopsis changes first
- use the second scan output as the list of remaining manual follow-up items
- if a directory still produces too many changes, narrow the target again
- keep this repository unchanged unless you are improving the fixer or its documentation

### full doc-en sync workflow (for a local coding agent)

Use this when you want the agent to download the related repositories and apply all directly fixable synopsis updates from `php-src` into `php/doc-en`.

```bash
cd php-doc-workspace/php-doc-fixer/

composer install --prefer-dist
composer test

# inspect all current mismatches
composer scan-docs -- ../doc-en/reference/

# apply all automatic fixes
composer fix-docs -- ../doc-en/reference/

# confirm what is still left afterwards
composer scan-docs -- ../doc-en/reference/
```

Expected behavior:

- `composer test` validates the fixer itself.
- `composer scan-docs` reports mismatches between `php-src` stubs and the XML synopsis files in `doc-en/reference/`.
- `composer fix-docs` updates directly fixable XML synopsis types in place and re-checks the result before exiting.
- A final `composer scan-docs` shows the remaining manual follow-up items, if any.

Suggested handoff for a local coding agent:

- clone or update `doc-en`, `php-src`, and `php-doc-fixer` as sibling directories
- run `composer install --prefer-dist` in `php-doc-fixer`
- run `composer test`
- run `composer scan-docs -- ../doc-en/reference/`
- run `composer fix-docs -- ../doc-en/reference/`
- run `composer scan-docs -- ../doc-en/reference/` again
- review the diff in `../doc-en/reference/`
- if the diff is too large, repeat the same workflow for smaller subdirectories such as `../doc-en/reference/bc/`
- open the pull request in `php/doc-en`, not in `voku/php-doc-fixer`

### command for analysing static code analysis stubs (PHPStan, Psalm, ...)
```
php bin/phpdocfixer static_analysis [--remove-array-value-info="true"] [--stubs-path="vendor/jetbrains/phpstorm-stubs/"] [--stubs-file-extension=".php"] ../phpstan-src/resources/functionMap.php
```

#### example: check types from php-src against static code analysis stubs from PHPStan
```
php bin/phpdocfixer static_analysis --remove-array-value-info="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../phpstan-src/resources/functionMap.php
```

The static-analysis flow also normalizes PHPStan-only pseudo-types such as `class-string<T>`, `list<T>`, array shapes, callable signatures, and `int-mask<...>` back to comparable native types before reporting mismatches.

#### example: check types from phpstorm-stubs (mysqli) against static code analysis stubs from PHPStan
```
php bin/phpdocfixer static_analysis --stubs-path="../phpstorm-stubs/mysqli/" ../phpstan-src/resources/functionMap.php
```

#### example: check types from PhpStorm stubs against static code analysis stubs from Psalm
```
php bin/phpdocfixer static_analysis ../psalm/dictionaries/CallMap_84.php
```

Notes:

- `static_analysis` defaults to `vendor/jetbrains/phpstorm-stubs/` when `--stubs-path` is omitted
- `static_analysis` defaults to `--stubs-file-extension=".php"`
- parameter names are ignored in static-analysis mode; parameter type order is compared


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

Notes:

- `run` defaults to `vendor/jetbrains/phpstorm-stubs/` when `--stubs-path` is omitted
- for `php/doc-en` work, pass `--stubs-path="../php-src/" --stubs-file-extension=".stub.php"` or use the composer shortcuts
- `run` compares both return types and parameter names/types
- when `--auto-fix="true"` is enabled, the command re-scans after writing files and reports the remaining mismatch count
