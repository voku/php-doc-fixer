# AGENTS.md

## Goal

Use this repository to create small, reviewable pull requests for `php/doc-en` that align XML synopsis types with the canonical signatures from `php-src`.

## Recommended workspace

Create sibling directories:

```text
php-doc-workspace/
├── doc-en/
├── php-src/
├── php-doc-fixer/
├── phpstorm-stubs/   # optional
├── phpstan-src/      # optional
└── psalm/            # optional
```

Run commands from:

`/absolute/path/to/php-doc-workspace/php-doc-fixer`

## What each source is for

- `php-src/*.stub.php`: source of truth for `php/doc-en` pull requests
- `vendor/jetbrains/phpstorm-stubs/` or `../phpstorm-stubs/`: alternative stub source, useful for static-analysis comparisons
- `../phpstan-src/resources/functionMap.php`: PHPStan call map input for `static_analysis`
- `../psalm/dictionaries/CallMap_84.php`: Psalm call map input for `static_analysis`

If the task is to update the PHP manual, compare `doc-en/reference/` against `php-src`, not against PhpStorm stubs.

## Baseline project checks

Run these before working:

```bash
composer install --prefer-dist
composer test
```

## Main commands

### 1) Scan `php/doc-en` against `php-src`

```bash
composer scan-docs -- ../doc-en/reference/
```

This expands to:

```bash
php bin/phpdocfixer run --stubs-path=../php-src --stubs-file-extension=.stub.php ../doc-en/reference/
```

### 2) Auto-fix directly safe XML synopsis updates

```bash
composer fix-docs -- ../doc-en/reference/
```

This expands to:

```bash
php bin/phpdocfixer run --auto-fix=true --stubs-path=../php-src --stubs-file-extension=.stub.php ../doc-en/reference/
```

### 3) Re-scan after auto-fix

```bash
composer scan-docs -- ../doc-en/reference/
```

### 4) Compare static-analysis maps against a stub source

Examples:

```bash
php bin/phpdocfixer static_analysis --remove-array-value-info="true" --stubs-path="../php-src/" --stubs-file-extension=".stub.php" ../phpstan-src/resources/functionMap.php
php bin/phpdocfixer static_analysis --stubs-path="../phpstorm-stubs/mysqli/" ../phpstan-src/resources/functionMap.php
php bin/phpdocfixer static_analysis ../psalm/dictionaries/CallMap_84.php
```

## Recommended agent workflow for `php/doc-en`

1. ensure `doc-en`, `php-src`, and `php-doc-fixer` are sibling directories
2. run `composer install --prefer-dist`
3. run `composer test`
4. choose a reviewable target directory inside `../doc-en/reference/`
5. run `composer scan-docs -- <target>`
6. run `composer fix-docs -- <target>`
7. run `composer scan-docs -- <target>` again
8. inspect the diff in `../doc-en`
9. keep the change set narrow enough for review
10. open the pull request in `php/doc-en`

## How to keep pull requests reviewable

- prefer one extension, one module, or one tight topic per PR
- if `reference/` is too large, narrow the target to a subdirectory such as:
  - `../doc-en/reference/bc/`
  - `../doc-en/reference/mysqli/`
  - `../doc-en/reference/array/`
- let `fix-docs` handle the automatic updates first
- use the second `scan-docs` output as the manual follow-up list
- do not mix fixer changes in this repository with XML changes in `php/doc-en` unless the task explicitly asks for both

## Behavior notes

- `run` compares return types and parameter names/types
- `static_analysis` compares return types and parameter type order
- `run` defaults to PhpStorm stubs if `--stubs-path` is omitted, so use the composer shortcuts or pass `../php-src/` explicitly for manual-page work
- `run` with `--auto-fix=true` writes XML files and then re-checks the result
- the remaining mismatches after auto-fix usually need manual review

## Expected deliverable

The normal deliverable is not a pull request in `voku/php-doc-fixer`.

The normal deliverable is a reviewable pull request in `php/doc-en`, based on changes generated or identified with this repository.
