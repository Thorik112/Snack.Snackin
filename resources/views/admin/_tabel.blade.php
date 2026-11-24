<div class="orows">
@foreach($pesanan as $p)
  <div class="orow {{ in_array($p['status'], ['Siap diambil','Diantar']) ? 'rdy' : '' }}" data-status="{{ $p['status'] }}">
    <b data-l="Kode">{{ $p['kode'] }}</b>
    <b data-l="Pelanggan">{{ $p['nama'] }}</b>
    <b data-l="Pesanan">{{ $p['ringkas'] }}</b>
    <b data-l="Penerimaan">{{ $p['jenis'] }}</b>
    <b data-l="Total dan bayar">{{ rp($p['total']) }}</b>
    <span data-l="Status"><span class="chip {{ statusClass($p['status']) }}">{{ $p['status'] }}</span></span>
    <button class="btn sm {{ in_array($p['status'], ['Siap diambil','Diantar']) ? 'dark' : 'primary' }}" data-open="detail">{{ $p['aksi'] }}</button>
  </div>
@endforeach
</div>