<div class="overlay" id="formmenu">
  <form class="sheet" id="menuform" novalidate role="dialog" aria-label="Form menu">
    <div class="top"><h2 id="mf-title">Tambah Menu</h2><button type="button" class="btn sm" data-close aria-label="Tutup">{!! icon('x') !!}</button></div>
    <input type="hidden" id="mf-id">
    <div class="field"><label for="mf-nama">Nama menu</label><input id="mf-nama" class="input" placeholder="Contoh: Sosis Bakar"><span class="errmsg" data-err="nama">Nama menu wajib diisi</span></div>
    <div class="field"><label for="mf-kat">Kategori</label><select id="mf-kat" class="select"><option>Bakaran</option><option>Cilok</option><option>Camilan</option><option>Nasi Bakar</option><option>Minuman</option></select></div>
    <div class="field"><label for="mf-harga">Harga (Rp)</label><input id="mf-harga" class="input" inputmode="numeric" placeholder="10000"><span class="errmsg" data-err="harga">Harga harus berupa angka lebih dari 0</span></div>
    <div class="field"><label for="mf-emoji">Ikon (emoji)</label><input id="mf-emoji" class="input" maxlength="4" placeholder="🍢"></div>
    <div class="field"><label for="mf-desk">Deskripsi singkat (opsional)</label><input id="mf-desk" class="input" maxlength="200" placeholder="Contoh: Dibakar dengan saus manis pedas"></div>
    <div class="field"><label for="mf-foto">Foto (opsional)</label><input id="mf-foto" class="input" type="file" accept="image/png,image/jpeg" style="padding-top:10px"><span class="hint">JPG atau PNG, maksimal 2 MB. Foto otomatis diperkecil sebelum diunggah.</span><div class="fotoprev"><img id="mf-prev" alt="Pratinjau foto menu" hidden><button type="button" class="btn sm" id="mf-fotodel" hidden>Hapus foto</button></div></div>
    <div class="switchrow"><span>Tersedia untuk dipesan</span><label class="sw"><input type="checkbox" id="mf-stok" checked><span></span></label></div>
    <div class="actions mt2"><button type="button" class="btn" data-close>Batal</button><button type="submit" class="btn primary">Simpan</button></div>
  </form>
</div>
<div class="overlay center" id="hapusmenu">
  <div class="sheet dialog"><h2>Hapus menu ini?</h2><p class="muted mt" id="hm-text">Menu tidak akan tampil lagi bagi pelanggan. Riwayat pesanan tetap tersimpan.</p>
    <div class="actions mt2"><button class="btn" data-close>Batal</button><button class="btn primary" id="hm-ok">Hapus</button></div></div>
</div>
