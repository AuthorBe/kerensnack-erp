--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.3
-- Dumped by pg_dump version 9.5.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

--
-- Name: tbl_acc_sa; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_acc_sa (
    kodeacc character varying(30) NOT NULL,
    tanggal date,
    matauang character varying(20),
    rate numeric(35,20) DEFAULT 0,
    jumlah numeric(20,3) DEFAULT 0,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone
);


ALTER TABLE public.tbl_acc_sa OWNER TO sysi5adm;

--
-- Name: tbl_acc_tmplrnr; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_acc_tmplrnr (
    kodeacc character varying(50),
    urut integer DEFAULT 0,
    tipeacc character varying(5),
    sub1 character varying(100),
    sub2 character varying(100),
    sub3 character varying(100),
    sub4 character varying(100),
    sub5 character varying(100),
    sub6 character varying(100),
    nilai numeric(35,20),
    setsub integer,
    usergen character varying(50)
);


ALTER TABLE public.tbl_acc_tmplrnr OWNER TO sysi5adm;

--
-- Name: tbl_accdepositdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_accdepositdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer,
    notransaksi character varying(50),
    kodeacc character varying(30),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    jumlah numeric(20,3) DEFAULT 0,
    dateupd timestamp(6) without time zone
);


ALTER TABLE public.tbl_accdepositdt OWNER TO sysi5adm;

--
-- Name: tbl_accdeposithd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_accdeposithd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kodeacc character varying(30),
    kodeaccto character varying(30),
    tanggal timestamp(6) without time zone,
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    tipe character varying(30),
    jumlah numeric(20,3) DEFAULT 0,
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    shiftkerja character varying(20),
    kodesupel character varying(50),
    tipetrs character varying(30),
    bc_trf_sts boolean DEFAULT false,
    userizin character varying(50)
);


ALTER TABLE public.tbl_accdeposithd OWNER TO sysi5adm;

--
-- Name: tbl_accjurnal; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_accjurnal (
    iddetail character varying(150) NOT NULL,
    nourut integer,
    tipeinput character varying(5),
    notransaksi character varying(150),
    tanggal timestamp without time zone,
    kodeacc character varying(30),
    jenis character varying(20),
    keterangan text,
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    jumlah numeric(35,20) DEFAULT 0,
    posisi character varying(5),
    debet numeric(35,20) DEFAULT 0,
    kredit numeric(35,20) DEFAULT 0,
    kantor character varying(50),
    modul character varying(20),
    kategori_kas character varying(20),
    kasbank boolean
);


ALTER TABLE public.tbl_accjurnal OWNER TO sysi5adm;

--
-- Name: tbl_acckasdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_acckasdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer,
    notransaksi character varying(50),
    kodeacc character varying(30),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    jumlah numeric(20,3) DEFAULT 0,
    dateupd timestamp without time zone,
    keterangan text,
    kategori_kas character varying(20),
    kasbank boolean
);


ALTER TABLE public.tbl_acckasdt OWNER TO sysi5adm;

--
-- Name: tbl_acckashd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_acckashd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kodeacc character varying(30),
    kodeaccto character varying(30),
    tanggal timestamp without time zone,
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    tipe character varying(30),
    jumlah numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    shiftkerja character varying(20),
    bc_trf_sts boolean DEFAULT false,
    kategori_kas character varying(20),
    userizin character varying(50)
);


ALTER TABLE public.tbl_acckashd OWNER TO sysi5adm;

--
-- Name: tbl_acctmpns; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_acctmpns (
    kodeacc character varying(30),
    kelompok character varying(5),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    rdebet numeric(20,3) DEFAULT 0,
    rkredit numeric(20,3) DEFAULT 0,
    debet numeric(20,3) DEFAULT 0,
    kredit numeric(20,3) DEFAULT 0,
    pdebet numeric(20,3) DEFAULT 0,
    pkredit numeric(20,3) DEFAULT 0,
    tdebet numeric(20,3) DEFAULT 0,
    tkredit numeric(20,3) DEFAULT 0,
    lrdebet numeric(20,3) DEFAULT 0,
    lrkredit numeric(20,3) DEFAULT 0,
    ndebet numeric(20,3) DEFAULT 0,
    nkredit numeric(20,3) DEFAULT 0,
    usergen character varying(50)
);


ALTER TABLE public.tbl_acctmpns OWNER TO sysi5adm;

--
-- Name: tbl_alamatkirim; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_alamatkirim (
    id character varying(150) NOT NULL,
    kode_supel character varying(50),
    kontak character varying(150),
    alamat character varying(200),
    kota character varying(100),
    telepon character varying(200),
    kotatujuan character varying(100),
    kodekantor character varying(50),
    subwilasal character varying(100),
    subwiltujuan character varying(100),
    provinsi_asal character varying(50),
    provinsi_tujuan character varying(50),
    id_provinsi_asal character varying(50),
    id_provinsi_tujuan character varying(50),
    id_kota_asal character varying(50),
    id_kota_tujuan character varying(50),
    id_kecamatan_asal character varying(50),
    id_kecamatan_tujuan character varying(50)
);


ALTER TABLE public.tbl_alamatkirim OWNER TO sysi5adm;

--
-- Name: tbl_bank; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_bank (
    kodebank character varying(30) NOT NULL,
    namabank character varying(100),
    acc_kd character varying(30),
    acc_kk character varying(30)
);


ALTER TABLE public.tbl_bank OWNER TO sysi5adm;

--
-- Name: tbl_byrhutangdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrhutangdt (
    iddetail character varying(150) NOT NULL,
    notransaksi character varying(50),
    notrsmasuk character varying(50),
    tipe character varying(20),
    matauang character varying(50),
    ratetrs numeric(35,20) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_total numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    dateupd timestamp without time zone
);


ALTER TABLE public.tbl_byrhutangdt OWNER TO sysi5adm;

--
-- Name: tbl_byrhutanghd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrhutanghd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    totalbayar numeric(20,3) DEFAULT 0,
    totalpotongan numeric(20,3),
    acc_bayar character varying(30),
    acc_pot character varying(30),
    carabayar character varying(5) DEFAULT 'TN'::character varying,
    byr_krd_jt timestamp without time zone,
    nomor character varying(50),
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    shiftkerja character varying(20),
    stslunas boolean DEFAULT false,
    tgllunas_cbg timestamp(0) without time zone,
    bc_trf_sts boolean DEFAULT false,
    userizin character varying(50)
);


ALTER TABLE public.tbl_byrhutanghd OWNER TO sysi5adm;

--
-- Name: tbl_byrhutangitem; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrhutangitem (
    iddetail character varying(150),
    iddetailitem character varying(150),
    notransaksi character varying(50),
    kodeitem character varying(100),
    jmlretur numeric(20,3),
    jmllaku numeric(20,3)
);


ALTER TABLE public.tbl_byrhutangitem OWNER TO sysi5adm;

--
-- Name: tbl_byrhutangkonsidt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrhutangkonsidt (
    iddetail character varying(150) NOT NULL,
    notransaksi character varying(50),
    notrsmasuk character varying(50),
    tipe character varying(20),
    matauang character varying(50),
    ratetrs numeric(35,20) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_total numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    dateupd timestamp(6) without time zone
);


ALTER TABLE public.tbl_byrhutangkonsidt OWNER TO sysi5adm;

--
-- Name: tbl_byrhutangkonsihd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrhutangkonsihd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    tanggal timestamp(6) without time zone,
    tipe character varying(20),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    totalbayar numeric(20,3) DEFAULT 0,
    totalpotongan numeric(20,3),
    acc_bayar character varying(30),
    acc_pot character varying(30),
    carabayar character varying(5) DEFAULT 'TN'::character varying,
    byr_krd_jt timestamp(6) without time zone,
    nomor character varying(50),
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    shiftkerja character varying(20),
    stslunas boolean DEFAULT false,
    xx numeric(20,3),
    tgllunas_cbg timestamp(0) without time zone,
    bc_trf_sts boolean DEFAULT false,
    userizin character varying(50)
);


ALTER TABLE public.tbl_byrhutangkonsihd OWNER TO sysi5adm;

--
-- Name: tbl_byrkomisislsdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrkomisislsdt (
    iddetail character varying(150) NOT NULL,
    notransaksi character varying(50),
    notrsmasuk character varying(50),
    tipe character varying(20),
    matauang character varying(50),
    ratetrs numeric(35,20) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_total numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    dateupd timestamp(6) without time zone,
    kodesupel character varying(50)
);


ALTER TABLE public.tbl_byrkomisislsdt OWNER TO sysi5adm;

--
-- Name: tbl_byrkomisislshd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrkomisislshd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    tanggal timestamp(6) without time zone,
    tipe character varying(20),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    totalbayar numeric(20,3) DEFAULT 0,
    acc_bayar character varying(30),
    acc_komisi_sales character varying(30),
    carabayar character varying(5) DEFAULT 'TN'::character varying,
    byr_krd_jt timestamp(6) without time zone,
    nomor character varying(50),
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    shiftkerja character varying(20),
    stslunas boolean DEFAULT false,
    periodetgl1 timestamp(6) without time zone,
    periodetgl2 timestamp(6) without time zone,
    tgllunas_cbg timestamp(0) without time zone,
    bc_trf_sts boolean DEFAULT false,
    userizin character varying(50)
);


ALTER TABLE public.tbl_byrkomisislshd OWNER TO sysi5adm;

--
-- Name: tbl_byrpiutangdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrpiutangdt (
    iddetail character varying(150) NOT NULL,
    notransaksi character varying(50),
    notrsmasuk character varying(50),
    tipe character varying(20),
    matauang character varying(50),
    ratetrs numeric(35,20) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_total numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    dateupd timestamp without time zone
);


ALTER TABLE public.tbl_byrpiutangdt OWNER TO sysi5adm;

--
-- Name: tbl_byrpiutanghd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrpiutanghd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    totalbayar numeric(20,3) DEFAULT 0,
    totalpotongan numeric(20,3),
    acc_bayar character varying(30),
    acc_pot character varying(30),
    carabayar character varying(5) DEFAULT 'TN'::character varying,
    byr_krd_jt timestamp without time zone,
    nomor character varying(50),
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    shiftkerja character varying(20),
    stslunas boolean DEFAULT false,
    tgllunas_cbg timestamp(0) without time zone,
    bc_trf_sts boolean DEFAULT false,
    userizin character varying(50)
);


ALTER TABLE public.tbl_byrpiutanghd OWNER TO sysi5adm;

--
-- Name: tbl_byrpiutangkonsidt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrpiutangkonsidt (
    iddetail character varying(150) NOT NULL,
    notransaksi character varying(50),
    notrsmasuk character varying(50),
    tipe character varying(5),
    matauang character varying(50),
    ratetrs numeric(35,20) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_total numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    dateupd timestamp(6) without time zone
);


ALTER TABLE public.tbl_byrpiutangkonsidt OWNER TO sysi5adm;

--
-- Name: tbl_byrpiutangkonsihd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_byrpiutangkonsihd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    tanggal timestamp(6) without time zone,
    tipe character varying(5),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    totalbayar numeric(20,3) DEFAULT 0,
    totalpotongan numeric(20,3),
    acc_bayar character varying(30),
    acc_pot character varying(30),
    carabayar character varying(5) DEFAULT 'TN'::character varying,
    byr_krd_jt timestamp(6) without time zone,
    nomor character varying(50),
    keterangan text,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    shiftkerja character varying(20),
    stslunas boolean DEFAULT false,
    tgllunas_cbg timestamp(0) without time zone,
    bc_trf_sts boolean DEFAULT false,
    userizin character varying(50)
);


ALTER TABLE public.tbl_byrpiutangkonsihd OWNER TO sysi5adm;

--
-- Name: tbl_conf; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_conf (
    confname character varying(50) NOT NULL,
    confvalue character varying(254),
    confblob bytea
);


ALTER TABLE public.tbl_conf OWNER TO sysi5adm;

--
-- Name: tbl_emoney; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_emoney (
    kodeprod character varying(30) NOT NULL,
    namaprod character varying(100),
    acc_prod character varying(30)
);


ALTER TABLE public.tbl_emoney OWNER TO sysi5adm;

--
-- Name: tbl_formatnosp; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_formatnosp (
    trid character varying(5) NOT NULL,
    nomor bigint,
    slot1 character varying(10),
    slot2 character varying(10),
    slot3 character varying(10),
    sep1 character varying(2),
    sep2 character varying(2),
    numdgt integer,
    lastnom character varying(200)
);


ALTER TABLE public.tbl_formatnosp OWNER TO sysi5adm;

--
-- Name: tbl_formatnotr; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_formatnotr (
    trid character varying(10) NOT NULL,
    nomor bigint DEFAULT (0)::bigint,
    slot1 character varying(10),
    slot2 character varying(10),
    slot3 character varying(10),
    slot4 character varying(10),
    slot5 character varying(10),
    sep1 character varying(2),
    sep2 character varying(2),
    sep3 character varying(2),
    sep4 character varying(2),
    resetid character varying(10),
    numdgt integer DEFAULT 0,
    notransaksi character varying(50),
    kantor character varying(50) NOT NULL,
    lastgen timestamp(0) without time zone
);


ALTER TABLE public.tbl_formatnotr OWNER TO sysi5adm;

--
-- Name: tbl_hupi_sa; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_hupi_sa (
    kodesupel character varying(50),
    tanggal timestamp without time zone,
    kode_acc character varying(30),
    kodemu character varying(50),
    jumlah numeric(20,3),
    tipe character varying(20),
    tipetrs character varying(20)
);


ALTER TABLE public.tbl_hupi_sa OWNER TO sysi5adm;

--
-- Name: tbl_ikdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_ikdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jumlah numeric(35,20) DEFAULT 0,
    jmlpesan numeric(35,20) DEFAULT 0,
    satuan character varying(50),
    harga numeric(35,20) DEFAULT 0,
    potongan numeric(35,20) DEFAULT 0,
    potongan2 numeric(35,20) DEFAULT 0,
    potongan3 numeric(35,20) DEFAULT 0,
    potongan4 numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    jmlrmasuk numeric(20,3) DEFAULT 0,
    jmlkeluar numeric(20,3) DEFAULT 0,
    jmlrkeluar numeric(20,3) DEFAULT 0,
    jmlsisa numeric(20,3) DEFAULT 0,
    jmlkonsibayar numeric(20,3) DEFAULT 0,
    idorder character varying(150),
    dateupd timestamp without time zone,
    idtrsretur character varying(150),
    jmlretur numeric(20,3) DEFAULT 0,
    detinfo text,
    notrsretur character varying(100),
    potpiutang numeric(50,3),
    jmlkonversi numeric(50,3) DEFAULT 0,
    jmlterimajadi numeric(20,3) DEFAULT 0,
    sistemhargajual character varying(1),
    tipepromo character varying(15) DEFAULT 'N'::character varying,
    jmlgratis numeric(20,3) DEFAULT 0,
    itempromo character varying(100),
    satuanpromo character varying(50),
    hppdasar numeric(35,20) DEFAULT 0,
    tebus boolean DEFAULT false,
    tglexp timestamp(6) without time zone,
    kodeprod character varying(100),
    jenis_pajak character varying(10),
    taxppnbm numeric(20,3)
);


ALTER TABLE public.tbl_ikdt OWNER TO sysi5adm;

--
-- Name: tbl_ikhd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_ikhd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantordari character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    notrsorder character varying(50),
    kodesupel character varying(50),
    kodesales character varying(50),
    kodesales2 character varying(50),
    kodesales3 character varying(50),
    kodesales4 character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    keterangan text,
    totalitem numeric(20,3) DEFAULT 0,
    totalitempesan numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    potfaktur numeric(25,10) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    biayalain numeric(20,3) DEFAULT 0,
    dppesanan numeric(20,3) DEFAULT 0,
    prpajak numeric(10,3) DEFAULT 0,
    totalakhir numeric(20,3) DEFAULT 0,
    carabayar character varying(20),
    jmltunai numeric(20,3) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    jmldebit numeric(20,3) DEFAULT 0,
    jmlkk numeric(20,3) DEFAULT 0,
    komisi1 numeric(20,3) DEFAULT 0,
    komisi2 numeric(20,3) DEFAULT 0,
    komisi3 numeric(20,3) DEFAULT 0,
    komisi4 numeric(20,3) DEFAULT 0,
    notrsretur character varying(100),
    ppn character varying(30),
    totalkotagih numeric(20,3) DEFAULT 0,
    acc_potongan character varying(30),
    acc_pajak character varying(30),
    acc_biayalain character varying(30),
    acc_tunai character varying(30),
    acc_kredit character varying(30),
    acc_sales character varying(30),
    acc_hpp character varying(30),
    acc_debit character varying(30),
    acc_kk character varying(30),
    acc_deposit character varying(30),
    acc_sales_hut character varying(30),
    acc_biaya_pot character varying(30),
    acc_dppesanan character varying(30),
    acc_beda_cab character varying(30),
    byr_krd_jt timestamp without time zone,
    byr_krd_no character varying(30),
    byr_debit_bank character varying(30),
    byr_kk_bank character varying(30),
    byr_debit_no character varying(100),
    byr_kk_no character varying(100),
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    potnomfaktur numeric(20,3) DEFAULT 0,
    jmldeposit numeric(20,3) DEFAULT 0,
    point_ik numeric(20,3) DEFAULT 0,
    point_sts integer DEFAULT 0,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    tanggal_sa date,
    biaya_msk_total boolean,
    compname character varying(255),
    shiftkerja character varying(20),
    nofp character varying(100),
    byr_komisi1 boolean,
    byr_komisi2 boolean,
    byr_komisi3 boolean,
    byr_komisi4 boolean,
    point_notrans character varying(50),
    totalterimajadi numeric(20,3) DEFAULT 0,
    ak_kotatujuan character varying(100),
    opsikirim character varying(5) DEFAULT 1,
    bc_trf_sts boolean DEFAULT false,
    ambilnomor character varying(50),
    jumlah_cetak numeric(5,3) DEFAULT 0,
    status_online boolean DEFAULT false,
    compname_online character varying(255),
    user_online character varying(50),
    mode_retur character varying(5),
    jmlemoney numeric(20,3) DEFAULT 0,
    byr_emoney_no character varying(100),
    byr_emoney_prod character varying(30),
    acc_emoney character varying(30),
    selisihpembulatan numeric(20,3) DEFAULT 0,
    acc_pend_pembulatan character varying(30),
    kodevoucher character varying(50),
    opsikembalian character varying(5),
    jmlopkembali numeric(20,3),
    acc_donasi character varying(30),
    krd_jml_byr_ls numeric(20,3) DEFAULT 0,
    krd_jml_pot_ls numeric(20,3) DEFAULT 0,
    jenis_pajak character varying(10),
    trxcode character varying(10),
    addinfo character varying(10),
    customdoc text,
    refdesc text,
    facilitystamp character varying(10),
    customdocmonthyear character varying(10),
    biaya_msk_total2 character varying(10),
    userizin character varying(50),
    prpajakppnbm numeric(10,3),
    pajakppnbm numeric(20,3),
    prpajakpph23 numeric(10,3),
    pajakpph23 numeric(20,3),
    acc_pajakppnbm character varying(30),
    acc_pajakpph23 character varying(30)
);


