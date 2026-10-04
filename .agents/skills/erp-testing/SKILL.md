---
name: erp-testing
description: >-
  Must read before writing, changing, or running KEREN ONE tests: files in tests/, DB-query-based code
  audits, INSERT/UPDATE/DELETE simulations, fixtures/seeders, TestRunnerService, or the /developer/tests
  runner. Covers read-only patterns, transaction rollback, try-finally teardown, and post-test hygiene.
---

# Testing & Verification Protocols (Zero Persistent Mock Data)

The connected database is **live production**. Tests must not leave mock or test artifacts (`TEST-`, `TMP-`, `FXTR-`, `Dummy`) under any circumstances, even upon assertion failure, fatal exception, or process abort.

## A. Read-only queries based on real master data (Highest Priority)
Utilize existing master data via `SELECT` queries without modifying data state:
```php
$item = Database::fetchOne("SELECT id, grup_id FROM public.item WHERE status_aktif = TRUE LIMIT 1");
if (!$item) {
    throw new RuntimeException("Active master item not found in real database.");
}
```

## B. Isolated transactions with automatic rollback (Mandatory for write simulations)
```php
$pdo->beginTransaction();
try {
    // Execute test simulations and assertions
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Guarantees database remains 100% intact and clean
    }
}
```

## C. Encapsulated teardown when controllers commit independently
If a test invokes a Controller method whose internal logic executes `beginTransaction()` + `commit()` (resulting in committed records):
1. **Local `try ... finally`**: all fixtures created before the controller execution must be cleaned up in that test's `finally` block.
2. **Delete children → parents** strictly following Foreign Key dependency order, e.g.: `item_pesanan` → `pesanan` → `pelanggan`, or `grup_produk_harga_level` → `item` → `grup_produk`.
3. **`register_shutdown_function` is a secondary fallback only**, not a replacement for `finally`.
4. **Post-test hygiene scan**: verify no residual records with prefixes (`TEST-`, `TMP-`, `FXTR-`, `Dummy`) remain in any database table.

## D. Test File Registry (Single Source of Truth)
1. Every file in `tests/` must be registered in `App\Services\TestRunnerService::SUITES`.
2. Creating ad-hoc, overlapping test fragments (orphan test files) is strictly forbidden.
3. The `/developer/tests` web runner must be protected by `Auth::requireDeveloper()`.

## E. Seeders & Test Helpers
- `database/seeds/` is intended strictly for isolated local sandboxes; executing seeds against live production is **strictly forbidden**.
- Writing test helpers that perform `INSERT` queries into public database tables without a rollback transaction wrapper is strictly forbidden.
