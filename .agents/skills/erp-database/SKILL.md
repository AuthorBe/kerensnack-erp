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
- Avoid PostgreSQL 15+ exclusive parameters (e.g., `security_invoker`) in canonical DDL to maintain backwards compatibility with local PostgreSQL environments.

## 3. Supabase PostgREST Permissions (Post-October 30, 2026 Ready)

**`SECURITY DEFINER` functions must be locked down** (revoke public access, grant strictly to `postgres` and `service_role`):
```sql
REVOKE EXECUTE ON FUNCTION public.fn_function_name(...) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_function_name(...) TO postgres, service_role;
```

**New tables accessed directly by public SDK/API** (Mobile App / Client JS): include explicit GRANT and RLS:
```sql
GRANT SELECT, INSERT ON public.new_table_name TO authenticated;
GRANT ALL ON public.new_table_name TO service_role;
ALTER TABLE public.new_table_name ENABLE ROW LEVEL SECURITY;
```

**Sensitive & financial tables (Zero Public Exposure)**: financial, cash, user, and system-settings tables are **strictly forbidden** from being GRANTed to `anon` / `authenticated`. Database access must be strictly handled through the PHP PDO backend as the database owner.

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
- [ ] `SECURITY DEFINER` functions REVOKEd/GRANTed; sensitive tables have zero public GRANT
- [ ] New master/lookup tables registered in `DatabaseManagerService::MASTER_TABLES`
- [ ] New transaction/log tables include standard timestamp column (`created_at` or `tanggal_*`)