ALTER TABLE public.tbl_ikhd OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_ikhd.acc_potongan; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_potongan IS 'POTONGAN';


--
-- Name: COLUMN tbl_ikhd.acc_pajak; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_pajak IS 'PAJAK';


--
-- Name: COLUMN tbl_ikhd.acc_biayalain; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_biayalain IS 'BIAYA';


--
-- Name: COLUMN tbl_ikhd.acc_tunai; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_tunai IS 'BAYAR TUNAI';


--
-- Name: COLUMN tbl_ikhd.acc_kredit; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_kredit IS 'BAYAR KREDIT';


--
-- Name: COLUMN tbl_ikhd.acc_sales; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_sales IS 'SALES';


--
-- Name: COLUMN tbl_ikhd.acc_deposit; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_deposit IS 'BAYAR DEPOSIT';


--
-- Name: COLUMN tbl_ikhd.acc_sales_hut; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_ikhd.acc_sales_hut IS 'HUTANG SALES';


--
-- Name: tbl_ikrakitan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_ikrakitan (
    iddetail character varying(150) NOT NULL,
    iddetailtrs character varying(150),
    notransaksi character varying(50),
    tipe character varying(20),
    kodeitem character varying(100),
    kodeitemrakitan character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    jumlahtrs numeric(20,3) DEFAULT 0,
    satuantrs character varying(50),
    dateupd timestamp without time zone,
    jenisrakit character varying(20),
    totalhppitem numeric(20,3) DEFAULT 0,
    jmlkonversi numeric(50,3) DEFAULT 0,
    hppdasar numeric(35,20) DEFAULT 0
);


ALTER TABLE public.tbl_ikrakitan OWNER TO sysi5adm;

--
-- Name: tbl_imdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_imdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jumlah numeric(35,20) DEFAULT 0,
    jmlpesan numeric(35,20) DEFAULT 0,
    satuan character varying(50),
    harga numeric(35,20) DEFAULT 0,
    potongan numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    hargadsr numeric(20,3) DEFAULT 0,
    satuandsr character varying(50),
    jmlmasuk numeric(20,3) DEFAULT 0,
    jmlrmasuk numeric(20,3) DEFAULT 0,
    jmlkeluar numeric(20,3) DEFAULT 0,
    jmlrkeluar numeric(20,3) DEFAULT 0,
    jmlsisa numeric(20,3) DEFAULT 0,
    jmlkonsibayar numeric(20,3) DEFAULT 0,
    jmlretur numeric(20,3) DEFAULT 0,
    tglexp timestamp without time zone,
    idtrsretur character varying(150),
    kodeprod character varying(100),
    idorder character varying(150),
    dateupd timestamp without time zone,
    sakantor character varying(50),
    detinfo text,
    pothutang numeric(50,3),
    notrsretur character varying(100),
    jmlkonversi numeric(50,3) DEFAULT 0,
    jmlprosesrakit numeric(20,3) DEFAULT 0,
    jmltagihki numeric(20,3) DEFAULT 0,
    potongan2 numeric(35,20) DEFAULT 0,
    potongan3 numeric(35,20) DEFAULT 0,
    potongan4 numeric(35,20) DEFAULT 0,
    hppdasar numeric(35,20) DEFAULT 0,
    jenis_pajak character varying(10)
);


ALTER TABLE public.tbl_imdt OWNER TO sysi5adm;

--
-- Name: tbl_imhd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_imhd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantortujuan character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    notrsorder character varying(50),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    keterangan text,
    totalitem numeric(20,3) DEFAULT 0,
    totalitempesan numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    potfaktur numeric(25,10) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    biayalain numeric(20,3) DEFAULT 0,
    prpajak numeric(10,3) DEFAULT 0,
    dppesanan numeric(20,3) DEFAULT 0,
    jmldeposit numeric(20,3) DEFAULT 0,
    totalakhir numeric(20,3) DEFAULT 0,
    carabayar character varying(20),
    jmltunai numeric(20,3) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    potnomfaktur numeric(20,3) DEFAULT 0,
    byr_krd_jt timestamp without time zone,
    byr_krd_no character varying(30),
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    ppn character varying(30),
    notrsretur character varying(100),
    acc_potongan character varying(30),
    acc_pajak character varying(30),
    acc_biayalain character varying(30),
    acc_tunai character varying(30),
    acc_kredit character varying(30),
    acc_hpp character varying(30),
    acc_deposit character varying(30),
    acc_dppesanan character varying(30),
    acc_biaya_pot character varying(30),
    acc_beda_cab character varying(30),
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    biaya_msk_total boolean,
    compname character varying(255),
    shiftkerja character varying(20),
    tanggal_sa date,
    bc_trf_sts boolean DEFAULT false,
    tottagihki numeric(20,3) DEFAULT 0,
    totitemretur numeric(20,3) DEFAULT 0,
    swt_sa_sts boolean DEFAULT false,
    prpotfaktur numeric(25,10),
    nofp character varying(100),
    status_online boolean DEFAULT false,
    compname_online character varying(255),
    user_online character varying(50),
    mode_retur character varying(5),
    jenis_pajak character varying(10),
    biaya_msk_total2 character varying(10),
    userizin character varying(50)
);


ALTER TABLE public.tbl_imhd OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_imhd.acc_potongan; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_imhd.acc_potongan IS 'POTONGAN';


--
-- Name: COLUMN tbl_imhd.acc_pajak; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_imhd.acc_pajak IS 'PAJAK';


--
-- Name: COLUMN tbl_imhd.acc_biayalain; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_imhd.acc_biayalain IS 'BIAYA';


--
-- Name: tbl_imrakitan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_imrakitan (
    iddetail character varying(150) NOT NULL,
    iddetailtrs character varying(150),
    notransaksi character varying(50),
    tipe character varying(20),
    kodeitem character varying(100),
    kodeitemrakitan character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    jumlahtrs numeric(20,3) DEFAULT 0,
    satuantrs character varying(50),
    dateupd timestamp without time zone,
    jmlkonversi numeric(50,3) DEFAULT 0
);


ALTER TABLE public.tbl_imrakitan OWNER TO sysi5adm;

--
-- Name: tbl_infodb; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_infodb (
    versidb character varying(255),
    versiupdate character varying(20)
);


ALTER TABLE public.tbl_infodb OWNER TO sysi5adm;

--
-- Name: tbl_item; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item (
    kodeitem character varying(100) NOT NULL,
    namaitem text,
    jenis character varying(50),
    tipe character varying(15) DEFAULT 'Y'::character varying,
    matauang character varying(50),
    serial character varying(15) DEFAULT 'N'::character varying,
    konsinyasi character varying(15) DEFAULT 'N'::character varying,
    stokmin numeric(20,3) DEFAULT 0,
    sistemhargajual character varying(1) DEFAULT 'J'::character varying,
    opsihargajual boolean DEFAULT true,
    rak character varying(100),
    satuan character varying(50),
    hargapokok numeric(35,20) DEFAULT 0,
    prhargajual1 numeric(20,3) DEFAULT 0,
    hargajual1 numeric(20,3) DEFAULT 0,
    keterangan text,
    supplier1 character varying(50),
    supplier2 character varying(50),
    supplier3 character varying(50),
    gambar bytea,
    statusjual character varying(15),
    merek character varying(50),
    hppsys character varying(10),
    sistempajak numeric DEFAULT 0,
    opsiflexhargajual boolean DEFAULT false,
    hargarakit numeric(20,3) DEFAULT 0,
    statushapus character varying(15),
    stok numeric(20,3) DEFAULT 0,
    dept character varying(50),
    pendingin character varying(15) DEFAULT 'N'::character varying,
    acc_hpp character varying(30),
    acc_pendapatan character varying(30),
    acc_persediaan character varying(30),
    acc_jasa character varying(30),
    acc_noninventory character varying(30),
    acc_perbahanbaku character varying(30),
    acc_bytenagakerja character varying(30),
    acc_byoverhead character varying(30),
    dateupd timestamp without time zone,
    tmphp numeric(20,3) DEFAULT 0,
    tmpjml numeric(20,3) DEFAULT 0,
    tmpnilai numeric(20,3) DEFAULT 0,
    gambarfiles text,
    tanggal_add timestamp without time zone,
    opsihargarakitan boolean DEFAULT false,
    nonpajakex boolean DEFAULT false,
    opsidefhargapokok boolean DEFAULT false,
    jenis_pajak character varying(10),
    brgjasa_refcode character varying(10),
    opt character varying(10)
);


ALTER TABLE public.tbl_item OWNER TO sysi5adm;

--
-- Name: tbl_item_ik; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_ik (
    iddetail character varying(150) NOT NULL,
    iddetailim character varying(150) NOT NULL,
    iddetailtrs character varying(150),
    notransaksi character varying(100),
    kodekantor character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    kodeitem character varying(100),
    jumlahdasar numeric(20,3) DEFAULT 0,
    satuandasar character varying(50),
    hargadasar numeric(35,20) DEFAULT 0,
    jmlretur numeric(20,3) DEFAULT 0,
    jmlkotagih numeric(20,3) DEFAULT 0,
    iddetailserial character varying(150),
    origin_tipe character varying(20),
    origin_iddt character varying(150),
    ori_iddetail character varying(150),
    ori_tipe character varying(20),
    noserial character varying(255),
    CONSTRAINT tbl_item_ik_chk_notrsnull CHECK ((((notransaksi)::text <> NULL::text) OR ((notransaksi)::text <> ''::text)))
);


ALTER TABLE public.tbl_item_ik OWNER TO sysi5adm;

--
-- Name: tbl_item_ikko; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_ikko (
    iddetail character varying(150) NOT NULL,
    iddetailik character varying(150),
    iddetailtrs character varying(150),
    notransaksi character varying(100),
    kodeitem character varying(100),
    jumlahdasar numeric(35,20) DEFAULT 0,
    hargadasar numeric(35,20) DEFAULT 0,
    iddetailserial character varying(150)
);


ALTER TABLE public.tbl_item_ikko OWNER TO sysi5adm;

--
-- Name: tbl_item_ikret; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_ikret (
    iddetail character varying(150) NOT NULL,
    iddetailik character varying(150),
    iddetailtrs character varying(150),
    notransaksi character varying(100),
    kodeitem character varying(100),
    jumlahdasar numeric(20,3) DEFAULT 0,
    hargadasar numeric(35,20) DEFAULT 0,
    origin_tipe character varying(20),
    origin_iddt character varying(150),
    ori_iddetail character varying(150),
    ori_tipe character varying(20)
);


ALTER TABLE public.tbl_item_ikret OWNER TO sysi5adm;

--
-- Name: tbl_item_im; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_im (
    iddetail character varying(150) NOT NULL,
    iddetailtrs character varying(150),
    notransaksi character varying(100),
    kodekantor character varying(50),
    tanggal timestamp without time zone,
    tgl_trs timestamp without time zone,
    tipe character varying(20),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    kodeitem character varying(100),
    jumlahdasar numeric(20,3) DEFAULT 0,
    satuandasar character varying(50),
    hargadasar numeric(35,20) DEFAULT 0,
    masuk numeric(20,3) DEFAULT 0,
    keluar numeric(20,3) DEFAULT 0,
    remasuk numeric(20,3) DEFAULT 0,
    rekeluar numeric(20,3) DEFAULT 0,
    transfer numeric(20,3) DEFAULT 0,
    sisa numeric(20,3) DEFAULT 0,
    keluar_konsi numeric(20,3) DEFAULT 0,
    rekeluar_konsi numeric(20,3) DEFAULT 0,
    remasuk_konsi numeric(20,3) DEFAULT 0,
    sisa_konsi numeric(20,3) DEFAULT 0,
    flagavg smallint DEFAULT (0)::smallint,
    origin_iddt character varying(150),
    origin_tipe character varying(20),
    ori_iddetail character varying(150),
    ori_tipe character varying(20),
    ori_id_trf character varying(150),
    CONSTRAINT tbl_item_im_chk_notrs_null CHECK ((((notransaksi)::text <> NULL::text) AND ((notransaksi)::text <> ''::text)))
);


ALTER TABLE public.tbl_item_im OWNER TO sysi5adm;

--
-- Name: tbl_item_imret; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_imret (
    iddetail character varying(150) NOT NULL,
    iddetailim character varying(150),
    iddetailtrs character varying(150),
    notransaksi character varying(100),
    kodeitem character varying(100),
    jumlahdasar numeric(20,3) DEFAULT 0,
    hargadasar numeric(35,20) DEFAULT 0,
    idtrsserial character varying(150),
    ori_iddetail character varying(150),
    ori_tipe character varying(20)
);


ALTER TABLE public.tbl_item_imret OWNER TO sysi5adm;

