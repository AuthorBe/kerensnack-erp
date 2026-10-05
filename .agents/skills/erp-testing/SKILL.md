---
name: erp-testing
description: >-
  Must read before writing, changing, or running KEREN ONE tests: files in tests/, DB-query-based code
  audits, INSERT/UPDATE/DELETE simulations, fixtures/seeders, TestRunnerService, or the /developer/tests
  runner. Covers read-only patterns, transaction rollback, try-finally teardown, and post-test hygiene.
---

# Testing & Verification Protocols (Zero Persistent Mock Data & Environment Isolation)

The repository interacts with **live production** (Supabase PostgreSQL) and **local sandbox** (`kerensnack_erp_local`). Tests must adhere strictly to environmental boundaries to protect real accounting sequences and prevent mock data residue.

## A. Strict Environment Test Isolation (Industry Best Practice)
1. **Read-Only Queries on Live Supabase**:
   Live production only accepts **read-only audits & health checks** via `SELECT` queries on real master data.
   ```php
   $item = Database::fetchOne("SELECT id, grup_id FROM public.item WHERE status_aktif = TRUE LIMIT 1");
   if (!$item) {
       throw new RuntimeException("Active master item not found in real database.");
   }
   ```
2. **Mutations & Lifecycle Simulations Strictly on Local DB**:
   Any test that performs `INSERT`, `UPDATE`, `DELETE`, or calls controllers that modify state **MUST be run against Local DB (`kerensnack_erp_local`)**.
   - **Reason**: In PostgreSQL, auto-increment sequences (`nextval`) and advisory locks are non-transactional and never roll back. Running write simulations against production causes transaction/invoice sequence gaps (e.g. `nomor_nota` skips), creating severe auditing issues.
   - `TestRunnerService` automatically enforces this via `MUTATING_SUITES` safety guard: write suites are safely skipped if the active DB is Live Supabase.

## B. Isolated transactions with automatic rollback (Mandatory for local write simulations)
```php
$pdo->beginTransaction();
try {
    // Execute test simulations and assertions in local sandbox
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

