-- ==============================================================================
-- MIGRATION 50: SEED USER DEVELOPER (ROOT SISTEM)
-- Tujuan: Memastikan tepat 1 akun developer tersedia setelah fresh clean.
--         Jika sudah ada (dilindungi trigger dari migration 49),
--         hanya update info dasarnya tanpa mengubah password yang ada.
--         Jika belum ada (kasus fresh install), buat baru dengan password default.
-- 
-- PENTING: Ganti password setelah pertama kali login!
--          Default password: developer123
-- ==============================================================================

DO $$
DECLARE
    v_dev_role_id    UUID;
    v_dev_user_id    UUID;
    v_dev_count      INT;
    v_default_pass   TEXT;
BEGIN
    -- Cari ID role developer
    SELECT id INTO v_dev_role_id FROM public.peran WHERE nama_peran = 'developer' LIMIT 1;

    IF v_dev_role_id IS NULL THEN
        RAISE EXCEPTION 'GAGAL: Peran developer tidak ditemukan di tabel public.peran. Pastikan migration 06_migration_rbac_v3.sql sudah dijalankan.';
    END IF;

    -- Cek apakah user developer sudah ada
    SELECT COUNT(*) INTO v_dev_count FROM public.pengguna WHERE peran_id = v_dev_role_id;
    SELECT id INTO v_dev_user_id FROM public.pengguna WHERE peran_id = v_dev_role_id LIMIT 1;

    IF v_dev_count >= 1 THEN
        -- Developer sudah ada — hanya pastikan data dasar benar
        -- (Tidak mengubah password karena trigger melindungi role dan status_aktif)
        UPDATE public.pengguna
        SET
            nama_lengkap  = 'Developer',
            nama_pengguna = 'developer',
            posisi        = 'developer',
            status_aktif  = TRUE,
            diubah_pada   = NOW()
        WHERE peran_id = v_dev_role_id;

        RAISE NOTICE 'OK: Akun developer sudah ada (ID: %). Data dasar dikonfirmasi.', v_dev_user_id;
        RAISE NOTICE '    nama_pengguna : developer';
        RAISE NOTICE '    posisi        : developer';
        RAISE NOTICE '    status_aktif  : TRUE';
        RAISE NOTICE '    Password      : TIDAK DIUBAH (gunakan password lama atau reset manual via Supabase Auth jika diperlukan)';

    ELSE
        -- Developer belum ada — buat baru dengan password default
        -- Hash password menggunakan pgcrypto (sudah ter-install via extension di migration 01)
        v_default_pass := crypt('developer123', gen_salt('bf', 10));

        INSERT INTO public.pengguna (
            nama_lengkap,
            nama_pengguna,
            kata_sandi,
            peran_id,
            posisi,
            status_aktif,
            dibuat_pada,
            diubah_pada
        ) VALUES (
            'Developer',
            'developer',
            v_default_pass,
            v_dev_role_id,
            'developer',
            TRUE,
            NOW(),
            NOW()
        )
        RETURNING id INTO v_dev_user_id;

        RAISE NOTICE '========================================';
        RAISE NOTICE 'AKUN DEVELOPER BARU DIBUAT:';
        RAISE NOTICE '  ID            : %', v_dev_user_id;
        RAISE NOTICE '  Username      : developer';
        RAISE NOTICE '  Password      : developer123';
        RAISE NOTICE '  *** WAJIB GANTI PASSWORD SETELAH LOGIN PERTAMA! ***';
        RAISE NOTICE '========================================';
    END IF;

END $$;

-- Verifikasi akhir
SELECT
    p.id,
    p.nama_lengkap,
    p.nama_pengguna,
    p.posisi,
    p.status_aktif,
    pr.nama_peran,
    p.dibuat_pada
FROM public.pengguna p
JOIN public.peran pr ON pr.id = p.peran_id
WHERE pr.nama_peran = 'developer';