--
-- Name: tbl_item_rekap; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_rekap (
    kodeitem character varying(100),
    kodekantor character varying(50),
    bulan integer,
    tahun integer,
    satuan character varying(50),
    awal numeric(20,3) DEFAULT 0,
    awal_nilai numeric(20,3) DEFAULT 0,
    awal_total numeric(20,3) DEFAULT 0,
    masuk numeric(20,3) DEFAULT 0,
    masuk_nilai numeric(20,3) DEFAULT 0,
    masuk_total numeric(20,3) DEFAULT 0,
    keluar numeric(20,3) DEFAULT 0,
    keluar_nilai numeric(20,3) DEFAULT 0,
    keluar_total numeric(20,3) DEFAULT 0,
    akhir numeric(20,3) DEFAULT 0,
    akhir_nilai numeric(20,3) DEFAULT 0,
    akhir_total numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_item_rekap OWNER TO sysi5adm;

--
-- Name: tbl_item_sa; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_item_sa (
    iddetailtrs character varying(150) NOT NULL,
    notransaksi character varying(100),
    tipe character varying(20),
    kodeitem character varying(100),
    tanggal timestamp without time zone,
    tgl_trs timestamp without time zone,
    nobaris integer DEFAULT 0,
    kodekantor character varying(50),
    jumlah numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    jmlkonversi numeric(20,3) DEFAULT 0,
    hppdasar numeric(35,20) DEFAULT 0,
    jmlretur numeric(35,20) DEFAULT 0
);


ALTER TABLE public.tbl_item_sa OWNER TO sysi5adm;

--
-- Name: tbl_itemdisp; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemdisp (
    iddiskon character varying(50) NOT NULL,
    kodeitemd character varying(100),
    kodeitems character varying(100),
    jenis character varying(50),
    merek character varying(50),
    tgldari timestamp(6) without time zone,
    tglsampai timestamp(6) without time zone,
    pot1 numeric(20,3) DEFAULT 0,
    pot2 numeric(20,3) DEFAULT 0,
    pot3 numeric(20,3) DEFAULT 0,
    pot4 numeric(20,3) DEFAULT 0,
    stsact boolean DEFAULT false,
    tipeper character varying(10),
    jamdari timestamp without time zone,
    jamsampai timestamp without time zone,
    w1 boolean DEFAULT false,
    w2 boolean DEFAULT false,
    w3 boolean DEFAULT false,
    w4 boolean DEFAULT false,
    w5 boolean DEFAULT false,
    w6 boolean DEFAULT false,
    w7 boolean DEFAULT false,
    prioritas numeric(10,0) DEFAULT 0,
    stsvcr boolean DEFAULT false
);


ALTER TABLE public.tbl_itemdisp OWNER TO sysi5adm;

--
-- Name: tbl_itemdispdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemdispdt (
    kodeitem character varying(100),
    satuan character varying(50),
    opsidiskon integer,
    diskon1 numeric(20,3),
    diskon2 numeric(20,3),
    diskon3 numeric(20,3),
    diskon4 numeric(20,3),
    disknom1 numeric(40,20),
    disknom2 numeric(40,20),
    disknom3 numeric(40,20),
    disknom4 numeric(40,20),
    iddiskon character varying(50),
    kgruppel character varying(20)
);


ALTER TABLE public.tbl_itemdispdt OWNER TO sysi5adm;

--
-- Name: tbl_itemhj; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemhj (
    iddetail character varying(150) NOT NULL,
    kodeitem character varying(100),
    tipehj character varying(10),
    jmlsampai numeric(20,3) DEFAULT 0,
    level integer DEFAULT 0,
    prosentase numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    hargajual numeric(35,20) DEFAULT 0,
    dateupd timestamp without time zone
);


ALTER TABLE public.tbl_itemhj OWNER TO sysi5adm;

--
-- Name: tbl_itemjenis; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemjenis (
    jenis character varying(50) NOT NULL,
    ketjenis character varying(100)
);


ALTER TABLE public.tbl_itemjenis OWNER TO sysi5adm;

--
-- Name: tbl_itemketerangan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemketerangan (
    kodeket character varying(50) NOT NULL,
    keterangan character varying(300),
    jenisket character varying(50) NOT NULL
);


ALTER TABLE public.tbl_itemketerangan OWNER TO sysi5adm;

--
-- Name: tbl_itemmerek; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemmerek (
    merek character varying(50) NOT NULL,
    ketmerek character varying(100)
);


ALTER TABLE public.tbl_itemmerek OWNER TO sysi5adm;

--
-- Name: tbl_itemopname; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemopname (
    iddetail character varying(150) NOT NULL,
    periode character varying(20),
    tanggal timestamp without time zone,
    kodeitem character varying(100) NOT NULL,
    kodekantor character varying(50),
    satuan character varying(50),
    jmlsebelum numeric(20,3) DEFAULT 0,
    jmlfisik numeric(20,3) DEFAULT 0,
    jmlselisih numeric(20,3) DEFAULT 0,
    kodeacc character varying(30),
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    harga numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    compname character varying(255),
    jmlkonversi numeric(20,3) DEFAULT 0,
    hppdasar numeric(35,20) DEFAULT 0,
    bc_trf_sts boolean DEFAULT false,
    keterangan text,
    userizin character varying(50)
);


ALTER TABLE public.tbl_itemopname OWNER TO sysi5adm;

--
-- Name: tbl_itempotongan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itempotongan (
    iddetail character varying(150) NOT NULL,
    kodeitem character varying(100),
    kodegrup character varying(50),
    jumlah numeric(20,3) DEFAULT 0,
    pot1 numeric(20,3) DEFAULT 0,
    pot2 numeric(20,3) DEFAULT 0,
    pot3 numeric(20,3) DEFAULT 0,
    pot4 numeric(20,3) DEFAULT 0,
    dateupd timestamp without time zone
);


ALTER TABLE public.tbl_itempotongan OWNER TO sysi5adm;

--
-- Name: tbl_itempromo; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itempromo (
    idpromo character varying(50) NOT NULL,
    kodeitemd character varying(100),
    kodeitems character varying(100),
    jenis character varying(50),
    merek character varying(50),
    tgldari timestamp without time zone,
    tglsampai timestamp without time zone,
    stsact boolean DEFAULT false,
    tipeper character varying(10),
    jamdari timestamp without time zone,
    jamsampai timestamp without time zone,
    w1 boolean DEFAULT false,
    w2 boolean DEFAULT false,
    w3 boolean DEFAULT false,
    w4 boolean DEFAULT false,
    w5 boolean DEFAULT false,
    w6 boolean DEFAULT false,
    w7 boolean DEFAULT false,
    prioritas numeric(10,0) DEFAULT 0
);


ALTER TABLE public.tbl_itempromo OWNER TO sysi5adm;

--
-- Name: tbl_itempromodt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itempromodt (
    kodeitem character varying(100),
    jumlahjual numeric(20,3),
    satuanjual character varying(50),
    jumlahgratis numeric(20,3),
    satuangratis character varying(50),
    idpromo character varying(50),
    kodeitemgr character varying(100),
    kelipatan boolean DEFAULT true,
    tebus boolean DEFAULT false,
    harga numeric(20,3) DEFAULT 0,
    opsigratis character varying(50) DEFAULT '1'::character varying
);


ALTER TABLE public.tbl_itempromodt OWNER TO sysi5adm;

--
-- Name: tbl_itemrakitan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemrakitan (
    iddetail character varying(100) NOT NULL,
    kodeitem character varying(100),
    kodeitemrakitan character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    dateupd timestamp without time zone,
    jenis character varying(20)
);


ALTER TABLE public.tbl_itemrakitan OWNER TO sysi5adm;

--
-- Name: tbl_itemsatuan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemsatuan (
    satuan character varying(50) NOT NULL,
    ketsatuan character varying(100),
    konversi numeric(20,3) DEFAULT 0,
    satuankonversi character varying(50),
    utama boolean DEFAULT false,
    unit_refcode character varying(10)
);


ALTER TABLE public.tbl_itemsatuan OWNER TO sysi5adm;

--
-- Name: tbl_itemsatuanjml; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemsatuanjml (
    iddetail character varying(150) NOT NULL,
    kodeitem character varying(100),
    satuan character varying(30),
    jumlahkonv numeric(20,3) DEFAULT 0,
    kodebarcode character varying(100),
    hargapokok numeric(35,20) DEFAULT 0,
    tipe character varying(20),
    dateupd timestamp without time zone,
    poin numeric(10,0),
    komisisales numeric(20,3)
);


ALTER TABLE public.tbl_itemsatuanjml OWNER TO sysi5adm;

--
-- Name: tbl_itemserial; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemserial (
    noserial character varying(255) NOT NULL,
    kodeitem character varying(100),
    dateupd timestamp without time zone,
    kodekantor character varying(50),
    tipe character varying(20),
    notransaksi character varying(50),
    iddetail character varying(150),
    harga numeric(35,20) DEFAULT 0,
    origin_tipe character varying(20),
    origin_iddt character varying(150)
);


ALTER TABLE public.tbl_itemserial OWNER TO sysi5adm;

--
-- Name: tbl_itemserial_kotag; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemserial_kotag (
    noserial character varying(255) NOT NULL,
    kodeitem character varying(100),
    dateupd timestamp(6) without time zone,
    kodekantor character varying(50),
    tipe character varying(20),
    notransaksi character varying(50),
    iddetail character varying(150),
    harga numeric(20,3) DEFAULT 0,
    iddetailtrs character varying(150),
    notrskonsinyasi character varying(50)
);


ALTER TABLE public.tbl_itemserial_kotag OWNER TO sysi5adm;

--
-- Name: tbl_itemserialdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemserialdt (
    noserial character varying(255),
    tipe character varying(20),
    notransaksi character varying(50),
    iddetail character varying(150),
    kodeitem character varying(100),
    kodekantor character varying(50),
    dateupd timestamp without time zone,
    harga numeric(35,20) DEFAULT 0,
    serialtipe character varying(20),
    serialiddetail character varying(150),
    iddetailrakitan character varying(150),
    idtrsretur character varying(150),
    statuskotag character varying(15) DEFAULT 'N'::character varying,
    origin_tipe character varying(20),
    origin_iddt character varying(150)
);


ALTER TABLE public.tbl_itemserialdt OWNER TO sysi5adm;

--
-- Name: tbl_itemserialmanage; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemserialmanage (
    iddetail character varying(150) NOT NULL,
    periode character varying(20),
    tanggal timestamp(6) without time zone,
    kodeitem character varying(100) NOT NULL,
    kodekantor character varying(50),
    satuan character varying(50),
    jmlsebelum numeric(20,3) DEFAULT 0,
    jmlfisik numeric(20,3) DEFAULT 0,
    jmlselisih numeric(20,3) DEFAULT 0,
    kodeacc character varying(30),
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    harga numeric(20,3) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    compname character varying(255),
    jmlserial numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_itemserialmanage OWNER TO sysi5adm;

--
-- Name: tbl_itemstok; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itemstok (
    kodeitem character varying(100),
    kantor character varying(50),
    stok numeric(20,3),
    hppdasar numeric(35,20) DEFAULT 0
);


ALTER TABLE public.tbl_itemstok OWNER TO sysi5adm;

--
-- Name: tbl_itktdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itktdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    jmlpesan numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    potongan numeric(35,20) DEFAULT 0,
    potongan2 numeric(35,20) DEFAULT 0,
    potongan3 numeric(35,20) DEFAULT 0,
    potongan4 numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    jmlrmasuk numeric(20,3) DEFAULT 0,
    jmlkeluar numeric(20,3) DEFAULT 0,
    jmlrkeluar numeric(20,3) DEFAULT 0,
    jmlsisa numeric(20,3) DEFAULT 0,
    jmlkonsibayar numeric(20,3) DEFAULT 0,
    idorder character varying(150),
    dateupd timestamp(6) without time zone,
    idtrsretur character varying(150),
    jmlretur numeric(20,3) DEFAULT 0,
    detinfo text,
    notrsretur character varying(100),
    potpiutang numeric(50,3),
    jmlkonversi numeric(50,3) DEFAULT 0,
    jmlterimajadi numeric(20,3) DEFAULT 0,
    jenis character varying(20),
    sistemhargajual character varying(1),
    hppdasar numeric(35,20) DEFAULT 0,
    tglexp timestamp(6) without time zone,
    kodeprod character varying(100),
    jenis_pajak character varying(10),
    taxppnbm numeric(20,3)
);


ALTER TABLE public.tbl_itktdt OWNER TO sysi5adm;

--
-- Name: tbl_itkthd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itkthd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantordari character varying(50),
    tanggal timestamp(6) without time zone,
    tipe character varying(20),
    notrsorder character varying(50),
    kodesupel character varying(50),
    kodesales character varying(50),
    kodesales2 character varying(50),
    kodesales3 character varying(50),
    kodesales4 character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    keterangan text,
    totalitemin numeric(20,3) DEFAULT 0,
    totalitemout numeric(20,3) DEFAULT 0,
    totalitempesan numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    subtoin numeric(20,3) DEFAULT 0,
    subtotout numeric(20,3) DEFAULT 0,
    potfaktur numeric(25,10) DEFAULT 0,
    pajakin numeric(20,3) DEFAULT 0,
    prpajakin numeric(10,3) DEFAULT 0,
    pajakout numeric(20,3) DEFAULT 0,
    prpajakout numeric(10,3) DEFAULT 0,
    biayalain numeric(20,3) DEFAULT 0,
    totalakhir numeric(20,3) DEFAULT 0,
    carabayar character varying(20),
    jmltunai numeric(20,3) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    jmldebit numeric(20,3) DEFAULT 0,
    jmlkk numeric(20,3) DEFAULT 0,
    dppesanan numeric(20,3) DEFAULT 0,
    komisi1 numeric(20,3) DEFAULT 0,
    komisi2 numeric(20,3) DEFAULT 0,
    komisi3 numeric(20,3) DEFAULT 0,
    komisi4 numeric(20,3) DEFAULT 0,
    nofp character varying(100),
    acc_potongan character varying(30),
    acc_pajak_in character varying(30),
    acc_pajak character varying(30),
    acc_biayalain character varying(30),
    acc_tunai character varying(30),
    acc_kredit character varying(30),
    acc_sales character varying(30),
    acc_hpp character varying(30),
    acc_debit character varying(30),
    acc_kk character varying(30),
    acc_deposit character varying(30),
    acc_dppesanan character varying(30),
    acc_biaya_pot character varying(30),
    byr_krd_jt timestamp(6) without time zone,
    byr_krd_no character varying(30),
    byr_debit_bank character varying(30),
    byr_kk_bank character varying(30),
    byr_debit_no character varying(100),
    byr_kk_no character varying(100),
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    tanggal_sa date,
    biaya_msk_total boolean,
    potnomfaktur numeric(20,3) DEFAULT 0,
    compname character varying(255),
    shiftkerja character varying(20),
    point_ik numeric(20,3) DEFAULT 0,
    point_sts integer DEFAULT 0,
    notrsretur character varying(100),
    point_notrans character varying(50),
    jmldeposit numeric(20,3) DEFAULT 0,
    ppn character varying(30),
    byr_komisi1 boolean,
    byr_komisi2 boolean,
    byr_komisi3 boolean,
    byr_komisi4 boolean,
    bc_trf_sts boolean DEFAULT false,
    status_online boolean DEFAULT false,
    compname_online character varying(255),
    user_online character varying(50),
    jmlemoney numeric(20,3) DEFAULT 0,
    byr_emoney_no character varying(100),
    byr_emoney_prod character varying(30),
    acc_emoney character varying(30),
    acc_sales_hut character varying(30) DEFAULT ''::character varying,
    opsikembalian character varying(5),
    jmlopkembali numeric(20,3),
    acc_donasi character varying(30),
    krd_jml_pot_ls numeric(20,3) DEFAULT 0,
    krd_jml_byr_ls numeric(20,3) DEFAULT 0,
    jenis_pajak_in character varying(10),
    jenis_pajak_out character varying(10),
    biaya_msk_total2 character varying(10),
    userizin character varying(50),
    prpajakppnbm numeric(10,3),
    pajakppnbm numeric(20,3),
    prpajakpph23 numeric(10,3),
    pajakpph23 numeric(20,3),
    acc_pajakppnbm character varying(30),
    acc_pajakpph23 character varying(30)
);


ALTER TABLE public.tbl_itkthd OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_itkthd.acc_potongan; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_potongan IS 'POTONGAN';


--
-- Name: COLUMN tbl_itkthd.acc_pajak_in; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_pajak_in IS 'PAJAK MASUKAN';


--
-- Name: COLUMN tbl_itkthd.acc_pajak; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_pajak IS 'PAJAK KELUARAN';


--
-- Name: COLUMN tbl_itkthd.acc_biayalain; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_biayalain IS 'BIAYA';


--
-- Name: COLUMN tbl_itkthd.acc_tunai; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_tunai IS 'BAYAR TUNAI';


--
-- Name: COLUMN tbl_itkthd.acc_kredit; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_kredit IS 'BAYAR KREDIT';


--
-- Name: COLUMN tbl_itkthd.acc_sales; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_sales IS 'SALES';


--
-- Name: COLUMN tbl_itkthd.acc_deposit; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itkthd.acc_deposit IS 'BAYAR DEPOSIT';


--
-- Name: tbl_itrdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itrdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    dateupd timestamp without time zone,
    detinfo text,
    jmlkonversi numeric(20,3) DEFAULT 0,
    hppdasar numeric(35,20) DEFAULT 0,
    tglexp timestamp(6) without time zone,
    kodeprod character varying(100)
);


ALTER TABLE public.tbl_itrdt OWNER TO sysi5adm;

--
-- Name: tbl_itrhd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_itrhd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantordari character varying(50),
    kantortujuan character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    keterangan text,
    acc_persediaan character varying(30),
    totalitem numeric(20,3) DEFAULT 0,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    shiftkerja character varying(20),
    mob_owner_id character varying(20),
    mob_trf_sts boolean,
    bc_trf_sts boolean DEFAULT false,
    status_online boolean DEFAULT false,
    compname_online character varying(255),
    user_online character varying(50),
    userizin character varying(50)
);


ALTER TABLE public.tbl_itrhd OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_itrhd.bc_trf_sts; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_itrhd.bc_trf_sts IS 'status transfer beda cabang. digunakan oleh web app';


--
-- Name: tbl_kantor; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_kantor (
    kodekantor character varying(50) NOT NULL,
    fungsi character varying(20),
    namakantor character varying(200),
    alamat text,
    notelepon character varying(150),
    fax character varying(150),
    cabang boolean DEFAULT false,
    kodeacc character varying(30),
    mobile boolean DEFAULT false,
    stspakai boolean DEFAULT false,
    nompajak numeric(20,3) DEFAULT 0,
    stsaktif character varying(15) DEFAULT 'Y'::character varying,
    whatsapp character varying(20),
    email character varying(100),
    jenis_pajak character varying(10)
);


ALTER TABLE public.tbl_kantor OWNER TO sysi5adm;

--
-- Name: tbl_kasktsetting; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_kasktsetting (
    kasktsetting character varying(50) NOT NULL,
    kodekategori character varying(20)
);


ALTER TABLE public.tbl_kasktsetting OWNER TO sysi5adm;

--
-- Name: tbl_kaslaci; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_kaslaci (
    nama_user character varying(50) NOT NULL,
    shift character varying(10),
    kas_awal numeric(20,3) DEFAULT 0,
    kas_masuk numeric(20,3) DEFAULT 0,
    kas_akhir numeric(20,3) DEFAULT 0,
    wkt_mulai timestamp(6) without time zone,
    wkt_akhir timestamp(6) without time zone,
    login_flag boolean DEFAULT false,
    kas_keluar numeric(20,3),
    nama_komputer character varying(20),
    notransaksi character varying(50) NOT NULL
);


ALTER TABLE public.tbl_kaslaci OWNER TO sysi5adm;

--
-- Name: tbl_kaslacidt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_kaslacidt (
    notransaksi character varying(50) NOT NULL,
    nama_pengambil character varying(50),
    kas_keluar numeric(20,3),
    keterangan_p character varying(100),
    iddetail character varying(200) NOT NULL
);


ALTER TABLE public.tbl_kaslacidt OWNER TO sysi5adm;

--
-- Name: tbl_kategori_kas; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_kategori_kas (
    kodekategori character varying(20) NOT NULL,
    namakategori character varying(150),
    grupaktivitas character varying(20),
    flagdel boolean DEFAULT false
);


