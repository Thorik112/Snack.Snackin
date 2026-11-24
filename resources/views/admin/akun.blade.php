@extends('layouts.admin')
@section('title', 'Kelola Akun')
@section('page', 'akun')
@section('content')
<div class="head"><h1>Kelola Akun Pemilik</h1><button class="btn primary" type="button" id="akun-add">{!! icon('plus') !!} Tambah Akun</button></div>
<div class="card">
  <p class="muted" style="margin-bottom:12px">Hanya akun di sini yang dapat masuk. Tidak ada pendaftaran publik.</p>
  <div class="toolbar">
    <div class="search">{!! icon('search') !!}<input class="input" data-search-for="akun" placeholder="Cari nama atau email..." aria-label="Cari akun"></div>
    <label class="sel">{!! icon('filter') !!}<select data-filter-for="akun" data-key="aktif" aria-label="Filter status"><option value="">Semua Status</option><option value="1">Aktif</option><option value="0">Nonaktif</option></select></label>
  </div>
  <div class="list" style="--cols:1.1fr 1.8fr .9fr .9fr 1.2fr 230px">
    <div class="lhead"><span>Nama</span><span>Email</span><span>Peran</span><span>Status</span><span>Terakhir Masuk</span><span class="r">Aksi</span></div>
    <div class="list" data-list="akun" data-size="5" style="--cols:1.1fr 1.8fr .9fr .9fr 1.2fr 230px"></div>
  </div>
  <div class="pager" data-pager-for="akun"></div>
</div>
@endsection
@section('overlays')
<div class="overlay" id="formakun">
  <form class="sheet" id="akunform" novalidate role="dialog" aria-label="Form akun">
    <div class="top"><h2 id="af-title">Tambah Akun</h2><button type="button" class="btn sm" data-close aria-label="Tutup">{!! icon('x') !!}</button></div>
    <input type="hidden" id="af-id">
    <div class="field"><label for="af-nama">Nama</label><input id="af-nama" class="input"><span class="errmsg" data-err="nama">Nama wajib diisi</span></div>
    <div class="field"><label for="af-email">Email</label><input id="af-email" class="input" type="email"><span class="errmsg" data-err="email">Masukkan email yang valid dan belum dipakai</span></div>
    <div class="field"><label for="af-sandi" id="af-sandi-l">Kata sandi sementara</label><input id="af-sandi" class="input" type="password" autocomplete="new-password"><span class="errmsg" data-err="sandi">Minimal 8 karakter</span><span class="hint" id="af-hint">Minimal 8 karakter. Minta pemilik baru menggantinya setelah masuk.</span></div>
    <div class="switchrow"><span>Akun aktif</span><label class="sw"><input type="checkbox" id="af-aktif" checked><span></span></label></div>
    <div class="actions mt2"><button type="button" class="btn" data-close>Batal</button><button type="submit" class="btn primary">Simpan</button></div>
  </form>
</div>
<div class="overlay center" id="hapusakun">
  <div class="sheet dialog"><h2>Hapus akun ini?</h2><p class="muted mt" id="ha-text"></p>
    <div class="actions mt2"><button class="btn" data-close>Batal</button><button class="btn primary" id="ha-ok">Hapus</button></div></div>
</div>
@endsection
