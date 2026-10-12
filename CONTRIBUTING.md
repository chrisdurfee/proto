# Contributing to Proto

Thanks for helping improve Proto. This file covers local setup, the checks every change must pass, and how commits and the changelog are handled.

## Development setup

Requirements: PHP 8.4+ with `mysqli`, `mbstring` and `intl`. MySQL or MariaDB is needed for the DB-backed tests. Redis is optional.

```sh
git clone https://github.com/chrisdurfee/proto.git
cd proto
composer install
cp common/Config/.env-example common/Config/.env   # JSON config; edit the "testing" connection
```

`common/Config/.env` is JSON (not dotenv) and is gitignored. Tests use the `connections.default.testing` settings. `FactoryExampleTest` and `SeederExampleTest` need `users` and `roles` tables; `.github/ci/test-schema.sql` creates the minimal versions.

## Checks

Run these before opening a pull request. CI (`.github/workflows/ci.yml`) runs the same commands.

```sh
composer validate --strict
php vendor/bin/phpunit        # or: composer test
composer analyze              # PHPStan, configured in phpstan.neon
```

- PHPStan runs at level 1 against `src/`. `phpstan-baseline.neon` lists errors that existed before PHPStan was set up. Do not add new entries to it: fix new errors instead. When you fix a baselined error, regenerate the baseline with `php vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline phpstan-baseline.neon`.
- The realtime end-to-end tests skip unless `PROTO_REALTIME_REDIS_URI` is set, for example `redis://127.0.0.1:6379`.

## Coding standards

Follow the conventions in [.github/copilot-instructions.md](.github/copilot-instructions.md): `declare(strict_types=1)`, opening braces on their own line, tabs for indentation, doc blocks on every class, property and method, and `use` imports instead of inline fully-qualified names.

## Commits

Use [Conventional Commits](https://www.conventionalcommits.org/) with a scope, matching the existing history:

```
fix(security): enforce id binding on write routes without `:id`
feat(database): introduce DatabaseUnavailableException
perf(cache): generation tokens instead of SCAN
test(unit): add ResourceController list-modifier tests
docs(changelog): file security and resilience fixes as 2.0.39
```

Common types: `feat`, `fix`, `perf`, `refactor`, `test`, `docs`, `build`, `ci`. Keep each commit to one logical change.

## Changelog

`CHANGELOG.md` follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and Semantic Versioning.

- Add user-visible changes under `## [Unreleased]` in the matching section (`Security`, `Added`, `Changed`, `Fixed`, `Upgrade notes`).
- Releases are cut by moving the Unreleased entries under a new `## [x.y.z] - YYYY-MM-DD` heading in a separate `docs(changelog): ...` commit, then tagging.
- Packagist takes versions from git tags. Do not add a `"version"` field to `composer.json`.
