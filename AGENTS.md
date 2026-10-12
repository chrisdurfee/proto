# AGENTS.md

The canonical guide for AI agents working on or with Proto is [.github/copilot-instructions.md](.github/copilot-instructions.md). Read it before writing code.

- Tests: `php vendor/bin/phpunit` (run `composer install` first if `vendor/` is missing)
- Static analysis: `composer analyze` (PHPStan, `phpstan.neon`; do not add to `phpstan-baseline.neon`)
- Contributor rules (commits, changelog): [CONTRIBUTING.md](CONTRIBUTING.md)
