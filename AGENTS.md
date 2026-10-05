# Engineering Guidelines & Agent Operational Protocols

Mandatory, no-exception standard for all AI assistants and contributors of the **KEREN ONE** repository. Long-form details live in skills under `.agents/skills/` (see section 2); critical core rules stay here.

## 1. Production & Data Integrity
- The repo is connected to the **live production database** (Supabase PostgreSQL: real transactions, finance, inventory). Every DB interaction must guarantee *data safety*, *transactional reliability*, and *relational integrity*.
- **Zero Contamination**: no dummy entities (products, groups, stores, trial orders, etc.) may appear in the UI or corrupt financial aggregates.
- **Zero Persistent Mock Data & Test Isolation**:
  1. Never persist test (mock/dummy) data permanently in any table.
  2. `database/seeds/` is for isolated local sandboxes only; never run it against production.
  3. **Strict Environment Test Isolation**: All write simulations and mutating test suites (`INSERT`/`UPDATE`/`DELETE`) **MUST run strictly against Local DB (`kerensnack_erp_local`)**. Supabase Live accepts **read-only health checks only**. This guarantees invoice sequence numbers never skip and prevents accidental pollution of production accounting tables.
  4. Tests/simulations must not leave *orphan records*, even on assertion failure, fatal exception, or abort.
  5. No test helper may `INSERT` into the public database without a rollback transaction wrapper.

## 2. Read the Matching Skill BEFORE Starting Work
| Work | Skill |
|---|---|
| Create/edit views, modals, tables, CSS, colors, PWA navigation (`views/**`, `public/assets/**`) | `erp-ui-design` |
| Migrations, schema, RPC, Supabase GRANT/RLS, live-to-local sync (`database/**`) | `erp-database` |
| Write/change/run tests (`tests/**`), fixtures, TestRunnerService | `erp-testing` |
| Controllers, services, helpers, API endpoints, logic, refactoring (`app/**`, `bin/**`) | `erp-coding` |

Skills hold the complete rules (testing, schema/permissions/replication, UI/UX, backend logic) and are **just as binding** as this document.

## 3. Core Testing Rules
- Prefer **read-only** tests using `SELECT` on real master data.
- **Write simulations (`INSERT`/`UPDATE`/`DELETE`)**: Must run strictly in **Local DB sandbox** wrapped inside a transaction with `rollBack()` in a `finally` block.
- If the tested controller commits on its own: clean up fixtures in a local `finally`, delete children → parents (FK order), then scan for residue `TEST-`, `TMP-`, `FXTR-`, `Dummy`. `register_shutdown_function` is a fallback only.
- Every file in `tests/` must be registered in `App\Services\TestRunnerService::SUITES`; orphan test files are forbidden. The `/developer/tests` runner must use `Auth::requireDeveloper()`.
- Test runner automatically enforces safety guards: mutating test suites will be safely skipped if the active connection points to Live Supabase.

## 4. Core Database Rules
- Every structural change (table, index, view, RPC, constraint) requires a numbered migration file `database/migrations/XX_description.sql` wrapped in `BEGIN; ... COMMIT;`.
- **Local-First Staging Workflow (Mandatory Dual-Database)**:
  Any official migration, schema fix, RPC update, or permanent system master entity **MUST NEVER be applied to only one database**, with strict staging order:
  1. **Stage 1 (Local First)**: Apply and verify the migration in **Local PostgreSQL (`kerensnack_erp_local`)** first via `php bin/migrate.php --target=local`. Validate zero SQL syntax or constraint regressions.
  2. **Stage 2 (Live Deploy)**: Once 100% verified in Local, apply the exact same migration file to **Cloud Supabase (Live)** via `php bin/migrate.php --target=live`.
  3. **Stage 3 (Automated Tracking & Status)**: Track and verify applied status across both databases using `php bin/migrate.php --status`.
