---
name: erp-database
description: >-
  Must read before changing the KEREN ONE database schema: creating/editing files in database/migrations/,
  database/01_schema.sql, database/02_triggers_and_rpc.sql, new tables/indexes/views/constraints, RPC or
  SECURITY DEFINER functions, Supabase GRANT/RLS, and live-to-local sync (bin/sync_db.php).
---

# Database Schema, Supabase Permissions & Replication

## 1. Official Migrations & Local-First Staging Protocol
- Every change to tables, indexes, views, RPC functions, constraints, or official system master entities **must** have a sequentially numbered migration file in `database/migrations/`, formatted as `XX_migration_description.sql` (always verify the highest current migration number first).
- Every migration **must be atomic**: wrapped within `BEGIN; ... COMMIT;`.
- **LOCAL-FIRST STAGING WORKFLOW (MANDATORY DUAL-DATABASE APPLICATION)**:
  Because Live-to-Local sync (`Supabase -> Local`) is authoritative and wipes local public data, **NEVER apply migrations to only one database, and NEVER run unverified DDL directly against Cloud Live**:
  1. **Stage 1 (Local Sandbox First)**:
     Execute and test the migration file on **Local DB (`kerensnack_erp_local`)** first:
     ```bash
     php bin/migrate.php --target=local
     ```
     Verify that the migration succeeds without SQL syntax errors, broken foreign key constraints, or application regressions.
  2. **Stage 2 (Cloud Supabase Live Deploy)**:
     Once 100% verified and working in Local, apply the exact same migration file to **Cloud Supabase (Live)**:
     ```bash
     php bin/migrate.php --target=live
     ```
     Or execute both stages sequentially via `php bin/migrate.php --target=all`.
  3. **Stage 3 (Automated Verification & Status Tracking)**:
     Verify that both databases are fully up-to-date and have zero pending migrations:
     ```bash
     php bin/migrate.php --status --target=local
     php bin/migrate.php --status --target=live
     ```
  All executed migrations are automatically recorded in `public.schema_migrations` with batch numbers and execution timestamps.

## 2. Canonical Schema Synchronization (Single Source of Truth)
- `database/01_schema.sql` and `database/02_triggers_and_rpc.sql` **must** be synchronized with the latest schema state whenever creating or modifying migrations.
- Avoid PostgreSQL 15+ exclusive parameters (e.g., `security_invoker`) directly in canonical `CREATE VIEW` DDL to maintain backwards compatibility with local PostgreSQL 14.5 environments. Instead, use migration scripts with version-aware dynamic SQL blocks for cross-version hardening.

## 3. Supabase PostgREST & Database Hardening Protocols (1000% Zero Leak Standard)

KEREN ONE connects to the database via internal PHP PDO as the database owner (`postgres`). PostgREST is exposed by Supabase on port 443, so every database entity **must be hardened against side-channel exposure**:

### 3.1 Total RLS Enforcement (Zero Table Without RLS)
Every table in schema `public` **MUST** have Row Level Security enabled. Never leave a table unprotected:
```sql
ALTER TABLE public.nama_tabel ENABLE ROW LEVEL SECURITY;
```
For internal/system tables that should never be accessed via PostgREST:
```sql
REVOKE ALL ON public.nama_tabel FROM PUBLIC, anon, authenticated;
GRANT ALL ON public.nama_tabel TO postgres, service_role;
CREATE POLICY service_role_all_nama_tabel ON public.nama_tabel FOR ALL TO service_role USING (true) WITH CHECK (true);
```

### 3.2 Immutable Function Search Path (Prevent Search Path Hijacking)
Every function created or updated with `CREATE OR REPLACE FUNCTION` **MUST** explicitly define a static `search_path` directly in its definition before `AS $$`:
```sql
CREATE OR REPLACE FUNCTION public.fn_nama_fungsi(...)
RETURNS ...
LANGUAGE plpgsql
SET search_path = public, pg_temp
AS $$
...
```
*Note*: `CREATE OR REPLACE FUNCTION` resets previous function attributes if omitted. Omitting `SET search_path` immediately triggers Supabase Linter warning `0011 (function_search_path_mutable)`.

### 3.3 Trigger & RPC Lockdown (Prevent Accidental Public API Exposure & Linter 0028/0029)
PostgreSQL by default grants `EXECUTE` on new functions to `PUBLIC`. Supabase PostgREST automatically exposes callable routines in schema `public` via `/rest/v1/rpc/*`.
Every table trigger function, internal guard, backend-only RPC, and **DDL event trigger function (such as `rls_auto_enable()` / `ensure_rls`)** with `SECURITY DEFINER` **MUST** revoke public execution:
```sql
REVOKE EXECUTE ON FUNCTION public.fn_nama_fungsi(...) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_nama_fungsi(...) TO postgres, service_role;
```
*Note*: Omitting `REVOKE EXECUTE` on `SECURITY DEFINER` functions in schema `public` immediately triggers Supabase Security Advisor warnings:
- `0028 (anon_security_definer_function_executable)`
- `0029 (authenticated_security_definer_function_executable)`
Even though an event trigger is invoked internally by the PostgreSQL engine upon DDL execution, PostgREST will still expose it via HTTP if `anon`/`authenticated` have `EXECUTE` privilege. Revoking execute secures the API boundary while allowing internal database triggers to run normally.