ALTER TABLE public.tbl_kategori_kas OWNER TO sysi5adm;

--
-- Name: tbl_logaktivitas_akuntansi; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_logaktivitas_akuntansi (
    object character varying(100) NOT NULL,
    value character varying(100),
    description character varying(100) NOT NULL,
    iddetail character varying(150),
    notransaksi character varying(100),
    kodeacc character varying(50),
    cmd character varying(20) NOT NULL,
    user1 character varying(50) NOT NULL,
    shift character varying(20),
    compname character varying(200) NOT NULL,
    kodekantor character varying(20) NOT NULL,
    dateupd timestamp without time zone NOT NULL,
    id character varying(100) NOT NULL,
    nama_app character varying(20),
    versi_app character varying(100),
    userizin character varying(50)
);


ALTER TABLE public.tbl_logaktivitas_akuntansi OWNER TO sysi5adm;

--
-- Name: tbl_logaktivitas_impor; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_logaktivitas_impor (
    object character varying(100) NOT NULL,
    value character varying(100),
    description character varying(100) NOT NULL,
    user1 character varying(50) NOT NULL,
    shift character varying(20),
    compname character varying(200) NOT NULL,
    kodekantor character varying(20) NOT NULL,
    dateupd timestamp(6) without time zone NOT NULL,
    id character varying(100) NOT NULL,
    nama_app character varying(20),
    versi_app character varying(100),
    userizin character varying(50)
);


ALTER TABLE public.tbl_logaktivitas_impor OWNER TO sysi5adm;

--
-- Name: tbl_logaktivitas_master; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_logaktivitas_master (
    object character varying(100) NOT NULL,
    value character varying(100),
    description character varying(100) NOT NULL,
    cmd character varying(20) NOT NULL,
    user1 character varying(50) NOT NULL,
    shift character varying(20),
    compname character varying(200) NOT NULL,
    kodekantor character varying(20) NOT NULL,
    dateupd timestamp(6) without time zone NOT NULL,
    id character varying(100) NOT NULL,
    nama_app character varying(20),
    versi_app character varying(100),
    userizin character varying(50)
);


ALTER TABLE public.tbl_logaktivitas_master OWNER TO sysi5adm;

--
-- Name: tbl_logaktivitas_sistem; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_logaktivitas_sistem (
    object character varying(100) NOT NULL,
    value character varying(100),
    description character varying(100) NOT NULL,
    iddetail character varying(150),
    notransaksi character varying(100),
    cmd character varying(20) NOT NULL,
    user1 character varying(50) NOT NULL,
    shift character varying(20),
    kodekantor character varying(20) NOT NULL,
    nama_app character varying(20),
    versi_app character varying(100),
    dateupd timestamp without time zone NOT NULL,
    compname character varying(200) NOT NULL,
    id character varying(100) NOT NULL,
    userizin character varying(50)
);


ALTER TABLE public.tbl_logaktivitas_sistem OWNER TO sysi5adm;

--
-- Name: tbl_logaktivitas_transaksi; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_logaktivitas_transaksi (
    object character varying(100) NOT NULL,
    value character varying(100),
    description character varying(100) NOT NULL,
    iddetail character varying(150),
    notransaksi character varying(100),
    cmd character varying(20) NOT NULL,
    user1 character varying(50) NOT NULL,
    shift character varying(20),
    kodekantor character varying(20) NOT NULL,
    dateupd timestamp without time zone NOT NULL,
    compname character varying(200) NOT NULL,
    id character varying(100) NOT NULL,
    nama_app character varying(20),
    versi_app character varying(100),
    userizin character varying(50),
    datajson json
);


ALTER TABLE public.tbl_logaktivitas_transaksi OWNER TO sysi5adm;

--
-- Name: tbl_matauang; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_matauang (
    matauang character varying(50) NOT NULL,
    ketmatauang character varying(100),
    rate numeric(35,20) DEFAULT 0,
    utama boolean DEFAULT false,
    acc_hutang character varying(50),
    acc_piutang character varying(50),
    acc_byrtunai character varying(50),
    acc_byrbank character varying(50),
    tipe character varying(5)
);


ALTER TABLE public.tbl_matauang OWNER TO sysi5adm;

--
-- Name: tbl_mu_ratesa; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_mu_ratesa (
    matauang character varying(50),
    tanggal timestamp without time zone,
    rate numeric(35,20)
);


ALTER TABLE public.tbl_mu_ratesa OWNER TO sysi5adm;

--
-- Name: tbl_ongkir; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_ongkir (
    id character varying(150) NOT NULL,
    expedisi character varying(50),
    kota character varying(100),
    negara character varying(100),
    biaya1 numeric(20,3) DEFAULT 0,
    biaya2 numeric(20,3) DEFAULT 0,
    biaya3 numeric(20,3) DEFAULT 0,
    keterangan text,
    kotatujuan character varying(100),
    kodekantor character varying(50),
    provinsi_asal character varying(50),
    provinsi_tujuan character varying(50),
    id_provinsi_asal character varying(50),
    id_provinsi_tujuan character varying(50),
    id_kota_asal character varying(50),
    id_kota_tujuan character varying(50)
);


ALTER TABLE public.tbl_ongkir OWNER TO sysi5adm;

--
-- Name: tbl_pengiriman; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_pengiriman (
    notrs character varying(50) NOT NULL,
    idalamat_kirim character varying(150),
    idongkir_kurir character varying(150),
    berat numeric(20,0),
    paket numeric(20,0),
    statuskirim character varying(50),
    tanggalkirim timestamp(6) without time zone,
    noresi character varying(150),
    kurir character varying(150),
    jasa character varying(50),
    layanan character varying(50),
    total numeric(20,0),
    opsioffline boolean,
    namajasa character varying(50)
);


ALTER TABLE public.tbl_pengiriman OWNER TO sysi5adm;

--
-- Name: tbl_perkiraan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_perkiraan (
    kodeacc character varying(30) NOT NULL,
    parentacc character varying(30),
    kelompok character varying(2),
    tipe character varying(2),
    namaacc character varying(200),
    matauang character varying(50),
    dateupd timestamp without time zone,
    kasbank boolean DEFAULT false,
    defmuutm boolean DEFAULT false,
    aktivitas character varying(15) DEFAULT 'Operasional'::character varying
);


ALTER TABLE public.tbl_perkiraan OWNER TO sysi5adm;

--
-- Name: tbl_perksetting; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_perksetting (
    accsetting character varying(50) NOT NULL,
    kodeacc character varying(30),
    acckantor character varying(50) NOT NULL
);


ALTER TABLE public.tbl_perksetting OWNER TO sysi5adm;

--
-- Name: tbl_pesandt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_pesandt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    jmlterima numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(35,20) DEFAULT 0,
    potongan numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    dateupd timestamp without time zone,
    detinfo text,
    sistemhargajual character varying(1),
    jmlkonversi numeric(20,3) DEFAULT 0,
    tglexp timestamp(6) without time zone,
    kodeprod character varying(100),
    jenis_pajak character varying(10),
    taxppnbm numeric(20,3)
);


ALTER TABLE public.tbl_pesandt OWNER TO sysi5adm;

--
-- Name: tbl_pesanhd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_pesanhd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantortujuan character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    tanggalkirim timestamp without time zone,
    jenis character varying(20),
    kodesupel character varying(50),
    kodesales character varying(50),
    kodesales2 character varying(50),
    kodesales3 character varying(50),
    kodesales4 character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    keterangan text,
    komisi1 numeric(20,3) DEFAULT 0,
    komisi2 numeric(20,3) DEFAULT 0,
    komisi3 numeric(20,3) DEFAULT 0,
    komisi4 numeric(20,3),
    totalitem numeric(20,3) DEFAULT 0,
    totalterima numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    potfaktur numeric(25,10) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    biayalain numeric(20,3) DEFAULT 0,
    totalakhir numeric(20,3) DEFAULT 0,
    biaya_msk_total boolean,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp without time zone,
    potnomfaktur numeric(20,3) DEFAULT 0,
    prpajak numeric(10,3) DEFAULT 0,
    dppesanan numeric(20,3) DEFAULT 0,
    dppesananbyr numeric(20,3) DEFAULT 0,
    acc_dppesanan character varying(30),
    acc_dpkas character varying(30),
    ppn character varying(30),
    bc_trf_sts boolean DEFAULT false,
    prpotfaktur numeric(25,10),
    acc_biaya_pot character varying(30),
    opsikirim character varying(5),
    status_online boolean DEFAULT false,
    compname_online character varying(255),
    user_online character varying(50),
    jenis_pajak character varying(10),
    biaya_msk_total2 character varying(10),
    userizin character varying(50),
    prpajakppnbm numeric(10,3),
    pajakppnbm numeric(20,3),
    prpajakpph23 numeric(10,3),
    pajakpph23 numeric(20,3)
);


ALTER TABLE public.tbl_pesanhd OWNER TO sysi5adm;

