<?php
include '../config/koneksi.php';
include '../config/helper.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$tahun_ajaran_aktif = "2025/2026";

// Ambil produk, varian, dan HARGA dari master
$q_produk = mysqli_query($conn, "
    SELECT 
        v.id_variant, 
        p.nama_produk, 
        v.ukuran, 
        v.stok, 
        p.jenjang, 
        IFNULL(pr.harga_jual, 0) as harga_jual
    FROM variants v 
    JOIN products p ON v.id_produk = p.id_produk 
    LEFT JOIN prices pr ON p.id_produk = pr.id_produk AND pr.tahun_ajaran = '$tahun_ajaran_aktif'
    WHERE p.is_active = 1
    ORDER BY p.jenjang ASC, p.nama_produk ASC
");

$produk_options = "";
while($d = mysqli_fetch_assoc($q_produk)) {
    $harga = $d['harga_jual'];
    $stok = $d['stok'];
    $label_stok = ($stok > 0) ? "Stok: $stok" : "HABIS";
    
    // Kita tambahkan atribut data-jenjang untuk filter di JavaScript
    $produk_options .= "<option value='{$d['id_variant']}' 
                                data-stok='{$stok}' 
                                data-harga='{$harga}'
                                data-jenjang='{$d['jenjang']}'>
                                [{$d['jenjang']}] {$d['nama_produk']} - {$d['ukuran']} ($label_stok)
                        </option>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir Penjualan - Kedai Nusaputera</title>
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .main-content { padding: 30px 40px; background: #f8fafc; }
        .pos-wrapper { display: grid; grid-template-columns: 320px 1fr; gap: 25px; align-items: start; }
        .pos-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .total-box { 
            background: var(--primary); color: white; padding: 15px 30px; 
            border-radius: 12px; text-align: right; min-width: 250px;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        }
        .total-box small { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
        .total-box h1 { margin: 0; font-size: 32px; font-weight: 800; }
        .table-pos { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table-pos th { text-align: left; padding: 12px 10px; font-size: 11px; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .table-pos td { padding: 12px 10px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .inp-qty { width: 100%; text-align: center; font-weight: 700; border-radius: 6px; padding: 8px; border: 1px solid #cbd5e1; }
        .inp-harga { background: #f1f5f9 !important; border: 1px solid #e2e8f0; text-align: center; color: #475569; font-weight: 600; }
        .inp-subtotal { border: none; background: transparent; text-align: right; font-family:Arial ;  font-weight: 800; color: var(--primary); font-size: 16px; width: 100%; }
        .btn-remove { background: #fff1f2; color: #e11d48; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .card-footer { background: #f8fafc; padding: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .btn-tambah-item { background: transparent; color: var(--primary); border: 1px dashed var(--primary); padding: 8px 15px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; }
        .btn-submit-pos { background: var(--primary); color: white; border: none; padding: 12px 40px; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; }
        .stok-tag { font-size: 10px; font-weight: 700; color: #ef4444; margin-top: 4px; display: block; }
        
        /* Animasi saat filter */
        .select-produk option { display: block; }
        .select-produk option.hidden { display: none; }
    </style>
</head>
<body>

    <?php include 'components/sidebar.php'; ?>

    <div class="main-content">
        <div class="pos-header">
            <div>
                <h2 style="margin:0; letter-spacing:-1px;">🛍️ Penjualan Langsung</h2>
                <p style="margin:2px 0 0; color:#64748b; font-size:13px;">Pilihan barang otomatis menyesuaikan Unit yang dipilih.</p>
            </div>
            <div class="total-box">
                <small>Total Bayar</small>
                <h1 id="displayGrandTotal">Rp 0</h1>
            </div>
        </div>

        <form action="proses_jual_langsung.php" method="POST">
            <div class="pos-wrapper">
                
                <div class="card" style="padding: 20px;">
                    <label class="label-sm">Nama Pembeli</label>
                    <input type="text" name="nama_pembeli" class="form-control" placeholder="Input Nama..." required style="margin-bottom:15px; font-weight:700; text-transform:uppercase;">

                    <label class="label-sm">Pilih Unit (Filter Barang)</label>
                    <select name="jenjang" id="filterJenjang" class="form-control" style="margin-bottom:20px; border: 2px solid var(--primary);">
                        <option value="SD">SD</option>
                        <option value="TK">TK</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="SMK">SMK</option>
                        <option value="UMUM">UMUM (Hanya Atribut)</option>
                    </select>

                    <a href="atur_harga.php" class="btn btn-outline" style="width:100%; font-size:11px; justify-content:center; padding:10px;">
                        <i class="ph ph-note-pencil"></i> Atur Master Harga
                    </a>
                </div>

                <div class="card" style="padding:0; overflow:hidden;">
                    <div style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0;">
                        <h4 style="margin:0; font-size:14px; color:#1e293b;">Daftar Item Belanja</h4>
                    </div>
                    
                    <table class="table-pos">
                        <thead>
                            <tr>
                                <th width="45%">Barang & Ukuran</th>
                                <th width="12%" style="text-align:center;">Qty</th>
                                <th width="18%" style="text-align:center;">Harga</th>
                                <th width="18%" style="text-align:right;">Subtotal</th>
                                <th width="7%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            <!-- JS Generated -->
                        </tbody>
                    </table>

                    <div style="padding: 15px 20px; background: #f8fafc;">
                        <button type="button" class="btn-tambah-item" onclick="addRow()">
                            <i class="ph ph-plus-circle"></i> Tambah Baris (F2)
                        </button>
                    </div>

                    <div class="card-footer">
                        <div style="color: #64748b; font-size: 12px; max-width: 300px;">
                            <i class="ph-fill ph-info"></i> Hanya menampilkan stok sesuai unit & umum.
                        </div>
                        <button type="submit" class="btn-submit-pos">
                            SELESAIKAN PENJUALAN
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script>
    let rowCount = 0;

    // Fungsi untuk memfilter dropdown berdasarkan jenjang yang dipilih
    function applyFilterToRow(rowId) {
        const selectedJenjang = $('#filterJenjang').val();
        const selectProduk = $(`#row_${rowId} .select-produk`);

        selectProduk.find('option').each(function() {
            const optJenjang = $(this).data('jenjang');
            if (!optJenjang) return; // Lewati yang "-- Pilih Barang --"

            // Logika: Tampilkan jika jenjang cocok ATAU jenjang produk adalah UMUM
            if (optJenjang === selectedJenjang || optJenjang === 'UMUM') {
                $(this).show().prop('disabled', false);
            } else {
                $(this).hide().prop('disabled', true);
            }
        });
    }

    function addRow() {
        rowCount++;
        let html = `
            <tr id="row_${rowCount}">
                <td>
                    <select name="items[${rowCount}][id_variant]" class="form-control select-produk" required style="font-size:13px;">
                        <option value="">-- Pilih Barang --</option>
                        <?php echo $produk_options; ?>
                    </select>
                    <div class="msg-stok"></div>
                </td>
                <td><input type="number" name="items[${rowCount}][qty]" value="1" min="1" class="inp-qty"></td>
                <td><input type="number" name="items[${rowCount}][harga]" class="form-control inp-harga" readonly placeholder="0"></td>
                <td><input type="text" class="inp-subtotal" value="Rp 0" readonly></td>
                <td align="right">
                    <button type="button" onclick="removeRow(${rowCount})" class="btn-remove"><i class="ph ph-trash"></i></button>
                </td>
            </tr>`;
        $('#itemRows').append(html);
        applyFilterToRow(rowCount); // Jalankan filter untuk baris baru
    }

    function removeRow(id) { if($('#itemRows tr').length > 1) { $(`#row_${id}`).remove(); calculateTotal(); } }

    function calculateTotal() {
        let grand = 0;
        $('#itemRows tr').each(function() {
            let q = $(this).find('.inp-qty').val() || 0;
            let h = $(this).find('.inp-harga').val() || 0;
            let sub = parseInt(q) * parseInt(h);
            $(this).find('.inp-subtotal').val("Rp " + sub.toLocaleString('id-ID'));
            grand += sub;
        });
        $('#displayGrandTotal').text("Rp " + grand.toLocaleString('id-ID'));
    }

    // Ketika filter Unit/Jenjang di kolom kiri diubah
    $('#filterJenjang').on('change', function() {
        const jenjang = $(this).val();
        $('.select-produk').each(function() {
            const currentVal = $(this).val();
            // Loop semua opsi di tiap baris
            $(this).find('option').each(function() {
                const optJenjang = $(this).data('jenjang');
                if (!optJenjang) return;

                if (optJenjang === jenjang || optJenjang === 'UMUM') {
                    $(this).show().prop('disabled', false);
                } else {
                    $(this).hide().prop('disabled', true);
                }
            });
            
            // Jika barang yang terpilih sekarang jadi tersembunyi, reset pilihannya
            if($(this).find('option:selected').css('display') === 'none') {
                $(this).val("");
                $(this).trigger('change');
            }
        });
    });

    $(document).on('change', '.select-produk', function() {
        let sel = $(this).find(':selected');
        let h = sel.data('harga') || 0;
        let s = sel.data('stok') || 0;
        let row = $(this).closest('tr');
        row.find('.inp-harga').val(h);
        row.find('.inp-qty').attr('max', s);
        row.find('.msg-stok').html(s <= 0 ? '<span class="stok-tag">STOK HABIS!</span>' : '');
        calculateTotal();
    });

    $(document).on('input', '.inp-qty', calculateTotal);
    $(document).ready(addRow);
    $(document).keydown(function(e) { if(e.which == 113) addRow(); });
    </script>
</body>
</html>