- Always keep `database/01_schema.sql` & `database/02_triggers_and_rpc.sql` in sync (Single Source of Truth); avoid PostgreSQL 15+ features (e.g. `security_invoker`) in canonical DDL.
- `SECURITY DEFINER` functions must `REVOKE EXECUTE ... FROM PUBLIC, anon, authenticated` and `GRANT EXECUTE ... TO postgres, service_role`.
- Replication: master data 100% complete; transactions/logs use a 14-day sliding window. New master tables must be registered in `App\Services\DatabaseManagerService::MASTER_TABLES`; new transaction tables must provide standard timestamp columns (`created_at`, `dibuat_pada`, `tanggal_*`). Large datasets or batch runs via CLI: `php bin/sync_db.php --mode=14d|full` or 1-click `bin\sync-db-from-live.bat`.

## 5. Core UI/UX Rules
- **Must comply with skill `erp-ui-design`** and mimic the Golden Templates.
- **Forbidden**: Material Design 3 (FAB, full-pill buttons, thick surface-container, Roboto). Use Modern Enterprise ERP / Supabase Clean UI: `Inter` & `JetBrains Mono`, `Lucide` icons, `rounded-lg`/`rounded-md`, subtle borders.
- Modals must use the standard structure (`x-teleport` → `.modal-backdrop` → `.modal-box` → `.modal-handle` → `.modal-header` → `.modal-body.custom-scrollbar` → `.modal-footer`).
- Dialogs must use `window.AppConfirm()`, `window.AppAlert()`, `window.toast` (`public/assets/js/erp-helpers.js`); native `alert()`/`confirm()` are **forbidden**.
- Notch color `#881337`; avoid `filter: blur` on `<header>`/`<main>` (use `backdrop-filter: blur(6px)` on overlays); standalone portal close buttons use Smart Navigation (`window.close()` → `history.back()` → `/dashboard`).

## 6. Security, CSRF & RBAC
- Credentials, API keys, passwords, tokens must come from `.env`; never hard-code or expose them in logs.
- All mutating endpoints (`POST`/`PUT`/`DELETE`) and forms must use `App\Helpers\CSRF`.
- Layered RBAC: `Auth::requirePermission()`, `Auth::requireRole()`, `Auth::requireDeveloper()`.

## 7. Git Commit & Push (only when explicitly requested)
1. **Never** `git commit`/`git push` automatically or on your own initiative; only when the user explicitly asks.
2. **Zero AI Footprint**: use the repo's local identity (`user.name` & `user.email`); no AI identifiers, bot signatures, watermarks, or `Co-authored-by` in commits/metadata.
3. **Manual Execution Fallback**: if a commit purely under the user's name cannot be guaranteed, **do not** execute. Present the changed-file list, a clean Conventional Commits message, and a copy-ready CLI block (`git add ...`, `git commit -m "..."`, `git push`) for the user to run.

## 8. Pragmatic Engineering, Token Efficiency & AI-Assisted Protocol
- **Comply with skill `erp-coding`**: KEREN ONE is Pure PHP 8.1+ Custom MVC (never hallucinate Laravel or Eloquent).
- **Mandatory Helper Reuse**: Always check and reuse utilities in `app/Helpers/` (`Format`, `StockHelper`, `DocumentNumber`, `CSRF`, `ActivityLog`, `Upload`, `CashVoucher`, `CompanySetting`, etc.). Never reinvent existing logic.
- **Pragmatic Dependency Curation (YAGNI)**: Do not add third-party dependencies that are not needed based on the current reality of code, database, and essential application needs. Leverage native PHP 8.1+ capabilities first; third-party official SDKs (e.g. payment gateway, official WhatsApp API, thermal printing) may only be introduced with explicit user alignment.
- **Minimalist Diff & YAGNI**: The shortest working diff that solves root cause wins. Avoid speculative features, single-use interfaces, and redundant boilerplate.
- **Atomic Operations**: All financial, stock, and multi-table state mutations must use explicit database transactions (`beginTransaction()` ... `commit()` / `rollBack()`).
- **Autonomous Technical Guardianship**: The user is an AI-assisted developer. Agents bear full responsibility for diagnosing errors, verifying constraints, avoiding technical jargon quizzes, and ensuring zero breakage across modules.
