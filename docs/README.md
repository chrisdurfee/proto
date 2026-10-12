# Proto Documentation Index

The main framework guide for humans is the root [README.md](../README.md). The canonical guide for AI agents is [.github/copilot-instructions.md](../.github/copilot-instructions.md). Contributor setup is in [CONTRIBUTING.md](../CONTRIBUTING.md).

## Models, storage and data

- [BIDIRECTIONAL_PAGINATION.md](BIDIRECTIONAL_PAGINATION.md): cursor pagination in both directions: `cursor` for older rows, `since` for newer ones.
- [CUSTOM_DATA_TYPES.md](CUSTOM_DATA_TYPES.md): `$dataTypes` handlers for complex SQL types such as `POINT` and `JSON`.
- [CUSTOM_DATA_TYPES_QUICK_REFERENCE.md](CUSTOM_DATA_TYPES_QUICK_REFERENCE.md): cheat sheet for custom data types.

## Modules and routing

- [NESTED_FEATURE_MODULES.md](NESTED_FEATURE_MODULES.md): feature modules nested as subdirectories of a parent module.
- [RFC_NESTED_API_COMPOSITION.md](RFC_NESTED_API_COMPOSITION.md): RFC behind nested `api.php` composition, `includeApi()` and `resourceStrict()`.

## Realtime and events

- [REALTIME_SERVER.md](REALTIME_SERVER.md): design of the standalone realtime (SSE) server started with `bin/proto-realtime`.
- [REDIS_EVENTS.md](REDIS_EVENTS.md): Redis pub/sub integration for the event system.
- [REDIS_EVENTS_QUICK_REFERENCE.md](REDIS_EVENTS_QUICK_REFERENCE.md): cheat sheet for Redis events.
- [REDIS_SSE_EXAMPLES.md](REDIS_SSE_EXAMPLES.md): examples of streaming Redis events over Server-Sent Events.

## Security

- [SECURITY_UPGRADE_GUIDE.md](SECURITY_UPGRADE_GUIDE.md): what apps must change when upgrading across the security hardening releases.

## Testing

- [TESTING_BEST_PRACTICES.md](TESTING_BEST_PRACTICES.md): how to write tests with `Proto\Tests\Test` (transactions, helpers, pitfalls).
- [TEST_QUICK_REFERENCE.md](TEST_QUICK_REFERENCE.md): cheat sheet of test helpers and assertions.
- [TESTING_DATABASE_SETUP.md](TESTING_DATABASE_SETUP.md): how tests pick the `testing` database connection and keep one shared connection.
- [FACTORIES.md](FACTORIES.md): full guide to model factories.
- [FACTORY_README.md](FACTORY_README.md): overview of the factory system and its files.
- [FACTORY_QUICK_REFERENCE.md](FACTORY_QUICK_REFERENCE.md): cheat sheet for factories.
- [SIMPLE_FAKER_NEW_METHODS.md](SIMPLE_FAKER_NEW_METHODS.md): newer `SimpleFaker` generator methods.

## History (archived, may be out of date)

These describe past audits and fixes. Keep them for context; do not treat them as current API docs.

- [history/AUDIT_REPORT.md](history/AUDIT_REPORT.md): comprehensive framework audit report.
- [history/SECURITY_AUDIT_REPORT.md](history/SECURITY_AUDIT_REPORT.md): earlier security and performance audit.
- [history/FRAMEWORK_IMPROVEMENTS.md](history/FRAMEWORK_IMPROVEMENTS.md): an earlier round of boilerplate-reducing framework changes.
- [history/TRANSACTION_ISOLATION_FIX.md](history/TRANSACTION_ISOLATION_FIX.md): write-up of the ORM test transaction-isolation fix.
- [history/StoragePage.js](history/StoragePage.js): unreferenced docs-site page component (Storage docs) kept for reference.
