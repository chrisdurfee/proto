# RFC: Nested `api.php` composition and safer `resource()`

**Status**: Deferred (design only)  
**Proto target**: post-1.3.51  
**Related**: anti-patterns (`one api.php per URL`; `resource()` `:id?` swallows literals)

## Problem

1. **Single-file resolution**  
   `ResourceHelper` / `ApiRouter` load **one** `api.php` per request (the file the URL path resolves to). Sibling feature folders under the same parent are never auto-included. Apps are forced to register catch-all or parent-level routes for cross-feature subpaths, which is easy to get wrong and hard to discover.

2. **`Router::resource()` optional `:id?`**  
   `resource('article', …)` registers `article/:id?`, so literal sub-routes registered **after** the resource (e.g. `article/featured`) are swallowed by the `:id` segment. The documented workaround is “register sub-routes before `->resource()`”, but that is a footgun, not a safeguard.

## Goals

- Additive API: existing apps keep working without rewriting every `api.php`.
- Safer defaults for new code: literal child paths win over optional id.
- Optional composition: a parent `api.php` can explicitly include child route files without changing URL resolution.

## Non-goals (this RFC)

- Auto-loading every nested `api.php` on every request (ambiguous middleware/policy boundaries; high breakage risk).
- Changing how `ResourceHelper` maps URL segments to module folders in 1.3.x.

## Proposed design (incremental)

### Phase A — Safer `resource()` (small, preferred first)

Add an opt-in (or later default) that registers the collection and item routes separately without a greedy optional `:id?`:

```php
// Today
$router->resource('user', UserController::class);
// → user/:id?

// Proposed helper / flag
$router->resource('user', UserController::class, options: ['idRequired' => true]);
// → GET|POST user
// → GET|PATCH|DELETE user/:id  (id required for item verbs)
```

Alternatively, document and ship `resourceStrict()` that never uses `:id?`, and leave `resource()` as-is until a major version.

**Mitigation for existing apps**: keep current `resource()` semantics until a major bump; add the strict variant in a minor.

### Phase B — Explicit `includeApi()` composition

```php
// modules/Article/Api/api.php
router()->includeApi(__DIR__ . '/../Featured/Api/api.php');
router()->get('article/featured', [...]); // before resource
router()->resource('article', ArticleController::class);
```

`includeApi()` would `require` the child file in the same router context (shared `$router` / middleware stack). Child files remain independently resolvable by URL when hit directly.

### Phase C — Route precedence helpers (optional)

- Prefer static segment matches over param segments when ranking routes.
- Or require apps to register static subpaths before resources (status quo) but emit a **dev-mode warning** when a literal path is registered after a catching `:id?` on the same prefix.

## Risks

| Change | Risk | Mitigation |
|--------|------|------------|
| Changing `resource()` matching | Breaks apps that rely on `:id?` for collection+item on one pattern | Additive `resourceStrict` / options flag first |
| Auto-including sibling `api.php` | Double-registration, wrong middleware, policy skips | Explicit `includeApi` only |
| Global route precedence change | Subtle reorder bugs across large apps | Dev warning first; behavior change in major |

## Decision for 1.3.51

**Skip implementation.** Another agent is actively touching `ApiRouter` / session reset for blank local API recovery; changing route matching or include semantics here would compound risk.

Ship this RFC under `docs/` so the next slice can implement Phase A (`resourceStrict` or options) without re-litigating the problem.

## Acceptance criteria (when implemented)

- [ ] Literal sub-route registered after `resource()` either works or fails loudly in development.
- [ ] Parent can compose child route files without changing `ResourceHelper` URL resolution.
- [ ] Changelog documents migration from `resource()` optional-id to strict form.
- [ ] Anti-pattern docs updated to point at the new helper.