--
-- Name: tbl_pesanrakitan; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_pesanrakitan (
    iddetail character varying(150) NOT NULL,
    iddetailtrs character varying(150),
    notransaksi character varying(50),
    tipe character varying(20),
    kodeitem character varying(100),
    kodeitemrakitan character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    jumlahtrs numeric(20,3) DEFAULT 0,
    satuantrs character varying(50),
    dateupd timestamp without time zone,
    jenisrakit character varying(20),
    jmlkonversi numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_pesanrakitan OWNER TO sysi5adm;

--
-- Name: tbl_point_sa; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_point_sa (
    kodesupel character varying(50) NOT NULL,
    kodekantor character varying(50),
    notransaksi character varying(50),
    tanggal timestamp without time zone,
    tipe character varying(20),
    point_ik numeric(20,3) DEFAULT 0,
    tgl_trs timestamp(6) without time zone
);


ALTER TABLE public.tbl_point_sa OWNER TO sysi5adm;

--
-- Name: tbl_pointambil; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_pointambil (
    notransaksi character varying(50) NOT NULL,
    tipe character varying(50),
    tanggal timestamp(6) without time zone,
    periodetgl1 timestamp(6) without time zone,
    periodetgl2 timestamp(6) without time zone,
    jmlambil numeric(20,0) DEFAULT 0,
    kodesupel character varying(50),
    keterangan text,
    kodekantor character varying(50)
);


ALTER TABLE public.tbl_pointambil OWNER TO sysi5adm;

--
-- Name: tbl_rb_hutang; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_rb_hutang (
    noretur character varying(50),
    notrspot character varying(50),
    jmlpot numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_rb_hutang OWNER TO sysi5adm;

--
-- Name: tbl_ref_retur; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_ref_retur (
    iddetail character varying(150) NOT NULL,
    notransaksi character varying(50),
    iddetailim character varying(150),
    kodeitem character varying(150),
    rate numeric(35,20) DEFAULT 0,
    jumlah numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_ref_retur OWNER TO sysi5adm;

--
-- Name: tbl_request_upload_queue; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_request_upload_queue (
    idupload character varying(100) NOT NULL,
    entry_date timestamp without time zone,
    modul_type character varying(50),
    modul_key_value character varying(255),
    modul_key_oldvalue character varying(255),
    save_type character varying(50),
    flag_upload integer,
    ret_id character varying(20),
    ret_msg text
);


ALTER TABLE public.tbl_request_upload_queue OWNER TO sysi5adm;

--
-- Name: tbl_rj_piutang; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_rj_piutang (
    noretur character varying(50),
    notrspot character varying(50),
    jmlpot numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_rj_piutang OWNER TO sysi5adm;

--
-- Name: tbl_sandi; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_sandi (
    angka character varying(2),
    huruf character varying(10)
);


ALTER TABLE public.tbl_sandi OWNER TO sysi5adm;

--
-- Name: tbl_settingpel; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_settingpel (
    ptipe integer DEFAULT 1,
    pkelipatan numeric(20,3) DEFAULT 0,
    pnilaitukar numeric(20,3) DEFAULT 0,
    pmasadari timestamp(0) without time zone,
    pmasasampai timestamp(0) without time zone,
    pmtukardari timestamp(0) without time zone,
    pmtukarsampai timestamp(0) without time zone,
    ppotberlaku integer DEFAULT 0,
    mnote1 character varying(255),
    mnote2 character varying(255),
    pumumnopoin boolean DEFAULT false,
    pmdapatdari timestamp without time zone,
    pmdapatsampai timestamp without time zone,
    mnote3 character varying(255),
    ppointopot boolean DEFAULT false,
    mnote4 character varying(255)
);


ALTER TABLE public.tbl_settingpel OWNER TO sysi5adm;

--
-- Name: tbl_supel; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_supel (
    kode character varying(50) NOT NULL,
    tipe character varying(2) NOT NULL,
    nama character varying(150),
    alamat text,
    kota character varying(100),
    provinsi character varying(100),
    kodepos character varying(20),
    negara character varying(100),
    telepon character varying(200),
    fax character varying(200),
    kontak character varying(200),
    email character varying(200),
    matauang character varying(50),
    norek character varying(100),
    atasnama character varying(100),
    bank character varying(100),
    keterangan text,
    limitjmlhupi numeric(20,3) DEFAULT 0,
    limitharihupi integer DEFAULT 0,
    tipepot character varying(5),
    kgrup character varying(20),
    pilkomisi integer DEFAULT 1,
    piljmlkomisi integer DEFAULT 1,
    komisipr numeric(20,3) DEFAULT 0,
    komisinom numeric(20,3) DEFAULT 0,
    npwp character varying(100),
    harijt integer DEFAULT 0,
    kdwilayah character varying(50),
    kdsubwil character varying(50),
    kdsales character varying(50),
    maxjmlkredit numeric(20,3) DEFAULT 0,
    syspajak character varying(10),
    opsyspajak character varying(10),
    nompajak numeric(20,3),
    nik character varying(50),
    nama_npwp character varying(150),
    alamat_npwp text,
    tgl_lahir timestamp without time zone,
    opsikredit character varying(5) DEFAULT 'Y'::character varying,
    acc_kredit character varying(30),
    stsaktif character varying(15) DEFAULT 'Y'::character varying,
    cekbglunas boolean DEFAULT false,
    jenis_pajak character varying(10),
    opsi_doc character varying(10),
    paspor character varying(100),
    other_id character varying(100),
    email_pajak character varying(100),
    idtku character varying(100),
    buyercountry character varying(10)
);


ALTER TABLE public.tbl_supel OWNER TO sysi5adm;

--
-- Name: tbl_supel_subwil; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_supel_subwil (
    kode character varying(50) NOT NULL,
    subwilayah character varying(250)
);


ALTER TABLE public.tbl_supel_subwil OWNER TO sysi5adm;

--
-- Name: tbl_supel_wil; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_supel_wil (
    kode character varying(50) NOT NULL,
    wilayah character varying(250)
);


ALTER TABLE public.tbl_supel_wil OWNER TO sysi5adm;

--
-- Name: tbl_supelgrup; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_supelgrup (
    kgrup character varying(20) NOT NULL,
    grup character varying(100),
    potongan numeric(20,3) DEFAULT 0,
    levelharga integer,
    kelipatanpoin numeric(20,3) DEFAULT 0
);


ALTER TABLE public.tbl_supelgrup OWNER TO sysi5adm;

--
-- Name: tbl_tagihandt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_tagihandt (
    iddetail character varying(100),
    notransaksi character varying(200),
    jumlah numeric(20,3),
    idko_dt character varying(20)
);


ALTER TABLE public.tbl_tagihandt OWNER TO sysi5adm;

--
-- Name: tbl_tagihikdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_tagihikdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jumlah numeric(20,3) DEFAULT 0,
    jmlpesan numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    potongan numeric(35,20) DEFAULT 0,
    potongan2 numeric(35,20) DEFAULT 0,
    potongan3 numeric(35,20) DEFAULT 0,
    potongan4 numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    jmlrmasuk numeric(20,3) DEFAULT 0,
    jmlkeluar numeric(20,3) DEFAULT 0,
    jmlrkeluar numeric(20,3) DEFAULT 0,
    jmlsisa numeric(20,3) DEFAULT 0,
    jmlkonsibayar numeric(20,3) DEFAULT 0,
    idorder character varying(150),
    dateupd timestamp(6) without time zone,
    idtrskonsinyasi character varying(150),
    jmlretur numeric(20,3) DEFAULT 0,
    detinfo text,
    notrskonsinyasi character varying(100),
    jmlkonversi numeric(50,3),
    iddetailtrs character varying(150),
    xx integer,
    tglexp timestamp(6) without time zone,
    kodeprod character varying(100),
    jenis_pajak character varying(10),
    taxppnbm numeric(20,3)
);


ALTER TABLE public.tbl_tagihikdt OWNER TO sysi5adm;

--
-- Name: tbl_tagihikhd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_tagihikhd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantordari character varying(50),
    tanggal timestamp(6) without time zone,
    tipe character varying(20),
    notrsorder character varying(50),
    kodesupel character varying(50),
    kodesales character varying(50),
    kodesales2 character varying(50),
    kodesales3 character varying(50),
    kodesales4 character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    keterangan text,
    totalitem numeric(20,3) DEFAULT 0,
    totalitempesan numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    potfaktur numeric(25,10) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    biayalain numeric(20,3) DEFAULT 0,
    potnomfaktur numeric(20,3) DEFAULT 0,
    dppesanan numeric(20,3) DEFAULT 0,
    prpajak numeric(10,3) DEFAULT 0,
    totalakhir numeric(20,3) DEFAULT 0,
    carabayar character varying(20),
    jmltunai numeric(20,3) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    jmldebit numeric(20,3) DEFAULT 0,
    jmlkk numeric(20,3) DEFAULT 0,
    komisi1 numeric(20,3) DEFAULT 0,
    komisi2 numeric(20,3) DEFAULT 0,
    komisi3 numeric(20,3) DEFAULT 0,
    komisi4 numeric(20,3) DEFAULT 0,
    point_ik numeric(20,3) DEFAULT 0,
    point_sts integer DEFAULT 0,
    nofp character varying(100),
    ppn character varying(30),
    notrsretur character varying(100),
    acc_potongan character varying(30),
    acc_pajak character varying(30),
    acc_biayalain character varying(30),
    acc_tunai character varying(30),
    acc_kredit character varying(30),
    acc_sales character varying(30),
    acc_hpp character varying(30),
    acc_debit character varying(30),
    acc_kk character varying(30),
    acc_dppesanan character varying(30),
    acc_biaya_pot character varying(30),
    byr_krd_jt timestamp(6) without time zone,
    byr_krd_no character varying(30),
    byr_debit_bank character varying(30),
    byr_kk_bank character varying(30),
    byr_debit_no character varying(100),
    byr_kk_no character varying(100),
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    tanggal_sa date,
    biaya_msk_total boolean,
    compname character varying(255),
    shiftkerja character varying(20),
    byr_komisi1 boolean,
    byr_komisi2 boolean,
    byr_komisi3 boolean,
    byr_komisi4 boolean,
    point_notrans character varying(50),
    notransaksi_ko character varying(50),
    bc_trf_sts boolean DEFAULT false,
    mode_tagih character varying(5),
    jenis_pajak character varying(10),
    biaya_msk_total2 character varying(10),
    userizin character varying(50),
    prpajakppnbm numeric(10,3),
    pajakppnbm numeric(20,3),
    acc_pajakppnbm character varying(30)
);


ALTER TABLE public.tbl_tagihikhd OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_tagihikhd.acc_potongan; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihikhd.acc_potongan IS 'POTONGAN';


--
-- Name: COLUMN tbl_tagihikhd.acc_pajak; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihikhd.acc_pajak IS 'PAJAK';


--
-- Name: COLUMN tbl_tagihikhd.acc_biayalain; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihikhd.acc_biayalain IS 'BIAYA';


--
-- Name: COLUMN tbl_tagihikhd.acc_tunai; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihikhd.acc_tunai IS 'BAYAR TUNAI';


--
-- Name: COLUMN tbl_tagihikhd.acc_kredit; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihikhd.acc_kredit IS 'BAYAR KREDIT';


--
-- Name: COLUMN tbl_tagihikhd.acc_sales; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihikhd.acc_sales IS 'SALES';


--
-- Name: tbl_tagihimdt; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_tagihimdt (
    iddetail character varying(150) NOT NULL,
    nobaris integer DEFAULT 0,
    notransaksi character varying(50),
    kodeitem character varying(100),
    jmlkonsi numeric(20,3) DEFAULT 0,
    jmlkonsiretur numeric(20,3) DEFAULT 0,
    satuan character varying(50),
    harga numeric(20,3) DEFAULT 0,
    potongan numeric(35,20) DEFAULT 0,
    total numeric(20,3) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    jmllaku numeric(20,3) DEFAULT 0,
    jmlreturjual numeric(20,3) DEFAULT 0,
    jmlsisa numeric(20,3) DEFAULT 0,
    jmlkonsibayar numeric(20,3) DEFAULT 0,
    tglexp timestamp(6) without time zone,
    idtrsretur character varying(150),
    kodeprod character varying(100),
    idorder character varying(150),
    dateupd timestamp(6) without time zone,
    sakantor character varying(50),
    detinfo text,
    pothutang numeric(20,3),
    notrsretur character varying(100),
    jmlkonversi numeric(20,3),
    nom_pajak numeric(20,3),
    jmlkeluar numeric(20,3),
    jenis_pajak character varying(10)
);


ALTER TABLE public.tbl_tagihimdt OWNER TO sysi5adm;

--
-- Name: tbl_tagihimhd; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_tagihimhd (
    notransaksi character varying(50) NOT NULL,
    kodekantor character varying(50),
    kantortujuan character varying(50),
    tanggal timestamp(6) without time zone,
    tipe character varying(20),
    notrsorder character varying(50),
    kodesupel character varying(50),
    matauang character varying(50),
    rate numeric(35,20) DEFAULT 0,
    keterangan text,
    totalitem numeric(20,3) DEFAULT 0,
    totalitempesan numeric(20,3) DEFAULT 0,
    subtotal numeric(20,3) DEFAULT 0,
    potfaktur numeric(25,10) DEFAULT 0,
    pajak numeric(20,3) DEFAULT 0,
    biayalain numeric(20,3) DEFAULT 0,
    totalakhir numeric(20,3) DEFAULT 0,
    carabayar character varying(20),
    jmltunai numeric(20,3) DEFAULT 0,
    jmlkredit numeric(20,3) DEFAULT 0,
    acc_potongan character varying(30),
    acc_pajak character varying(30),
    acc_biayalain character varying(30),
    acc_tunai character varying(30),
    acc_kredit character varying(30),
    acc_hpp character varying(30),
    acc_tagihan character varying(30),
    acc_dppesanan character varying(30),
    acc_biaya_pot character varying(30),
    byr_krd_jt timestamp(6) without time zone,
    byr_krd_no character varying(30),
    krd_jml_pot numeric(20,3) DEFAULT 0,
    krd_jml_byr numeric(20,3) DEFAULT 0,
    user1 character varying(50),
    user2 character varying(50),
    dateupd timestamp(6) without time zone,
    tanggal_sa date,
    biaya_msk_total boolean,
    potnomfaktur numeric(20,3) DEFAULT 0,
    compname character varying(255),
    shiftkerja character varying(20),
    prpajak numeric(10,3) DEFAULT 0,
    dppesanan numeric(20,3) DEFAULT 0,
    notrsretur character varying(100),
    ppn character varying(30),
    totallaku numeric(20,3),
    totalretur numeric(20,3),
    totalkonsinyasi numeric(20,3),
    bc_trf_sts boolean DEFAULT false,
    jenis_pajak character varying(10),
    biaya_msk_total2 character varying(10),
    userizin character varying(50)
);


ALTER TABLE public.tbl_tagihimhd OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_tagihimhd.acc_potongan; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihimhd.acc_potongan IS 'POTONGAN';


--
-- Name: COLUMN tbl_tagihimhd.acc_pajak; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihimhd.acc_pajak IS 'PAJAK';


--
-- Name: COLUMN tbl_tagihimhd.acc_biayalain; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_tagihimhd.acc_biayalain IS 'BIAYA';


--
-- Name: tbl_tmp; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_tmp (
    cntprsjurnal bigint,
    cntlevelrep integer DEFAULT 0,
    cntsortrep integer DEFAULT 0,
    cnt_im bigint DEFAULT 0
);


ALTER TABLE public.tbl_tmp OWNER TO sysi5adm;

--
-- Name: tbl_user; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_user (
    userid character varying(20) NOT NULL,
    nama character varying(150),
    password character varying(255),
    tipe character varying(5),
    loginkantor character varying(50),
    kelompok character varying(35),
    loginshift boolean,
    synchronized boolean,
    kodesales character varying(50),
    stslogin boolean DEFAULT false
);


ALTER TABLE public.tbl_user OWNER TO sysi5adm;

--
-- Name: COLUMN tbl_user.synchronized; Type: COMMENT; Schema: public; Owner: sysi5adm
--

COMMENT ON COLUMN tbl_user.synchronized IS 'apakah mobile user sudah dipakai atau belum';


--
-- Name: tbl_userakses; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_userakses (
    klpakses character varying(35),
    modulid character varying(50),
    mopen boolean DEFAULT false,
    mnew boolean DEFAULT false,
    medit boolean DEFAULT false,
    mdel boolean DEFAULT false,
    mlock boolean DEFAULT false,
    urut integer DEFAULT 0,
    kelompok integer,
    mlocktgl boolean DEFAULT false
);


ALTER TABLE public.tbl_userakses OWNER TO sysi5adm;

--
-- Name: tbl_usercus_acc; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_usercus_acc (
    klpakses character varying(35),
    modulid character varying(50),
    customacc character varying(50),
    customval character varying(50)
);


ALTER TABLE public.tbl_usercus_acc OWNER TO sysi5adm;

--
-- Name: tbl_userg; Type: TABLE; Schema: public; Owner: sysi5adm
--

CREATE TABLE tbl_userg (
    kelompok character varying(30) NOT NULL,
    urut integer
);


ALTER TABLE public.tbl_userg OWNER TO sysi5adm;

--
-- Name: iddetail; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckasdt
    ADD CONSTRAINT iddetail UNIQUE (iddetail);


--
-- Name: kodebarcode; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemsatuanjml
    ADD CONSTRAINT kodebarcode UNIQUE (kodebarcode);


--
-- Name: kodeitem; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT kodeitem UNIQUE (kodeitem);


--
-- Name: tbl_accdepositdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdepositdt
    ADD CONSTRAINT tbl_accdepositdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_accdeposithd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdeposithd
    ADD CONSTRAINT tbl_accdeposithd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_accjurnal_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accjurnal
    ADD CONSTRAINT tbl_accjurnal_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_acckasdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckasdt
    ADD CONSTRAINT tbl_acckasdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_acckashd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckashd
    ADD CONSTRAINT tbl_acckashd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_alamatkirim_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_alamatkirim
    ADD CONSTRAINT tbl_alamatkirim_pkey PRIMARY KEY (id);


--
-- Name: tbl_bank_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_bank
    ADD CONSTRAINT tbl_bank_pkey PRIMARY KEY (kodebank);


--
-- Name: tbl_byrhutangdt_copy_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsidt
    ADD CONSTRAINT tbl_byrhutangdt_copy_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_byrhutangdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangdt
    ADD CONSTRAINT tbl_byrhutangdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_byrhutanghd_copy_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsihd
    ADD CONSTRAINT tbl_byrhutanghd_copy_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_byrhutanghd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutanghd
    ADD CONSTRAINT tbl_byrhutanghd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_byrkomisislsdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislsdt
    ADD CONSTRAINT tbl_byrkomisislsdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_byrkomisislshd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislshd
    ADD CONSTRAINT tbl_byrkomisislshd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_byrpiutangdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangdt
    ADD CONSTRAINT tbl_byrpiutangdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_byrpiutanghd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutanghd
    ADD CONSTRAINT tbl_byrpiutanghd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_byrpiutangkonsidt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsidt
    ADD CONSTRAINT tbl_byrpiutangkonsidt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_byrpiutangkonsihd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsihd
    ADD CONSTRAINT tbl_byrpiutangkonsihd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_conf_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_conf
    ADD CONSTRAINT tbl_conf_pkey PRIMARY KEY (confname);


--
-- Name: tbl_emoney_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_emoney
    ADD CONSTRAINT tbl_emoney_pkey PRIMARY KEY (kodeprod);


--
-- Name: tbl_formatnosp_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_formatnosp
    ADD CONSTRAINT tbl_formatnosp_pkey PRIMARY KEY (trid);


--
-- Name: tbl_formatnotr_pk; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_formatnotr
    ADD CONSTRAINT tbl_formatnotr_pk PRIMARY KEY (trid, kantor);


--
-- Name: tbl_ikdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikdt
    ADD CONSTRAINT tbl_ikdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_ikhd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_ikrakitan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikrakitan
    ADD CONSTRAINT tbl_ikrakitan_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_imdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imdt
    ADD CONSTRAINT tbl_imdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_imhd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_imrakitan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imrakitan
    ADD CONSTRAINT tbl_imrakitan_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_item_ik_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ik
    ADD CONSTRAINT tbl_item_ik_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_item_ikko_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ikko
    ADD CONSTRAINT tbl_item_ikko_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_item_ikret_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ikret
    ADD CONSTRAINT tbl_item_ikret_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_item_im_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_im
    ADD CONSTRAINT tbl_item_im_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_item_imretur_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_imret
    ADD CONSTRAINT tbl_item_imretur_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_item_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_pkey PRIMARY KEY (kodeitem);


--
-- Name: tbl_item_sa_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_sa
    ADD CONSTRAINT tbl_item_sa_pkey PRIMARY KEY (iddetailtrs);


--
-- Name: tbl_itemdisp_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemdisp
    ADD CONSTRAINT tbl_itemdisp_pkey PRIMARY KEY (iddiskon);


--
-- Name: tbl_itemhj_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemhj
    ADD CONSTRAINT tbl_itemhj_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itemjenis_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemjenis
    ADD CONSTRAINT tbl_itemjenis_pkey PRIMARY KEY (jenis);


--
-- Name: tbl_itemketerangan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemketerangan
    ADD CONSTRAINT tbl_itemketerangan_pkey PRIMARY KEY (kodeket, jenisket);


--
-- Name: tbl_itemmerek_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemmerek
    ADD CONSTRAINT tbl_itemmerek_pkey PRIMARY KEY (merek);


--
-- Name: tbl_itemopname_iddetail_key; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemopname
    ADD CONSTRAINT tbl_itemopname_iddetail_key UNIQUE (iddetail);


--
-- Name: tbl_itemopname_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemopname
    ADD CONSTRAINT tbl_itemopname_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itempotongan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempotongan
    ADD CONSTRAINT tbl_itempotongan_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itempromo_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromo
    ADD CONSTRAINT tbl_itempromo_pkey PRIMARY KEY (idpromo);


--
-- Name: tbl_itemrakitan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemrakitan
    ADD CONSTRAINT tbl_itemrakitan_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itemsatuan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemsatuan
    ADD CONSTRAINT tbl_itemsatuan_pkey PRIMARY KEY (satuan);


--
-- Name: tbl_itemsatuanjml_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemsatuanjml
    ADD CONSTRAINT tbl_itemsatuanjml_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itemserial_kotag_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserial_kotag
    ADD CONSTRAINT tbl_itemserial_kotag_pkey PRIMARY KEY (noserial);


--
-- Name: tbl_itemserial_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserial
    ADD CONSTRAINT tbl_itemserial_pkey PRIMARY KEY (noserial);


--
-- Name: tbl_itemserialmanage_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserialmanage
    ADD CONSTRAINT tbl_itemserialmanage_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itktdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itktdt
    ADD CONSTRAINT tbl_itktdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itkthd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_itrdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrdt
    ADD CONSTRAINT tbl_itrdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_itrhd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrhd
    ADD CONSTRAINT tbl_itrhd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_kantor_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kantor
    ADD CONSTRAINT tbl_kantor_pkey PRIMARY KEY (kodekantor);


--
-- Name: tbl_kas_laci_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kaslaci
    ADD CONSTRAINT tbl_kas_laci_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_kasktsetting_pk; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kasktsetting
    ADD CONSTRAINT tbl_kasktsetting_pk PRIMARY KEY (kasktsetting);


--
-- Name: tbl_kategori_kas_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kategori_kas
    ADD CONSTRAINT tbl_kategori_kas_pkey PRIMARY KEY (kodekategori);


--
-- Name: tbl_logaktivitas_akuntansi_copy_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_logaktivitas_impor
    ADD CONSTRAINT tbl_logaktivitas_akuntansi_copy_pkey PRIMARY KEY (id);


--
-- Name: tbl_logaktivitas_akuntansi_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_logaktivitas_akuntansi
    ADD CONSTRAINT tbl_logaktivitas_akuntansi_pkey PRIMARY KEY (id);


--
-- Name: tbl_logaktivitas_master_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_logaktivitas_master
    ADD CONSTRAINT tbl_logaktivitas_master_pkey PRIMARY KEY (id);


--
-- Name: tbl_logaktivitas_sistem_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_logaktivitas_sistem
    ADD CONSTRAINT tbl_logaktivitas_sistem_pkey PRIMARY KEY (id);


--
-- Name: tbl_logaktivitas_transaksi_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_logaktivitas_transaksi
    ADD CONSTRAINT tbl_logaktivitas_transaksi_pkey PRIMARY KEY (id);


--
-- Name: tbl_matauang_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_matauang
    ADD CONSTRAINT tbl_matauang_pkey PRIMARY KEY (matauang);


--
-- Name: tbl_ongkir_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ongkir
    ADD CONSTRAINT tbl_ongkir_pkey PRIMARY KEY (id);


--
-- Name: tbl_pengiriman_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pengiriman
    ADD CONSTRAINT tbl_pengiriman_pkey PRIMARY KEY (notrs);


--
-- Name: tbl_perkiraan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_perkiraan
    ADD CONSTRAINT tbl_perkiraan_pkey PRIMARY KEY (kodeacc);


--
-- Name: tbl_perksetting_pk; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_perksetting
    ADD CONSTRAINT tbl_perksetting_pk PRIMARY KEY (acckantor, accsetting);


--
-- Name: tbl_pesandt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesandt
    ADD CONSTRAINT tbl_pesandt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_pesanhd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_pesanrakitan_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanrakitan
    ADD CONSTRAINT tbl_pesanrakitan_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_pointambil_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pointambil
    ADD CONSTRAINT tbl_pointambil_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_request_upload_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_request_upload_queue
    ADD CONSTRAINT tbl_request_upload_queue_pkey PRIMARY KEY (idupload);


--
-- Name: tbl_supel_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supel
    ADD CONSTRAINT tbl_supel_pkey PRIMARY KEY (kode, tipe);


--
-- Name: tbl_supel_subwil_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supel_subwil
    ADD CONSTRAINT tbl_supel_subwil_pkey PRIMARY KEY (kode);


--
-- Name: tbl_supel_wil_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supel_wil
    ADD CONSTRAINT tbl_supel_wil_pkey PRIMARY KEY (kode);


--
-- Name: tbl_supelgrup_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supelgrup
    ADD CONSTRAINT tbl_supelgrup_pkey PRIMARY KEY (kgrup);


--
-- Name: tbl_tagihikdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikdt
    ADD CONSTRAINT tbl_tagihikdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_tagihikhd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_tagihimdt_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimdt
    ADD CONSTRAINT tbl_tagihimdt_pkey PRIMARY KEY (iddetail);


--
-- Name: tbl_tagihimhd_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_pkey PRIMARY KEY (notransaksi);


--
-- Name: tbl_user_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_user
    ADD CONSTRAINT tbl_user_pkey PRIMARY KEY (userid);


--
-- Name: tbl_userg_pkey; Type: CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_userg
    ADD CONSTRAINT tbl_userg_pkey PRIMARY KEY (kelompok);


--
-- Name: acc_hpp; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX acc_hpp ON tbl_itrhd USING btree (acc_persediaan);


--
-- Name: acc_hutang; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX acc_hutang ON tbl_matauang USING btree (acc_hutang);


--
-- Name: acc_piutang; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX acc_piutang ON tbl_matauang USING btree (acc_piutang);


--
-- Name: iddetail1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX iddetail1 ON tbl_imdt USING btree (iddetail);


--
-- Name: iddetail1_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX iddetail1_tki ON tbl_tagihimdt USING btree (iddetail);


--
-- Name: jenis; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX jenis ON tbl_item USING btree (jenis);


--
-- Name: kantordari; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantordari ON tbl_ikhd USING btree (kantordari);


--
-- Name: kantordari1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantordari1 ON tbl_itrhd USING btree (kantordari);


--
-- Name: kantordari_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantordari_tko ON tbl_tagihikhd USING btree (kantordari);


--
-- Name: kantordaritkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantordaritkt ON tbl_itkthd USING btree (kantordari);


--
-- Name: kantortujuan; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantortujuan ON tbl_imhd USING btree (kantortujuan);


--
-- Name: kantortujuan1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantortujuan1 ON tbl_itrhd USING btree (kantortujuan);


--
-- Name: kantortujuan2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantortujuan2 ON tbl_pesanhd USING btree (kantortujuan);


--
-- Name: kantortujuan_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kantortujuan_tki ON tbl_tagihimhd USING btree (kantortujuan);


--
-- Name: kode; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kode ON tbl_supel USING btree (kode);


--
-- Name: kodeacc; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeacc ON tbl_acckashd USING btree (kodeacc);


--
-- Name: kodeacc1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeacc1 ON tbl_itemopname USING btree (kodeacc);


--
-- Name: kodeacc_depo; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeacc_depo ON tbl_accdeposithd USING btree (kodeacc);


--
-- Name: kodeaccto; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeaccto ON tbl_acckashd USING btree (kodeaccto);


--
-- Name: kodeaccto_depo; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeaccto_depo ON tbl_accdeposithd USING btree (kodeaccto);


--
-- Name: kodeitem1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem1 ON tbl_ikdt USING btree (kodeitem);


--
-- Name: kodeitem10; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem10 ON tbl_itemsatuanjml USING btree (kodeitem);


--
-- Name: kodeitem12; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem12 ON tbl_itrdt USING btree (kodeitem);


--
-- Name: kodeitem13; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem13 ON tbl_pesandt USING btree (kodeitem);


--
-- Name: kodeitem1_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem1_tko ON tbl_tagihikdt USING btree (kodeitem);


--
-- Name: kodeitem1tkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem1tkt ON tbl_itktdt USING btree (kodeitem);


--
-- Name: kodeitem2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem2 ON tbl_ikrakitan USING btree (kodeitem);


--
-- Name: kodeitem3; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem3 ON tbl_imdt USING btree (kodeitem);


--
-- Name: kodeitem3_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem3_tki ON tbl_tagihimdt USING btree (kodeitem);


--
-- Name: kodeitem4; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem4 ON tbl_imrakitan USING btree (kodeitem);


--
-- Name: kodeitem5; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem5 ON tbl_item_rekap USING btree (kodeitem);


--
-- Name: kodeitem6; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem6 ON tbl_item_sa USING btree (kodeitem);


--
-- Name: kodeitem7; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem7 ON tbl_itemhj USING btree (kodeitem);


--
-- Name: kodeitem8; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem8 ON tbl_itemopname USING btree (kodeitem);


--
-- Name: kodeitem8sm; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem8sm ON tbl_itemserialmanage USING btree (kodeitem);


--
-- Name: kodeitem9; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem9 ON tbl_itemrakitan USING btree (kodeitem, kodeitemrakitan);


--
-- Name: kodeitem_2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitem_2 ON tbl_itemrakitan USING btree (kodeitem);


--
-- Name: kodeitemrakitan; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitemrakitan ON tbl_ikrakitan USING btree (kodeitemrakitan);


--
-- Name: kodeitemrakitan1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitemrakitan1 ON tbl_imrakitan USING btree (kodeitemrakitan);


--
-- Name: kodeitemrakitan2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodeitemrakitan2 ON tbl_itemrakitan USING btree (kodeitemrakitan);


--
-- Name: kodekantor; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor ON tbl_acckashd USING btree (kodekantor);


--
-- Name: kodekantor1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor1 ON tbl_byrhutanghd USING btree (kodekantor);


--
-- Name: kodekantor10; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor10 ON tbl_kantor USING btree (kodekantor);


--
-- Name: kodekantor11; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor11 ON tbl_pesanhd USING btree (kodekantor);


--
-- Name: kodekantor1_kinhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor1_kinhd ON tbl_byrhutangkonsihd USING btree (kodekantor);


--
-- Name: kodekantor2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor2 ON tbl_byrpiutanghd USING btree (kodekantor);


--
-- Name: kodekantor2_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor2_tko ON tbl_byrpiutangkonsihd USING btree (kodekantor);


--
-- Name: kodekantor3; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor3 ON tbl_ikhd USING btree (kodekantor);


--
-- Name: kodekantor3_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor3_tko ON tbl_tagihikhd USING btree (kodekantor);


--
-- Name: kodekantor3tkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor3tkt ON tbl_itkthd USING btree (kodekantor);


--
-- Name: kodekantor4; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor4 ON tbl_imhd USING btree (kodekantor);


--
-- Name: kodekantor4_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor4_tki ON tbl_tagihimhd USING btree (kodekantor);


--
-- Name: kodekantor5; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor5 ON tbl_item_rekap USING btree (kodekantor);


--
-- Name: kodekantor6; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor6 ON tbl_item_sa USING btree (kodekantor);


--
-- Name: kodekantor7; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor7 ON tbl_itemopname USING btree (kodekantor);


--
-- Name: kodekantor7sm; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor7sm ON tbl_itemserialmanage USING btree (kodekantor);


--
-- Name: kodekantor9; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor9 ON tbl_itrhd USING btree (kodekantor);


--
-- Name: kodekantor_depo; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodekantor_depo ON tbl_accdeposithd USING btree (kodekantor);


--
-- Name: kodesales; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesales ON tbl_ikhd USING btree (kodesales);


--
-- Name: kodesales2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesales2 ON tbl_ikhd USING btree (kodesales2);


--
-- Name: kodesales2tkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesales2tkt ON tbl_itkthd USING btree (kodesales2);


--
-- Name: kodesales3; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesales3 ON tbl_ikhd USING btree (kodesales3);


--
-- Name: kodesales3tkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesales3tkt ON tbl_itkthd USING btree (kodesales3);


--
-- Name: kodesalestkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesalestkt ON tbl_itkthd USING btree (kodesales);


--
-- Name: kodesupel1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupel1 ON tbl_ikhd USING btree (kodesupel);


--
-- Name: kodesupel1_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupel1_tko ON tbl_tagihikhd USING btree (kodesupel);


--
-- Name: kodesupel1tkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupel1tkt ON tbl_itkthd USING btree (kodesupel);


--
-- Name: kodesupel2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupel2 ON tbl_pesanhd USING btree (kodesupel);


--
-- Name: kodesupel_depo; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupel_depo ON tbl_accdeposithd USING btree (kodesupel);


--
-- Name: kodesupplier; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupplier ON tbl_byrhutanghd USING btree (kodesupel);


--
-- Name: kodesupplier1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupplier1 ON tbl_byrpiutanghd USING btree (kodesupel);


--
-- Name: kodesupplier1_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupplier1_tko ON tbl_byrpiutangkonsihd USING btree (kodesupel);


--
-- Name: kodesupplier2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupplier2 ON tbl_imhd USING btree (kodesupel);


--
-- Name: kodesupplier2_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupplier2_tki ON tbl_tagihimhd USING btree (kodesupel);


--
-- Name: kodesupplier_kinhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX kodesupplier_kinhd ON tbl_byrhutangkonsihd USING btree (kodesupel);


--
-- Name: matauang; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang ON tbl_acc_sa USING btree (matauang);


--
-- Name: matauang1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang1 ON tbl_acckashd USING btree (matauang);


--
-- Name: matauang10; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang10 ON tbl_pesanhd USING btree (matauang);


--
-- Name: matauang1_depo; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang1_depo ON tbl_accdeposithd USING btree (matauang);


--
-- Name: matauang2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang2 ON tbl_byrhutangdt USING btree (matauang);


--
-- Name: matauang2_kinhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang2_kinhd ON tbl_byrhutangkonsidt USING btree (matauang);


--
-- Name: matauang3; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang3 ON tbl_byrhutanghd USING btree (matauang);


--
-- Name: matauang3_kinhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang3_kinhd ON tbl_byrhutangkonsihd USING btree (matauang);


--
-- Name: matauang4; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang4 ON tbl_byrpiutangdt USING btree (matauang);


--
-- Name: matauang4_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang4_tko ON tbl_byrpiutangkonsidt USING btree (matauang);


--
-- Name: matauang5; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang5 ON tbl_byrpiutanghd USING btree (matauang);


--
-- Name: matauang5_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang5_tko ON tbl_byrpiutangkonsihd USING btree (matauang);


--
-- Name: matauang6; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang6 ON tbl_ikhd USING btree (matauang);


--
-- Name: matauang6_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang6_tko ON tbl_tagihikhd USING btree (matauang);


--
-- Name: matauang6tkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang6tkt ON tbl_itkthd USING btree (matauang);


--
-- Name: matauang7; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang7 ON tbl_imhd USING btree (matauang);


--
-- Name: matauang7_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang7_tki ON tbl_tagihimhd USING btree (matauang);


--
-- Name: matauang8; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang8 ON tbl_item USING btree (matauang);


--
-- Name: matauang9; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX matauang9 ON tbl_perkiraan USING btree (matauang);


--
-- Name: noretur; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX noretur ON tbl_rb_hutang USING btree (noretur);


--
-- Name: noretur1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX noretur1 ON tbl_rj_piutang USING btree (noretur);


--
-- Name: noserial; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX noserial ON tbl_itemserialdt USING btree (noserial);


--
-- Name: notransaksi; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi ON tbl_acckasdt USING btree (notransaksi);


--
-- Name: notransaksi1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi1 ON tbl_byrhutangdt USING btree (notransaksi);


--
-- Name: notransaksi1_kinhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi1_kinhd ON tbl_byrhutangkonsidt USING btree (notransaksi);


--
-- Name: notransaksi2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi2 ON tbl_byrpiutangdt USING btree (notransaksi);


--
-- Name: notransaksi2_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi2_tko ON tbl_byrpiutangkonsidt USING btree (notransaksi);


--
-- Name: notransaksi3; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi3 ON tbl_ikrakitan USING btree (notransaksi);


--
-- Name: notransaksi4; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi4 ON tbl_imrakitan USING btree (notransaksi);


--
-- Name: notransaksi5; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi5 ON tbl_itrdt USING btree (notransaksi);


--
-- Name: notransaksi6; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi6 ON tbl_pesandt USING btree (notransaksi);


--
-- Name: notransaksi_depo; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notransaksi_depo ON tbl_accdepositdt USING btree (notransaksi);


--
-- Name: notrs; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notrs ON tbl_rb_hutang USING btree (notrspot);


--
-- Name: notrs1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notrs1 ON tbl_rj_piutang USING btree (notrspot);


--
-- Name: notrsmasuk; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notrsmasuk ON tbl_byrhutangdt USING btree (notrsmasuk);


--
-- Name: notrsmasuk1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notrsmasuk1 ON tbl_byrpiutangdt USING btree (notrsmasuk);


--
-- Name: notrsmasuk1_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notrsmasuk1_tko ON tbl_byrpiutangkonsidt USING btree (notrsmasuk);


--
-- Name: notrsmasuk_kinhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX notrsmasuk_kinhd ON tbl_byrhutangkonsidt USING btree (notrsmasuk);


--
-- Name: satuan; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuan ON tbl_item USING btree (satuan);


--
-- Name: satuan1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuan1 ON tbl_item_sa USING btree (satuan);


--
-- Name: satuan2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuan2 ON tbl_itemopname USING btree (satuan);


--
-- Name: satuan2sm; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuan2sm ON tbl_itemserialmanage USING btree (satuan);


--
-- Name: satuan3; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuan3 ON tbl_itemsatuanjml USING btree (satuan);


--
-- Name: satuan4; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuan4 ON tbl_itrdt USING btree (satuan);


--
-- Name: satuankonversi; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX satuankonversi ON tbl_itemsatuan USING btree (satuankonversi);


--
-- Name: tbl_accjurnal_notrs; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_accjurnal_notrs ON tbl_accjurnal USING btree (notransaksi);


--
-- Name: tbl_belidt_belihd1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_belidt_belihd1 ON tbl_imdt USING btree (notransaksi);


--
-- Name: tbl_belidt_belihd1_tki; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_belidt_belihd1_tki ON tbl_tagihimdt USING btree (notransaksi);


--
-- Name: tbl_ikdt_ikhd; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_ikdt_ikhd ON tbl_ikdt USING btree (notransaksi);


--
-- Name: tbl_ikdt_ikhd_tko; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_ikdt_ikhd_tko ON tbl_tagihikdt USING btree (notransaksi);


--
-- Name: tbl_ikrakitan_detailtrs1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_ikrakitan_detailtrs1 ON tbl_ikrakitan USING btree (iddetailtrs);


--
-- Name: tbl_ikrakitan_detailtrs2; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_ikrakitan_detailtrs2 ON tbl_imrakitan USING btree (iddetailtrs);


--
-- Name: tbl_item_ik_iddetailim; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_item_ik_iddetailim ON tbl_item_ik USING btree (iddetailim);


--
-- Name: tbl_item_ik_iddetailtrs; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_item_ik_iddetailtrs ON tbl_item_ik USING btree (iddetailtrs);


--
-- Name: tbl_item_ik_notrs; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_item_ik_notrs ON tbl_item_ik USING btree (notransaksi);


--
-- Name: tbl_item_ikko_iddetail; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_item_ikko_iddetail ON tbl_item_ikko USING btree (notransaksi, iddetailtrs, iddetailik);


--
-- Name: tbl_item_im_iddetailtrs; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_item_im_iddetailtrs ON tbl_item_im USING btree (iddetailtrs);


--
-- Name: tbl_item_im_notrs; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_item_im_notrs ON tbl_item_im USING btree (notransaksi);


--
-- Name: tbl_itemserial_index; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_itemserial_index ON tbl_itemserial USING btree (kodeitem, kodekantor, tipe, notransaksi);


--
-- Name: tbl_itemserialdt_index; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_itemserialdt_index ON tbl_itemserialdt USING btree (iddetail, notransaksi, tipe);


--
-- Name: tbl_itktdt_itkthdtkt; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_itktdt_itkthdtkt ON tbl_itktdt USING btree (notransaksi);


--
-- Name: tbl_supel_kode_key; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE UNIQUE INDEX tbl_supel_kode_key ON tbl_supel USING btree (kode);


--
-- Name: tbl_supplier_fk_mu1; Type: INDEX; Schema: public; Owner: sysi5adm
--

CREATE INDEX tbl_supplier_fk_mu1 ON tbl_supel USING btree (matauang);


--
-- Name: piutang_tkt_blok_delete; Type: TRIGGER; Schema: public; Owner: sysi5adm
--

CREATE TRIGGER piutang_tkt_blok_delete BEFORE DELETE ON tbl_itkthd FOR EACH ROW EXECUTE PROCEDURE piutang_trs_blok_delete();


--
-- Name: piutang_trs_blok_delete; Type: TRIGGER; Schema: public; Owner: sysi5adm
--

CREATE TRIGGER piutang_trs_blok_delete BEFORE DELETE ON tbl_ikhd FOR EACH ROW EXECUTE PROCEDURE piutang_trs_blok_delete();


--
-- Name: tbl_ikhd_update_notrs; Type: TRIGGER; Schema: public; Owner: sysi5adm
--

CREATE TRIGGER tbl_ikhd_update_notrs AFTER UPDATE ON tbl_ikhd FOR EACH ROW EXECUTE PROCEDURE trg_update_ikhd_notrs();


--
-- Name: tbl_imhd_update_notrs; Type: TRIGGER; Schema: public; Owner: sysi5adm
--

CREATE TRIGGER tbl_imhd_update_notrs AFTER UPDATE ON tbl_imhd FOR EACH ROW EXECUTE PROCEDURE trg_update_imhd_notrs();


--
-- Name: tbl_itkthd_update_notrs; Type: TRIGGER; Schema: public; Owner: sysi5adm
--

CREATE TRIGGER tbl_itkthd_update_notrs AFTER UPDATE ON tbl_itkthd FOR EACH ROW EXECUTE PROCEDURE trg_update_tkthd_notrs();


--
-- Name: trg_update_hpp; Type: TRIGGER; Schema: public; Owner: sysi5adm
--

CREATE TRIGGER trg_update_hpp AFTER UPDATE OF hargadasar ON tbl_item_im FOR EACH ROW EXECUTE PROCEDURE trg_update_hpp();


--
-- Name: kodeacc_key; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kantor
    ADD CONSTRAINT kodeacc_key FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: no_transaksi; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kaslacidt
    ADD CONSTRAINT no_transaksi FOREIGN KEY (notransaksi) REFERENCES tbl_kaslaci(notransaksi) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_acc_sa_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acc_sa
    ADD CONSTRAINT tbl_acc_sa_kodeacc FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_acc_sa_matauang; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acc_sa
    ADD CONSTRAINT tbl_acc_sa_matauang FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_accdepodt_hd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdepositdt
    ADD CONSTRAINT tbl_accdepodt_hd FOREIGN KEY (notransaksi) REFERENCES tbl_accdeposithd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_accdepodt_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdepositdt
    ADD CONSTRAINT tbl_accdepodt_kodeacc FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_accdepodt_matauang; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdepositdt
    ADD CONSTRAINT tbl_accdepodt_matauang FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_accdepohd_acc1; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdeposithd
    ADD CONSTRAINT tbl_accdepohd_acc1 FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_accdepohd_acc2; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdeposithd
    ADD CONSTRAINT tbl_accdepohd_acc2 FOREIGN KEY (kodeaccto) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_accdepohd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdeposithd
    ADD CONSTRAINT tbl_accdepohd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_accdepohd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdeposithd
    ADD CONSTRAINT tbl_accdepohd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_accdepohd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accdeposithd
    ADD CONSTRAINT tbl_accdepohd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_accjurnal_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accjurnal
    ADD CONSTRAINT tbl_accjurnal_kantor FOREIGN KEY (kantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_accjurnal_kategori_kas; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accjurnal
    ADD CONSTRAINT tbl_accjurnal_kategori_kas FOREIGN KEY (kategori_kas) REFERENCES tbl_kategori_kas(kodekategori) ON UPDATE CASCADE;


--
-- Name: tbl_accjurnal_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accjurnal
    ADD CONSTRAINT tbl_accjurnal_kodeacc FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_accjurnal_matauang; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_accjurnal
    ADD CONSTRAINT tbl_accjurnal_matauang FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_acckasdt_hd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckasdt
    ADD CONSTRAINT tbl_acckasdt_hd FOREIGN KEY (notransaksi) REFERENCES tbl_acckashd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_acckasdt_kategori_kas; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckasdt
    ADD CONSTRAINT tbl_acckasdt_kategori_kas FOREIGN KEY (kategori_kas) REFERENCES tbl_kategori_kas(kodekategori) ON UPDATE CASCADE;


--
-- Name: tbl_acckasdt_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckasdt
    ADD CONSTRAINT tbl_acckasdt_kodeacc FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_acckasdt_matauang; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckasdt
    ADD CONSTRAINT tbl_acckasdt_matauang FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_acckashd_acc1; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckashd
    ADD CONSTRAINT tbl_acckashd_acc1 FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_acckashd_acc2; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckashd
    ADD CONSTRAINT tbl_acckashd_acc2 FOREIGN KEY (kodeaccto) REFERENCES tbl_perkiraan(kodeacc) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_acckashd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckashd
    ADD CONSTRAINT tbl_acckashd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_acckashd_kategori_kas; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckashd
    ADD CONSTRAINT tbl_acckashd_kategori_kas FOREIGN KEY (kategori_kas) REFERENCES tbl_kategori_kas(kodekategori) ON UPDATE CASCADE;


--
-- Name: tbl_acckashd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_acckashd
    ADD CONSTRAINT tbl_acckashd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_alamatkirim_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_alamatkirim
    ADD CONSTRAINT tbl_alamatkirim_supel FOREIGN KEY (kode_supel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_bank_acc_kd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_bank
    ADD CONSTRAINT tbl_bank_acc_kd FOREIGN KEY (acc_kd) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_bank_acc_kk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_bank
    ADD CONSTRAINT tbl_bank_acc_kk FOREIGN KEY (acc_kk) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_belidt_belihd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imdt
    ADD CONSTRAINT tbl_belidt_belihd FOREIGN KEY (notransaksi) REFERENCES tbl_imhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_belidt_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imdt
    ADD CONSTRAINT tbl_belidt_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrhutangdt_fk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangdt
    ADD CONSTRAINT tbl_byrhutangdt_fk FOREIGN KEY (notransaksi) REFERENCES tbl_byrhutanghd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrhutangdt_fk_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangdt
    ADD CONSTRAINT tbl_byrhutangdt_fk_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrhutangdt_im; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangdt
    ADD CONSTRAINT tbl_byrhutangdt_im FOREIGN KEY (notrsmasuk) REFERENCES tbl_imhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutanghd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutanghd
    ADD CONSTRAINT tbl_byrhutanghd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutanghd_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutanghd
    ADD CONSTRAINT tbl_byrhutanghd_kodeacc FOREIGN KEY (acc_bayar) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutanghd_kodeacc_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutanghd
    ADD CONSTRAINT tbl_byrhutanghd_kodeacc_pot FOREIGN KEY (acc_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutanghd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutanghd
    ADD CONSTRAINT tbl_byrhutanghd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrhutanghd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutanghd
    ADD CONSTRAINT tbl_byrhutanghd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutangitem_header; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangitem
    ADD CONSTRAINT tbl_byrhutangitem_header FOREIGN KEY (notransaksi) REFERENCES tbl_byrhutanghd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutangitem_hutangdt; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangitem
    ADD CONSTRAINT tbl_byrhutangitem_hutangdt FOREIGN KEY (iddetail) REFERENCES tbl_byrhutangdt(iddetail) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_byrhutangitem_kodeitem; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangitem
    ADD CONSTRAINT tbl_byrhutangitem_kodeitem FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_byrhutangkonsihd_kodeacc_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsihd
    ADD CONSTRAINT tbl_byrhutangkonsihd_kodeacc_pot FOREIGN KEY (acc_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislsdt_fk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislsdt
    ADD CONSTRAINT tbl_byrkomisislsdt_fk FOREIGN KEY (notransaksi) REFERENCES tbl_byrkomisislshd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislsdt_fk_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislsdt
    ADD CONSTRAINT tbl_byrkomisislsdt_fk_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislshd_acc_komisi_sales; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislshd
    ADD CONSTRAINT tbl_byrkomisislshd_acc_komisi_sales FOREIGN KEY (acc_komisi_sales) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislshd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislshd
    ADD CONSTRAINT tbl_byrkomisislshd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislshd_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislshd
    ADD CONSTRAINT tbl_byrkomisislshd_kodeacc FOREIGN KEY (acc_bayar) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislshd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislshd
    ADD CONSTRAINT tbl_byrkomisislshd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_byrkomisislshd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrkomisislshd
    ADD CONSTRAINT tbl_byrkomisislshd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiindt_matauang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsidt
    ADD CONSTRAINT tbl_byrkonsiindt_matauang_fkey FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiindt_notransaksi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsidt
    ADD CONSTRAINT tbl_byrkonsiindt_notransaksi_fkey FOREIGN KEY (notransaksi) REFERENCES tbl_byrhutangkonsihd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiindt_notrsmasuk_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsidt
    ADD CONSTRAINT tbl_byrkonsiindt_notrsmasuk_fkey FOREIGN KEY (notrsmasuk) REFERENCES tbl_tagihimhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiinhd_acc_bayar_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsihd
    ADD CONSTRAINT tbl_byrkonsiinhd_acc_bayar_fkey FOREIGN KEY (acc_bayar) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiinhd_kodekantor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsihd
    ADD CONSTRAINT tbl_byrkonsiinhd_kodekantor_fkey FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiinhd_kodesupel_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsihd
    ADD CONSTRAINT tbl_byrkonsiinhd_kodesupel_fkey FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_byrkonsiinhd_matauang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrhutangkonsihd
    ADD CONSTRAINT tbl_byrkonsiinhd_matauang_fkey FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangdt_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangdt
    ADD CONSTRAINT tbl_byrpiutangdt_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangdt_notrs; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangdt
    ADD CONSTRAINT tbl_byrpiutangdt_notrs FOREIGN KEY (notransaksi) REFERENCES tbl_byrpiutanghd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutanghd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutanghd
    ADD CONSTRAINT tbl_byrpiutanghd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutanghd_kodeacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutanghd
    ADD CONSTRAINT tbl_byrpiutanghd_kodeacc FOREIGN KEY (acc_bayar) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutanghd_kodeacc_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutanghd
    ADD CONSTRAINT tbl_byrpiutanghd_kodeacc_pot FOREIGN KEY (acc_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutanghd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutanghd
    ADD CONSTRAINT tbl_byrpiutanghd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutanghd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutanghd
    ADD CONSTRAINT tbl_byrpiutanghd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsidt_matauang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsidt
    ADD CONSTRAINT tbl_byrpiutangkonsidt_matauang_fkey FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsidt_notransaksi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsidt
    ADD CONSTRAINT tbl_byrpiutangkonsidt_notransaksi_fkey FOREIGN KEY (notransaksi) REFERENCES tbl_byrpiutangkonsihd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsidt_notrsmasuk_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsidt
    ADD CONSTRAINT tbl_byrpiutangkonsidt_notrsmasuk_fkey FOREIGN KEY (notrsmasuk) REFERENCES tbl_tagihikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsihd_acc_bayar_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsihd
    ADD CONSTRAINT tbl_byrpiutangkonsihd_acc_bayar_fkey FOREIGN KEY (acc_bayar) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsihd_kodeacc_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsihd
    ADD CONSTRAINT tbl_byrpiutangkonsihd_kodeacc_pot FOREIGN KEY (acc_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsihd_kodekantor_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsihd
    ADD CONSTRAINT tbl_byrpiutangkonsihd_kodekantor_fkey FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsihd_kodesupel_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsihd
    ADD CONSTRAINT tbl_byrpiutangkonsihd_kodesupel_fkey FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_byrpiutangkonsihd_matauang_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_byrpiutangkonsihd
    ADD CONSTRAINT tbl_byrpiutangkonsihd_matauang_fkey FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_emoney_acc_prod; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_emoney
    ADD CONSTRAINT tbl_emoney_acc_prod FOREIGN KEY (acc_prod) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_formatnotr_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_formatnotr
    ADD CONSTRAINT tbl_formatnotr_kantor FOREIGN KEY (kantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_hupi_sa_acc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_hupi_sa
    ADD CONSTRAINT tbl_hupi_sa_acc FOREIGN KEY (kode_acc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_hupi_sa_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_hupi_sa
    ADD CONSTRAINT tbl_hupi_sa_mu FOREIGN KEY (kodemu) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_hupi_sa_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_hupi_sa
    ADD CONSTRAINT tbl_hupi_sa_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_ikdt_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikdt
    ADD CONSTRAINT tbl_ikdt_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_ikdt_notransaksi; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikdt
    ADD CONSTRAINT tbl_ikdt_notransaksi FOREIGN KEY (notransaksi) REFERENCES tbl_ikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_ikdt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikdt
    ADD CONSTRAINT tbl_ikdt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_accdebit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_accdebit FOREIGN KEY (acc_debit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_accdppsn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_accdppsn FOREIGN KEY (acc_dppesanan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_accemoney; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_accemoney FOREIGN KEY (acc_emoney) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_acckk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_acckk FOREIGN KEY (acc_kk) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_accpendpembulatan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_accpendpembulatan FOREIGN KEY (acc_pend_pembulatan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_accpot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_accpot FOREIGN KEY (acc_potongan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_accsales; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_accsales FOREIGN KEY (acc_sales) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_bank_kd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_bank_kd FOREIGN KEY (byr_debit_bank) REFERENCES tbl_bank(kodebank) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_bank_kk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_bank_kk FOREIGN KEY (byr_kk_bank) REFERENCES tbl_bank(kodebank) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_biaya; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_biaya FOREIGN KEY (acc_biayalain) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_biaya_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_biaya_pot FOREIGN KEY (acc_biaya_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_emoney_prod; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_emoney_prod FOREIGN KEY (byr_emoney_prod) REFERENCES tbl_emoney(kodeprod) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_hpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_hpp FOREIGN KEY (acc_hpp) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_kantordari; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_kantordari FOREIGN KEY (kantordari) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_kredit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_kredit FOREIGN KEY (acc_kredit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_pajak; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_pajak FOREIGN KEY (acc_pajak) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_pajakpph23; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_pajakpph23 FOREIGN KEY (acc_pajakpph23) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_pajakppnbm; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_pajakppnbm FOREIGN KEY (acc_pajakppnbm) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_retur FOREIGN KEY (notrsretur) REFERENCES tbl_ikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_sales; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_sales FOREIGN KEY (kodesales) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_sales2; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_sales2 FOREIGN KEY (kodesales2) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_sales3; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_sales3 FOREIGN KEY (kodesales3) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_sales4; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_sales4 FOREIGN KEY (kodesales4) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_point_sa
    ADD CONSTRAINT tbl_ikhd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_ikhd_tunai; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikhd
    ADD CONSTRAINT tbl_ikhd_tunai FOREIGN KEY (acc_tunai) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_ikrakitan_detailtrs; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikrakitan
    ADD CONSTRAINT tbl_ikrakitan_detailtrs FOREIGN KEY (iddetailtrs) REFERENCES tbl_ikdt(iddetail) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_ikrakitan_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikrakitan
    ADD CONSTRAINT tbl_ikrakitan_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_ikrakitan_notrs; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikrakitan
    ADD CONSTRAINT tbl_ikrakitan_notrs FOREIGN KEY (notransaksi) REFERENCES tbl_ikhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_ikrakitan_rakitan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikrakitan
    ADD CONSTRAINT tbl_ikrakitan_rakitan FOREIGN KEY (kodeitemrakitan) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_ikrakitan_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_ikrakitan
    ADD CONSTRAINT tbl_ikrakitan_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_imdt_fk_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imdt
    ADD CONSTRAINT tbl_imdt_fk_kantor FOREIGN KEY (sakantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_imdt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imdt
    ADD CONSTRAINT tbl_imdt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_accbiaya; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_accbiaya FOREIGN KEY (acc_biayalain) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_accdppsn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_accdppsn FOREIGN KEY (acc_dppesanan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_acchpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_acchpp FOREIGN KEY (acc_hpp) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_acckredit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_acckredit FOREIGN KEY (acc_kredit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_accpajak; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_accpajak FOREIGN KEY (acc_pajak) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_accpot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_accpot FOREIGN KEY (acc_potongan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_acctunai; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_acctunai FOREIGN KEY (acc_tunai) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_biaya_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_biaya_pot FOREIGN KEY (acc_biaya_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_kantortjn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_kantortjn FOREIGN KEY (kantortujuan) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_retur FOREIGN KEY (notrsretur) REFERENCES tbl_imhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_imhd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imhd
    ADD CONSTRAINT tbl_imhd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_imrakitan_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imrakitan
    ADD CONSTRAINT tbl_imrakitan_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_imrakitan_notrs; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imrakitan
    ADD CONSTRAINT tbl_imrakitan_notrs FOREIGN KEY (notransaksi) REFERENCES tbl_imhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_imrakitan_rakitan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_imrakitan
    ADD CONSTRAINT tbl_imrakitan_rakitan FOREIGN KEY (kodeitemrakitan) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_item_acchpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_acchpp FOREIGN KEY (acc_hpp) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_item_accjasa; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_accjasa FOREIGN KEY (acc_jasa) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_item_accnoninv; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_accnoninv FOREIGN KEY (acc_noninventory) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_item_accpendpt; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_accpendpt FOREIGN KEY (acc_pendapatan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_item_accpersdn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_accpersdn FOREIGN KEY (acc_persediaan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_item_bahanbaku; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_bahanbaku FOREIGN KEY (acc_perbahanbaku) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_item_dept_gudang; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_dept_gudang FOREIGN KEY (dept) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_item_ik_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ik
    ADD CONSTRAINT tbl_item_ik_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_ik_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ik
    ADD CONSTRAINT tbl_item_ik_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_ik_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ik
    ADD CONSTRAINT tbl_item_ik_satuan FOREIGN KEY (satuandasar) REFERENCES tbl_itemsatuan(satuan) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_ikko_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ikko
    ADD CONSTRAINT tbl_item_ikko_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_ikret_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_ikret
    ADD CONSTRAINT tbl_item_ikret_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_im_fk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_im
    ADD CONSTRAINT tbl_item_im_fk FOREIGN KEY (satuandasar) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_im_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_im
    ADD CONSTRAINT tbl_item_im_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_im_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_im
    ADD CONSTRAINT tbl_item_im_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_im_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_im
    ADD CONSTRAINT tbl_item_im_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_imret_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_imret
    ADD CONSTRAINT tbl_item_imret_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_jenis; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_jenis FOREIGN KEY (jenis) REFERENCES tbl_itemjenis(jenis) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_item_matauang; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_matauang FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_item_merek; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_merek FOREIGN KEY (merek) REFERENCES tbl_itemmerek(merek) ON UPDATE CASCADE;


--
-- Name: tbl_item_overhead; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_overhead FOREIGN KEY (acc_byoverhead) REFERENCES tbl_perkiraan(kodeacc);


--
-- Name: tbl_item_rekap_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_rekap
    ADD CONSTRAINT tbl_item_rekap_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_rekap_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_rekap
    ADD CONSTRAINT tbl_item_rekap_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_item_rekap_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_rekap
    ADD CONSTRAINT tbl_item_rekap_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_item_sa_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_sa
    ADD CONSTRAINT tbl_item_sa_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_item_sa_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_sa
    ADD CONSTRAINT tbl_item_sa_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_item_sa_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item_sa
    ADD CONSTRAINT tbl_item_sa_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_item_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_item_supplier; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_supplier FOREIGN KEY (supplier1) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_item_tenagakerja; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_item
    ADD CONSTRAINT tbl_item_tenagakerja FOREIGN KEY (acc_bytenagakerja) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itemdisp_jenis_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemdisp
    ADD CONSTRAINT tbl_itemdisp_jenis_fkey FOREIGN KEY (jenis) REFERENCES tbl_itemjenis(jenis) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemdisp_merek_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemdisp
    ADD CONSTRAINT tbl_itemdisp_merek_fkey FOREIGN KEY (merek) REFERENCES tbl_itemmerek(merek) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemdispdt_iddiskon_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemdispdt
    ADD CONSTRAINT tbl_itemdispdt_iddiskon_fkey FOREIGN KEY (iddiskon) REFERENCES tbl_itemdisp(iddiskon) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemdispdt_kodeitem_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemdispdt
    ADD CONSTRAINT tbl_itemdispdt_kodeitem_fkey FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemdispdt_satuan_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemdispdt
    ADD CONSTRAINT tbl_itemdispdt_satuan_fkey FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemhj_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemhj
    ADD CONSTRAINT tbl_itemhj_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemhj_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemhj
    ADD CONSTRAINT tbl_itemhj_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_itemopname_acc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemopname
    ADD CONSTRAINT tbl_itemopname_acc FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itemopname_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemopname
    ADD CONSTRAINT tbl_itemopname_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itemopname_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemopname
    ADD CONSTRAINT tbl_itemopname_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_itemopname_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemopname
    ADD CONSTRAINT tbl_itemopname_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itempotongan_grup; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempotongan
    ADD CONSTRAINT tbl_itempotongan_grup FOREIGN KEY (kodegrup) REFERENCES tbl_supelgrup(kgrup) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempotongan_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempotongan
    ADD CONSTRAINT tbl_itempotongan_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempromo_jenis_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromo
    ADD CONSTRAINT tbl_itempromo_jenis_fkey FOREIGN KEY (jenis) REFERENCES tbl_itemjenis(jenis) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempromo_merek_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromo
    ADD CONSTRAINT tbl_itempromo_merek_fkey FOREIGN KEY (merek) REFERENCES tbl_itemmerek(merek) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempromodt_idpromo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromodt
    ADD CONSTRAINT tbl_itempromodt_idpromo_fkey FOREIGN KEY (idpromo) REFERENCES tbl_itempromo(idpromo) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempromodt_kodeitem_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromodt
    ADD CONSTRAINT tbl_itempromodt_kodeitem_fkey FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempromodt_satuangratis_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromodt
    ADD CONSTRAINT tbl_itempromodt_satuangratis_fkey FOREIGN KEY (satuangratis) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itempromodt_satuanjual_fkey; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itempromodt
    ADD CONSTRAINT tbl_itempromodt_satuanjual_fkey FOREIGN KEY (satuanjual) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemrakitan_fk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemrakitan
    ADD CONSTRAINT tbl_itemrakitan_fk FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_itemrakitan_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemrakitan
    ADD CONSTRAINT tbl_itemrakitan_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemrakitan_itemsub; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemrakitan
    ADD CONSTRAINT tbl_itemrakitan_itemsub FOREIGN KEY (kodeitemrakitan) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemsatuanjml_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemsatuanjml
    ADD CONSTRAINT tbl_itemsatuanjml_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemsatuanjml_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemsatuanjml
    ADD CONSTRAINT tbl_itemsatuanjml_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itemserial_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserial
    ADD CONSTRAINT tbl_itemserial_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemserial_kodeitem; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserial
    ADD CONSTRAINT tbl_itemserial_kodeitem FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_itemserialdt_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserialdt
    ADD CONSTRAINT tbl_itemserialdt_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemserialdt_kodeitem; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserialdt
    ADD CONSTRAINT tbl_itemserialdt_kodeitem FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemserialkotag_kodeitem; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserial_kotag
    ADD CONSTRAINT tbl_itemserialkotag_kodeitem FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_itemsermanage_iddetail_key; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserialmanage
    ADD CONSTRAINT tbl_itemsermanage_iddetail_key FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_itemsermanage_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserialmanage
    ADD CONSTRAINT tbl_itemsermanage_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_itemsermanage_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemserialmanage
    ADD CONSTRAINT tbl_itemsermanage_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_itemstok_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemstok
    ADD CONSTRAINT tbl_itemstok_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_itemstok_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itemstok
    ADD CONSTRAINT tbl_itemstok_kantor FOREIGN KEY (kantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_ithd_acchpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrhd
    ADD CONSTRAINT tbl_ithd_acchpp FOREIGN KEY (acc_persediaan) REFERENCES tbl_perkiraan(kodeacc) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itktdt_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itktdt
    ADD CONSTRAINT tbl_itktdt_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_itktdt_notransaksi; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itktdt
    ADD CONSTRAINT tbl_itktdt_notransaksi FOREIGN KEY (notransaksi) REFERENCES tbl_itkthd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_itktdt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itktdt
    ADD CONSTRAINT tbl_itktdt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_accdebit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_accdebit FOREIGN KEY (acc_debit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_accdppsn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_accdppsn FOREIGN KEY (acc_dppesanan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_accemoney; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_accemoney FOREIGN KEY (acc_emoney) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_acckk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_acckk FOREIGN KEY (acc_kk) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_accpot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_accpot FOREIGN KEY (acc_potongan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_accsales; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_accsales FOREIGN KEY (acc_sales) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_bank_kd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_bank_kd FOREIGN KEY (byr_debit_bank) REFERENCES tbl_bank(kodebank) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_bank_kk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_bank_kk FOREIGN KEY (byr_kk_bank) REFERENCES tbl_bank(kodebank) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_biaya; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_biaya FOREIGN KEY (acc_biayalain) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_biaya_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_biaya_pot FOREIGN KEY (acc_biaya_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_emoney_prod; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_emoney_prod FOREIGN KEY (byr_emoney_prod) REFERENCES tbl_emoney(kodeprod) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_hpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_hpp FOREIGN KEY (acc_hpp) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_kantordari; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_kantordari FOREIGN KEY (kantordari) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_kredit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_kredit FOREIGN KEY (acc_kredit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_pajak_keluar; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_pajak_keluar FOREIGN KEY (acc_pajak) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_pajak_masuk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_pajak_masuk FOREIGN KEY (acc_pajak_in) REFERENCES tbl_perkiraan(kodeacc);


--
-- Name: tbl_itkthd_pajakpph23; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_pajakpph23 FOREIGN KEY (acc_pajakpph23) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_pajakppnbm; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_pajakppnbm FOREIGN KEY (acc_pajakppnbm) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_retur FOREIGN KEY (notrsretur) REFERENCES tbl_ikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_sales; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_sales FOREIGN KEY (kodesales) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_sales2; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_sales2 FOREIGN KEY (kodesales2) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_sales3; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_sales3 FOREIGN KEY (kodesales3) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_sales4; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_sales4 FOREIGN KEY (kodesales4) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_itkthd_tunai; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itkthd
    ADD CONSTRAINT tbl_itkthd_tunai FOREIGN KEY (acc_tunai) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_itrdt_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrdt
    ADD CONSTRAINT tbl_itrdt_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itrdt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrdt
    ADD CONSTRAINT tbl_itrdt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itrdt_trhd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrdt
    ADD CONSTRAINT tbl_itrdt_trhd FOREIGN KEY (notransaksi) REFERENCES tbl_itrhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_itrhd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrhd
    ADD CONSTRAINT tbl_itrhd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_itrhd_kantordari; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrhd
    ADD CONSTRAINT tbl_itrhd_kantordari FOREIGN KEY (kantordari) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_itrhd_kantortujuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_itrhd
    ADD CONSTRAINT tbl_itrhd_kantortujuan FOREIGN KEY (kantortujuan) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_kasktsetting_kategori; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_kasktsetting
    ADD CONSTRAINT tbl_kasktsetting_kategori FOREIGN KEY (kodekategori) REFERENCES tbl_kategori_kas(kodekategori) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: tbl_mu_ratesa_fk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_mu_ratesa
    ADD CONSTRAINT tbl_mu_ratesa_fk FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_orderbelidt_fk_tblitem; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesandt
    ADD CONSTRAINT tbl_orderbelidt_fk_tblitem FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_perkiraan_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_perkiraan
    ADD CONSTRAINT tbl_perkiraan_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_perkiraan_parent; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_perkiraan
    ADD CONSTRAINT tbl_perkiraan_parent FOREIGN KEY (parentacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_perksetting_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_perksetting
    ADD CONSTRAINT tbl_perksetting_kantor FOREIGN KEY (acckantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_perksetting_perkiraan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_perksetting
    ADD CONSTRAINT tbl_perksetting_perkiraan FOREIGN KEY (kodeacc) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: tbl_pesanbelihd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanbelihd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_pesandt_hd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesandt
    ADD CONSTRAINT tbl_pesandt_hd FOREIGN KEY (notransaksi) REFERENCES tbl_pesanhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_pesandt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesandt
    ADD CONSTRAINT tbl_pesandt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_accdpkas; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_accdpkas FOREIGN KEY (acc_dpkas) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_accdppsn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_accdppsn FOREIGN KEY (acc_dppesanan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_biaya_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_biaya_pot FOREIGN KEY (acc_biaya_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_ktrtujuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_ktrtujuan FOREIGN KEY (kantortujuan) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_sales; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_sales FOREIGN KEY (kodesales) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_pesanhd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanhd
    ADD CONSTRAINT tbl_pesanhd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_pesanrakitan_dt; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanrakitan
    ADD CONSTRAINT tbl_pesanrakitan_dt FOREIGN KEY (iddetailtrs) REFERENCES tbl_pesandt(iddetail) ON UPDATE CASCADE;


--
-- Name: tbl_pesanrakitan_hd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanrakitan
    ADD CONSTRAINT tbl_pesanrakitan_hd FOREIGN KEY (notransaksi) REFERENCES tbl_pesanhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_pesanrakitan_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanrakitan
    ADD CONSTRAINT tbl_pesanrakitan_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_pesanrakitan_rakitan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanrakitan
    ADD CONSTRAINT tbl_pesanrakitan_rakitan FOREIGN KEY (kodeitemrakitan) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_pesanrakitan_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pesanrakitan
    ADD CONSTRAINT tbl_pesanrakitan_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_pointambil_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pointambil
    ADD CONSTRAINT tbl_pointambil_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_pointambil_pelanggan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_pointambil
    ADD CONSTRAINT tbl_pointambil_pelanggan FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_rb_hutang_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_rb_hutang
    ADD CONSTRAINT tbl_rb_hutang_retur FOREIGN KEY (noretur) REFERENCES tbl_imhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_rb_hutang_trs; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_rb_hutang
    ADD CONSTRAINT tbl_rb_hutang_trs FOREIGN KEY (notrspot) REFERENCES tbl_imhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_rj_piutang_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_rj_piutang
    ADD CONSTRAINT tbl_rj_piutang_retur FOREIGN KEY (noretur) REFERENCES tbl_ikhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_rj_piutang_trs; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_rj_piutang
    ADD CONSTRAINT tbl_rj_piutang_trs FOREIGN KEY (notrspot) REFERENCES tbl_ikhd(notransaksi) MATCH FULL ON UPDATE CASCADE;


--
-- Name: tbl_supel_fk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supel
    ADD CONSTRAINT tbl_supel_fk FOREIGN KEY (kdsubwil) REFERENCES tbl_supel_subwil(kode) ON UPDATE CASCADE;


--
-- Name: tbl_supel_wilayah; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supel
    ADD CONSTRAINT tbl_supel_wilayah FOREIGN KEY (kdwilayah) REFERENCES tbl_supel_wil(kode) ON UPDATE CASCADE;


--
-- Name: tbl_supplier_fk_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_supel
    ADD CONSTRAINT tbl_supplier_fk_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) MATCH FULL;


--
-- Name: tbl_tagihikdt_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikdt
    ADD CONSTRAINT tbl_tagihikdt_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikdt_notransaksi; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikdt
    ADD CONSTRAINT tbl_tagihikdt_notransaksi FOREIGN KEY (notransaksi) REFERENCES tbl_tagihikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikdt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikdt
    ADD CONSTRAINT tbl_tagihikdt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_accdebit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_accdebit FOREIGN KEY (acc_debit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_accdppsn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_accdppsn FOREIGN KEY (acc_dppesanan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_acckk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_acckk FOREIGN KEY (acc_kk) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_accpot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_accpot FOREIGN KEY (acc_potongan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_bank_kd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_bank_kd FOREIGN KEY (byr_debit_bank) REFERENCES tbl_bank(kodebank) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_bank_kk; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_bank_kk FOREIGN KEY (byr_kk_bank) REFERENCES tbl_bank(kodebank) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_biaya; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_biaya FOREIGN KEY (acc_biayalain) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_biaya_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_biaya_pot FOREIGN KEY (acc_biaya_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_hpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_hpp FOREIGN KEY (acc_hpp) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_kantordari; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_kantordari FOREIGN KEY (kantordari) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_kredit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_kredit FOREIGN KEY (acc_kredit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_pajak; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_pajak FOREIGN KEY (acc_pajak) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_pajakppnbm; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_pajakppnbm FOREIGN KEY (acc_pajakppnbm) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_retur FOREIGN KEY (notrsretur) REFERENCES tbl_tagihikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_tagihikhd_tunai; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihikhd_tunai FOREIGN KEY (acc_tunai) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimdt_fk_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimdt
    ADD CONSTRAINT tbl_tagihimdt_fk_kantor FOREIGN KEY (sakantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimdt_item; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimdt
    ADD CONSTRAINT tbl_tagihimdt_item FOREIGN KEY (kodeitem) REFERENCES tbl_item(kodeitem) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimdt_satuan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimdt
    ADD CONSTRAINT tbl_tagihimdt_satuan FOREIGN KEY (satuan) REFERENCES tbl_itemsatuan(satuan) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimdt_tagihimhd; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimdt
    ADD CONSTRAINT tbl_tagihimdt_tagihimhd FOREIGN KEY (notransaksi) REFERENCES tbl_tagihimhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_accbiaya; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_accbiaya FOREIGN KEY (acc_biayalain) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_accdppsn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_accdppsn FOREIGN KEY (acc_dppesanan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_acchpp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_acchpp FOREIGN KEY (acc_hpp) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_acckredit; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_acckredit FOREIGN KEY (acc_kredit) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_accpajak; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_accpajak FOREIGN KEY (acc_pajak) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_accpot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_accpot FOREIGN KEY (acc_potongan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_acctagihan; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_acctagihan FOREIGN KEY (acc_tagihan) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_acctunai; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_acctunai FOREIGN KEY (acc_tunai) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_biaya_pot; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_biaya_pot FOREIGN KEY (acc_biaya_pot) REFERENCES tbl_perkiraan(kodeacc) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_kantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_kantor FOREIGN KEY (kodekantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_kantortjn; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_kantortjn FOREIGN KEY (kantortujuan) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_mu; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_mu FOREIGN KEY (matauang) REFERENCES tbl_matauang(matauang) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_retur; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_retur FOREIGN KEY (notrsretur) REFERENCES tbl_tagihimhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_tagihimhd_supel; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihimhd
    ADD CONSTRAINT tbl_tagihimhd_supel FOREIGN KEY (kodesupel) REFERENCES tbl_supel(kode) ON UPDATE CASCADE;


--
-- Name: tbl_tagihkhd_notransaksi_ko; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_tagihikhd
    ADD CONSTRAINT tbl_tagihkhd_notransaksi_ko FOREIGN KEY (notransaksi_ko) REFERENCES tbl_ikhd(notransaksi) ON UPDATE CASCADE;


--
-- Name: tbl_user_kelompokacc; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_user
    ADD CONSTRAINT tbl_user_kelompokacc FOREIGN KEY (kelompok) REFERENCES tbl_userg(kelompok) ON UPDATE CASCADE;


--
-- Name: tbl_user_loginkantor; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_user
    ADD CONSTRAINT tbl_user_loginkantor FOREIGN KEY (loginkantor) REFERENCES tbl_kantor(kodekantor) ON UPDATE CASCADE;


--
-- Name: tbl_userakses_klp; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_userakses
    ADD CONSTRAINT tbl_userakses_klp FOREIGN KEY (klpakses) REFERENCES tbl_userg(kelompok) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tbl_usercus_acc_userg; Type: FK CONSTRAINT; Schema: public; Owner: sysi5adm
--

ALTER TABLE ONLY tbl_usercus_acc
    ADD CONSTRAINT tbl_usercus_acc_userg FOREIGN KEY (klpakses) REFERENCES tbl_userg(kelompok) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

