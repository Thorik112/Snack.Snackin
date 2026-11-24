<div class="overlay" id="detail">
  <aside class="sheet" role="dialog" aria-label="Detail pesanan">
    <div class="top"><h2 id="d-kode">Pesanan</h2><button class="btn sm" data-close aria-label="Tutup">{!! icon('x') !!}</button></div>
    <div id="d-chips"></div>
    <div class="mt" id="d-info"></div>
    <h3 class="mt2">Item</h3>
    <div id="d-items"></div>
    <div id="d-pay" class="paybox" hidden></div>
    <h3 class="mt2">Progres</h3>
    <ul class="timeline" id="d-timeline"></ul>
    <div class="actions mt2" id="d-actions"></div>
    <div class="actions mt"><button type="button" class="btn" id="d-edit" hidden>{!! icon('list') !!} Ubah pesanan</button><a class="btn" id="d-wa" target="_blank" rel="noopener" hidden>Kabari lewat WhatsApp</a><button type="button" class="btn" id="d-print">{!! icon('print') !!} Cetak struk</button><a class="btn" id="d-map" target="_blank" rel="noopener" hidden>{!! icon('pin') !!} Lihat lokasi di peta</a></div>
  </aside>
</div>
<div class="overlay center" id="batal">
  <div class="sheet dialog">
    <h2>Batalkan pesanan ini?</h2>
    <p class="muted mt">Pesanan akan ditandai Dibatalkan dan tidak masuk laporan pendapatan.</p>
    <div class="actions mt2"><button class="btn" data-close>Kembali</button><button class="btn primary" id="batal-ok">Ya, batalkan</button></div>
  </div>
</div>
<div class="overlay center" id="editorder">
  <div class="sheet dialog editdlg" role="dialog" aria-labelledby="e-title">
    <h2 id="e-title">Ubah pesanan</h2>
    <p class="muted mt" id="e-note">Harga menu yang sudah ada di pesanan tidak berubah. Menu baru memakai harga sekarang.</p>
    <div id="e-lines" class="elines"></div>
    <div class="field"><label for="e-add">Tambah menu</label><div style="display:flex;gap:8px"><select class="input" id="e-add"></select><button type="button" class="btn" id="e-addbtn">Tambah</button></div></div>
    <div class="field"><label for="e-cat">Catatan</label><input class="input" id="e-cat" maxlength="200"></div>
    <div id="e-sum" class="paybox"></div>
    <span class="errmsg" id="e-err" role="alert"></span>
    <div class="actions mt2"><button type="button" class="btn" data-close>Batal</button><button type="button" class="btn primary" id="e-save">Simpan perubahan</button></div>
  </div>
</div>
