# Sistem Manajemen Inventaris & Distribusi Seragam (Kedai Nusaputera)

Aplikasi web berbasis PHP & MySQL untuk mengelola pengadaan stok seragam (PO Vendor), pendataan pengukuran siswa berdasarkan gelombang pendaftaran, kontrol stok real-time, kasir penjualan langsung, serta integrasi notifikasi WhatsApp.

## Pembagian Tugas Tim
1. **Aresta Stenly Susanto** *(Project Lead & Core Developer)*
   - Perancangan database (ERD), sistem autentikasi (Admin & Manager), UI/UX Layout, integrasi WhatsApp API (Fonnte), dan manajemen GitHub (Merge/Review).
2. **Theo** *(Supply Chain & Master Data Developer)*
   - Modul Master Produk & Ukuran, Manajemen Gelombang, PO Vendor (Supply Chain), Tukar Ukuran, dan Koreksi/Inject Stok.
3. **Michael Felix** *(Operational, Cashier & Reporting Developer)*
   - Modul Pengukuran Siswa, Penjualan Langsung (Kasir Hybrid), Master Harga, Setoran Keuangan, dan Ekspor Laporan (Matriks, Audit Mutasi, Rekap Excel).

## Planning & Timeline Pengerjaan
- **Fase 1 (Minggu 1) — Fondasi & Master Data:** Setup database, autentikasi multi-role, layout dashboard, master produk/ukuran, dan modul pengukuran dasar.
- **Fase 2 (Minggu 2) — Supply Chain & Kasir Hybrid:** Modul PO Vendor otomatis dari defisit stok, koreksi/tukar ukuran, master harga, dan penjualan langsung.
- **Fase 3 (Minggu 3) — Integrasi API, Audit & Finalisasi:** Integrasi WhatsApp API (Fonnte) untuk status PO/Ready/Diambil, nota publik, laporan matriks real-time dengan slider, dan ekspor Excel rekapitulasi.

## Tech Stack
- **Backend:** PHP 8.x (Native), MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (jQuery), Phosphor Icons
- **Integrasi:** Fonnte WhatsApp Gateway (cURL), JsBarcode