### 3.4 Cross-Version View Security (`security_invoker` Blueprint)
Views querying RLS-protected tables must evaluate caller permissions on Cloud Supabase (PostgreSQL 15+) without causing `unrecognized parameter "security_invoker"` errors on Local Sandbox (PostgreSQL 14.5).
Always wrap view security hardening in a dynamic version check block:
```sql
DO $$
BEGIN
    IF current_setting('server_version_num')::int >= 150000 THEN
        EXECUTE 'ALTER VIEW public.v_nama_view SET (security_invoker = true)';
    END IF;
END $$;
```

### 3.5 Sensitive & Financial Tables (Zero Public Exposure)
Financial, cash (`arus_kas`, `akun_kas`), HR (`penggajian`, `karyawan`, `tabungan`, `kasbon`), user (`pengguna`, `izin`), and system settings tables are **strictly forbidden** from being GRANTed to `anon` / `authenticated`. Database access must be strictly handled through the PHP PDO backend.

## 4. Live-to-Local Replication & Hybrid Smart Sync Architecture

### 4.1 Master Data vs. Transactional Registry Protocol
- **Master Data (100% all-time, unwindowed)**:
  - Entities: `pelanggan`, `item`, `grup_produk`, `grup_produk_harga_level`, `item_barcode_toko`, `item_resep_bom`, `pengguna`, `karyawan`, `pemasok`, `pemasok_item`, `akun_kas`, `kategori_biaya`, `aturan_komisi`, `pengaturan_perusahaan`, etc.
  - **MANDATORY RULE FOR NEW MASTER TABLES**: Whenever adding a new lookup, catalog, configuration, or reference table, you **MUST** register its table name in `App\Services\DatabaseManagerService::MASTER_TABLES`. This guarantees 100% complete all-time replication so that relational integrity and local foreign keys never break.
- **Transactions & Logs (Default 14-day sliding window)**:
  - Entities: `pesanan`, `item_pesanan`, `surat_jalan`, `arus_kas`, `absensi`, `produksi_harian`, `penarikan_gaji`, `kasbon`, `potongan_kasbon`, `tabungan`, `transaksi_tabungan`, `penggajian`, `rincian_penggajian`, `pembelian`, `item_pembelian`, `log_aktivitas`, `mutasi_stok_harian`, etc.
  - **MANDATORY RULE FOR NEW TRANSACTION TABLES**: Any new transactional, journal, movement, or activity log table **MUST** include at least one standard timestamp column (`created_at`, `dibuat_pada`, or a `tanggal_*` column) with an index. This ensures the automated 14-day windowing query detects it seamlessly, keeping local sync lightning-fast (~3–5s) and memory footprint minimal.

### 4.2 Execution Protocols & Timeout Prevention
- **Standard CLI Path**: Execute via `php bin/sync_db.php --mode=14d` (default) or `--mode=full` (all-time), or 1-click Windows runner `bin\sync-db-from-live.bat`.
- **Large Datasets (>50,000 rows)**: NEVER attempt full replication through the web browser UI (prone to HTTP gateway timeouts). Always utilize the CLI / batch runner which employs memory-safe chunking (500 rows per batch) and zero execution timeouts.

### 4.3 Target Safety Invariant (Zero Production Overwrite)
- The replication safety guard (`isTargetSafe` in `DatabaseManagerService.php`) strictly asserts that target is `127.0.0.1` and `kerensnack_erp_local`. Under NO circumstances should this invariant check be bypassed or altered. Production Supabase must remain strictly read-only (`SET default_transaction_read_only = on;`).

## Pre-flight Completion Checklist
- [ ] Sequentially numbered migration file wrapped in `BEGIN; ... COMMIT;`
- [ ] `01_schema.sql` & `02_triggers_and_rpc.sql` fully synchronized
- [ ] `ALTER TABLE public.table_name ENABLE ROW LEVEL SECURITY;` on every new table
- [ ] `SET search_path = public, pg_temp` included on every `CREATE/ALTER FUNCTION`
- [ ] `REVOKE EXECUTE ... FROM PUBLIC, anon, authenticated;` on all triggers, event triggers (e.g. `rls_auto_enable`), and internal functions (Linter 0028 & 0029)
- [ ] Views querying RLS tables hardened via version-aware `security_invoker = true` block
- [ ] Sensitive/financial tables have zero public GRANT (`anon`/`authenticated`)
- [ ] New master/lookup tables registered in `DatabaseManagerService::MASTER_TABLES`
- [ ] New transaction/log tables include standard timestamp column (`created_at` or `tanggal_*`)
- [ ] Stage 1 Local migration verified (`php bin/migrate.php --target=local`)
- [ ] Stage 2 Cloud Live migration deployed (`php bin/migrate.php --target=live`)
- [ ] Stage 3 Dual-database status verified (`php bin/migrate.php --status`)
