-- ==============================================================================
-- KEREN ONE MIGRATION: 93_create_schema_migrations_table.sql
-- Standarisasi Pelacak Riwayat Migrasi Otomatis (Automated Migration History)
-- Digunakan oleh bin/migrate.php untuk verifikasi status migrasi dual-database.
-- ==============================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS public.schema_migrations (
    version VARCHAR(255) PRIMARY KEY,
    migrated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    batch INTEGER NOT NULL DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_schema_migrations_version ON public.schema_migrations(version);
CREATE INDEX IF NOT EXISTS idx_schema_migrations_batch ON public.schema_migrations(batch);

-- Izin akses: Supabase PostgREST & Internal PDO
REVOKE ALL ON public.schema_migrations FROM PUBLIC, anon;
GRANT SELECT ON public.schema_migrations TO authenticated;
GRANT ALL ON public.schema_migrations TO postgres, service_role;

COMMIT;
