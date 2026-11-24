/* Snack.Snackin — JavaScript frontend. Data menu, pesanan, akun, dan pengaturan berasal dari API Laravel (lihat routes/web.php).
   Keranjang, nama/WA yang diingat, dan daftar pesanan milik perangkat disimpan di localStorage peramban. */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const SEED = window.SNK || {};
  const rp = n => 'Rp ' + Math.round(+n || 0).toLocaleString('id-ID');
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const LS = {
    get(k, d) { try { const v = localStorage.getItem(k); return v == null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
    del(k) { try { localStorage.removeItem(k); } catch (e) {} }
  };
  const ICON = {
    sun: '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/></svg>',
    moon: '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z"/></svg>',
    left: '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>',
    check: '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>',
    right: '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>'
  };
  const AREA = document.body.dataset.area, PAGE = document.body.dataset.page;

  /* ---------- Koneksi ke server ---------- */
  const CSRF = ($('meta[name="csrf-token"]') || {}).content || '';
  const SKEW = (SEED.now ? SEED.now * 1000 - Date.now() : 0);          // selisih jam server dan peramban
  const nowSec = () => Math.floor((Date.now() + SKEW) / 1000);
  const Api = {
    async req(method, url, body) {
      const o = { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } };
      if (body instanceof FormData) o.body = body; else if (body !== undefined) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body); }
      let r; try { r = await fetch(url, o); } catch (e) { throw { status: 0, message: 'Tidak dapat terhubung ke server. Periksa internet Anda lalu coba lagi.', errors: {} }; }
      let j = null; try { j = await r.json(); } catch (e) { /* bukan JSON */ }
      if (r.ok) return j || {};
      if (r.status === 401 && AREA === 'admin') { location.replace('/admin/login'); }
      const errors = {}; Object.entries((j && j.errors) || {}).forEach(([k, v]) => { errors[k] = Array.isArray(v) ? v[0] : v; });
      const first = Object.values(errors)[0];
      let message = (j && j.message) || 'Terjadi kesalahan. Silakan coba lagi.';
      if (r.status === 419) message = 'Halaman sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
      if (r.status === 429) message = 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
      if (r.status === 422 && first && /given data|invalid/i.test(message)) message = first;
      throw { status: r.status, message, errors };
    },
    get: u => Api.req('GET', u), post: (u, b) => Api.req('POST', u, b === undefined ? {} : b), del: (u, b) => Api.req('DELETE', u, b)
  };
  const fail = (e, fallback) => toast((e && e.message) || fallback || 'Gagal. Coba lagi.', true);
  const Menu = { d: SEED.menu || [], get() { return this.d; }, set(v) { this.d = v; } };
  const Akun = { d: SEED.akun || [], get() { return this.d; }, set(v) { this.d = v; } };
  const Cfg = { d: SEED.settings || {}, get() { return this.d; }, set(v) { this.d = v; } };
  const Orders = {
    d: SEED.orders || [],
    all() { return this.d; },
    find(k) { return this.d.find(o => o.kode === k); },
    put(o) { const i = this.d.findIndex(x => x.kode === o.kode); if (i >= 0) this.d[i] = Object.assign({}, this.d[i], o); else this.d.unshift(o); return o; }
  };
  const Cart = {
    get: () => LS.get('snk_cart', []), set: v => LS.set('snk_cart', v), count: () => Cart.get().reduce((a, i) => a + i.qty, 0), sub: () => Cart.get().reduce((a, i) => a + i.qty * i.harga, 0),
    /** Samakan keranjang dengan menu terbaru: harga diperbarui, menu yang hilang atau habis dibuang. Mengembalikan nama yang dibuang. */
    sync() {
      const out = [], keep = [];
      Cart.get().forEach(i => { const m = Menu.get().find(x => x.id === i.id); if (!m || !m.stok) out.push(i.nama); else keep.push(Object.assign({}, i, { nama: m.nama, harga: m.harga })); });
      if (out.length || JSON.stringify(keep) !== JSON.stringify(Cart.get())) Cart.set(keep);
      return out;
    }
  };
  /** Pesanan milik perangkat ini: pasangan kode + token rahasia (token diberikan server saat pesanan dibuat). */
  const My = {
    get() { return LS.get('snk_my', []).filter(x => x && typeof x === 'object' && x.kode && x.token); },
    add(kode, token) { const a = this.get().filter(x => x.kode !== kode); a.unshift({ kode, token }); LS.set('snk_my', a.slice(0, 30)); },
    token(kode) { const x = this.get().find(y => y.kode === kode); return x ? x.token : ''; },
    async load() { const t = this.get().map(x => x.token); if (!t.length) { Orders.d = []; return; } const j = await Api.post('/api/orders/mine', { tokens: t }); Orders.d = j.orders; }
  };
  async function pullOrders() { try { Orders.d = (await Api.get('/admin/api/orders')).orders; } catch (e) { /* dicoba lagi pada putaran berikutnya */ } }
  async function pullState() { try { const j = await Api.get('/api/state'); Menu.set(j.menu); Cfg.set(j.settings); return true; } catch (e) { return false; } }
  /** Menanyakan menu + pengaturan ke server tiap 30 detik; cb dipanggil hanya jika ada yang berubah. */
  function pollState(cb, ms) {
    let sig = JSON.stringify([Menu.get(), Cfg.get()]);
    const tick = async () => { if (document.hidden) return; if (!await pullState()) return; const n = JSON.stringify([Menu.get(), Cfg.get()]); if (n !== sig) { sig = n; cb(); } };
    setInterval(tick, ms || 30000); document.addEventListener('visibilitychange', () => { if (!document.hidden) tick(); });
  }
  const Profile = { get: () => LS.get('snk_profile', {}), set: v => LS.set('snk_profile', v) };
  const waNum = () => { let d = String(Cfg.get().wa || '').replace(/\D/g, ''); if (d.startsWith('0')) d = '62' + d.slice(1); return d; };
  /* batas waktu bayar QRIS: hanya penanda, pesanan tidak dibatalkan otomatis */
  const payDue = o => { const b = +(Cfg.get().bayarbatas) || 0; return b && o.bayar === 'QRIS' && o.status === 'Belum dibayar' ? o.dibuat + b * 60 : 0; };
  const payLate = o => { const d = payDue(o); return !!d && nowSec() > d; };
  const dueHtml = o => { const d = payDue(o); if (!d) return ''; const s = d - nowSec(); return s > 0 ? `<p class="say due">Bayar sebelum pukul <b>${clockOf(d)}</b> (sisa ${Math.max(1, Math.ceil(s / 60))} menit).</p>` : `<p class="say bad due">Waktu bayar sudah lewat. Sudah membayar? Hubungi toko lewat WhatsApp.</p>`; };
  const waLink = text => `https://wa.me/${waNum()}${text ? '?text=' + encodeURIComponent(text) : ''}`;
  /* kirim kode ke WhatsApp milik pelanggan sendiri (nomor dari saat memesan), supaya tidak hilang */
  const selfWa = o => { const n = telpOf(Profile.get().telp); const t = 'Kode pesanan ' + o.kode + ' di ' + Cfg.get().toko + '. Simpan pesan ini untuk melihat status pesanan.'; return `https://wa.me/${n}?text=${encodeURIComponent(t)}`; };
  const telpOf = t => { let d = String(t || '').replace(/\D/g, ''); if (d.startsWith('0')) d = '62' + d.slice(1); return d.length >= 10 ? d : ''; };
  const clockOf = ts => new Date(ts * 1000).toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', hour12: false }).replace(':', '.');

  /* ---------- Peta (Leaflet + OpenStreetMap + pencarian Nominatim) ----------
     Pustaka Leaflet ada di /vendor/leaflet. Ubin peta dan pencarian butuh internet.
     Untuk produksi/trafik besar, ganti NOMINATIM dan ubin dengan penyedia sendiri (mis. MapTiler, LocationIQ) sesuai kebijakan pemakaian. */
  const MapKit = {
    NOMINATIM: 'https://nominatim.openstreetmap.org',
    ok: () => typeof window.L !== 'undefined',
    kitchen() { const c = Cfg.get(); return { lat: +c.lat || -7.9608, lng: +c.lng || 112.6173, alamat: c.alamat || '', toko: c.toko || 'Dapur Snack Snackin', radius: +c.radius || 0 }; },
    km(a, b) { const r = x => x * Math.PI / 180, R = 6371, dA = r(b.lat - a.lat), dO = r(b.lng - a.lng), h = Math.sin(dA / 2) ** 2 + Math.cos(r(a.lat)) * Math.cos(r(b.lat)) * Math.sin(dO / 2) ** 2; return 2 * R * Math.asin(Math.sqrt(h)); },
    gmaps: p => `https://www.google.com/maps/search/?api=1&query=${p.lat},${p.lng}`,
    route: p => `https://www.google.com/maps/dir/?api=1&destination=${p.lat},${p.lng}`,
    async get(path) { const c = new AbortController(), t = setTimeout(() => c.abort(), 9000); try { const r = await fetch(this.NOMINATIM + path, { signal: c.signal, headers: { Accept: 'application/json' } }); if (!r.ok) throw new Error(r.status); return await r.json(); } finally { clearTimeout(t); } },
    async search(q, near) {
      const v = near ? `&viewbox=${near.lng - .4},${near.lat + .4},${near.lng + .4},${near.lat - .4}` : '';
      const j = await this.get(`/search?format=jsonv2&limit=6&countrycodes=id&accept-language=id${v}&q=${encodeURIComponent(q)}`);
      return j.map(x => ({ lat: +x.lat, lng: +x.lon, nama: x.display_name }));
    },
    async reverse(p) { try { const j = await this.get(`/reverse?format=jsonv2&zoom=18&accept-language=id&lat=${p.lat}&lon=${p.lng}`); return j && j.display_name || ''; } catch (e) { return ''; } },
    icon: {
      k: () => L.divIcon({ className: '', html: '<span class="pin pk">🍢</span>', iconSize: [44, 44], iconAnchor: [22, 22] }),
      u: () => L.divIcon({ className: '', html: '<span class="pin pu"></span>', iconSize: [36, 46], iconAnchor: [18, 42] })
    },
    create(el, c, zoom) {
      const m = L.map(el, { scrollWheelZoom: false }).setView([c.lat, c.lng], zoom || 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(m);
      return m;
    },
    /* kotak pencarian: input + tombol + daftar hasil; onPick({lat,lng,nama}) */
    bindSearch(o) {
      const note = (t, bad) => { o.note.textContent = t || ''; o.note.classList.toggle('bad', !!bad); o.note.hidden = !t; };
      const run = async () => {
        const v = o.q.value.trim(); o.res.hidden = true; o.res.innerHTML = '';
        if (v.length < 3) { note('Ketik minimal 3 huruf, misalnya nama jalan atau gedung.', true); return; }
        note('Mencari…'); o.go.disabled = true;
        try {
          const r = await this.search(v, o.near && o.near());
          if (!r.length) { note('Tidak ditemukan. Coba kata kunci lain, atau ketuk langsung di peta.', true); return; }
          note(r.length + ' hasil. Pilih salah satu:'); o.res.hidden = false;
          o.res.innerHTML = r.map((x, i) => `<li><button type="button" data-i="${i}">${esc(x.nama)}</button></li>`).join('');
          o.res._r = r;
        } catch (e) { note('Pencarian tidak dapat dihubungi. Periksa internet Anda, atau ketuk langsung di peta.', true); }
        finally { o.go.disabled = false; }
      };
      o.go.addEventListener('click', run);
      o.q.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); run(); } });
      o.res.addEventListener('click', e => { const b = e.target.closest('[data-i]'); if (!b) return; const x = o.res._r[+b.dataset.i]; o.res.hidden = true; note('Lokasi dipilih.'); o.onPick(x); });
    }
  };
  const statusCls = s => ({ 'Belum dibayar': 'warn', 'Diterima': 'info', 'Diproses': 'violet', 'Siap diambil': 'ok', 'Diantar': 'ok', 'Selesai': 'ok', 'Dibatalkan': 'bad' }[s] || 'info');
  const readyOf = o => o.jenis === 'Antar' ? 'Diantar' : 'Siap diambil';
  const itemCount = o => o.items.reduce((a, i) => a + i.qty, 0);

  /* ---------- UI umum ---------- */
  let toastT;
  function toast(msg, bad) { const t = $('#toast'); if (!t) return; t.textContent = msg; t.className = 'toast show' + (bad ? ' bad' : ''); clearTimeout(toastT); toastT = setTimeout(() => t.classList.remove('show'), 2400); }
  const openO = id => { const o = $('#' + id); if (o) o.classList.add('open'); };
  const closeO = el => { const o = el && el.closest ? el.closest('.overlay') : el; if (o) o.classList.remove('open'); };
  document.addEventListener('click', e => {
    const op = e.target.closest('[data-open]'); if (op) { e.preventDefault(); openO(op.dataset.open); }
    const cl = e.target.closest('[data-close]'); if (cl) closeO(cl);
    if (e.target.classList && e.target.classList.contains('overlay')) closeO(e.target);
    const dd = $('#ddm'); if (dd && !e.target.closest('#userdd')) { dd.hidden = true; $('#ddbtn') && $('#ddbtn').setAttribute('aria-expanded', 'false'); }
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { $$('.overlay.open').forEach(o => o.classList.remove('open')); const dd = $('#ddm'); if (dd) dd.hidden = true; } });
  const setErr = (form, key, on) => { const m = $(`[data-err="${key}"]`, form); if (m) m.classList.toggle('on', !!on); };
  const mark = (inp, bad) => { if (inp) inp.classList.toggle('err', !!bad); };

  // tema terang / gelap
  function applyTheme(t) {
    if (t) document.documentElement.setAttribute('data-theme', t); else document.documentElement.removeAttribute('data-theme');
    const light = document.documentElement.getAttribute('data-theme') === 'light';
    $$('[data-theme-label]').forEach(x => x.textContent = light ? 'Mode gelap' : 'Mode terang');
    $$('[data-theme-icon]').forEach(x => x.innerHTML = light ? ICON.moon : ICON.sun);
  }
  if (AREA !== 'user') applyTheme(LS.get('snk_theme', null));
  document.addEventListener('click', e => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    LS.set('snk_theme', next); applyTheme(next);
  });

  // dropdown pengguna + sidebar mobile
  const ddb = $('#ddbtn');
  if (ddb) ddb.addEventListener('click', () => { const m = $('#ddm'); m.hidden = !m.hidden; ddb.setAttribute('aria-expanded', String(!m.hidden)); });
  const bg = $('#burger'), side = $('#side'), shell = $('#shell'), scrim = $('#scrim');
  if (bg && side) {
    const mob = () => matchMedia('(max-width:900px)').matches;
    const isOpen = () => mob() ? side.classList.contains('open') : !shell.classList.contains('collapsed');
    const setSide = (open, keep) => {
      if (mob()) { side.classList.toggle('open', open); document.body.classList.toggle('side-open', open); scrim.hidden = !open; }
      else { shell.classList.toggle('collapsed', !open); if (keep) LS.set('snk_side', open ? 1 : 0); }
      bg.setAttribute('aria-expanded', String(open)); bg.setAttribute('aria-label', open ? 'Tutup menu samping' : 'Buka menu samping');
    };
    if (!mob() && LS.get('snk_side', 1) === 0) setSide(false);
    else bg.setAttribute('aria-expanded', String(isOpen())), bg.setAttribute('aria-label', isOpen() ? 'Tutup menu samping' : 'Buka menu samping');
    bg.addEventListener('click', () => setSide(!isOpen(), true));
    $('#sclose').addEventListener('click', () => { setSide(false, true); bg.focus(); });
    scrim.addEventListener('click', () => setSide(false));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && mob() && isOpen()) { setSide(false); bg.focus(); } });
    $$('#side .nav a').forEach(a => a.addEventListener('click', () => { if (mob()) setSide(false); }));
    matchMedia('(max-width:900px)').addEventListener('change', () => { side.classList.remove('open'); document.body.classList.remove('side-open'); scrim.hidden = true; shell.classList.toggle('collapsed', !mob() && LS.get('snk_side', 1) === 0); bg.setAttribute('aria-expanded', String(isOpen())); });
  }
  $$('[data-logout]').forEach(a => a.addEventListener('click', async e => { e.preventDefault(); try { await Api.post('/admin/api/logout'); } catch (x) { /* tetap keluar */ } location.href = '/admin/login'; }));

  // halaman admin hanya untuk Pemilik yang sudah masuk (server juga memeriksa setiap permintaan)
  if (AREA === 'admin') {
    if (!SEED.me) { location.replace('/admin/login'); return; }
    const n = $('#whoname'); if (n) n.textContent = SEED.me.nama;
    const v = $('#whoava'); if (v) v.textContent = (SEED.me.nama || 'P')[0].toUpperCase();
  }

  /* ---------- Daftar: filter + pencarian + pagination ---------- */
  const Lists = {};
  function pageBtns(cur, pages) {
    const out = []; const add = (p, l) => out.push(`<button type="button" data-p="${p}" class="${p === cur ? 'on' : ''}" ${p === cur ? 'aria-current="page"' : ''}>${l || p}</button>`);
    if (pages <= 7) { for (let i = 1; i <= pages; i++) add(i); }
    else { add(1); if (cur > 3) out.push('<button type="button" disabled>…</button>'); for (let i = Math.max(2, cur - 1); i <= Math.min(pages - 1, cur + 1); i++) add(i); if (cur < pages - 2) out.push('<button type="button" disabled>…</button>'); add(pages); }
    return out.join('');
  }
  function renderPager(c, pages, shown, total) {
    const p = c.pager, st = c.state; if (!p) return;
    p.innerHTML = `<span></span><div class="pg"><button type="button" data-p="${st.page - 1}" ${st.page <= 1 ? 'disabled' : ''} aria-label="Sebelumnya">${ICON.left}</button>${pageBtns(st.page, pages)}<button type="button" data-p="${st.page + 1}" ${st.page >= pages ? 'disabled' : ''} aria-label="Berikutnya">${ICON.right}</button></div><b class="info">Menampilkan ${shown} dari ${total} hasil</b>`;
  }
  function renderList(name) {
    const c = Lists[name]; if (!c || !c.el) return; const st = c.state;
    const all = c.data(st); const total = all.length, pages = Math.max(1, Math.ceil(total / st.size));
    st.page = Math.min(Math.max(1, st.page), pages);
    const slice = all.slice((st.page - 1) * st.size, st.page * st.size);
    c.el.innerHTML = slice.length ? slice.map(c.row).join('') : `<div class="empty">${(typeof c.empty === 'function' ? c.empty(st) : c.empty) || 'Tidak ada data yang cocok.'}</div>`;
    renderPager(c, pages, slice.length, total); if (c.after) c.after(all);
  }
  const renderAll = () => { Object.keys(Lists).forEach(renderList); refreshStats(); };
  function initList(name) {
    const c = Lists[name], el = $(`[data-list="${name}"]`); if (!c || !el) return;
    c.el = el; c.pager = $(`[data-pager-for="${name}"]`); c.state = { page: 1, size: +el.dataset.size || 10, q: '', f: {} };
    $$(`[data-filter-for="${name}"]`).forEach(s => { c.state.f[s.dataset.key] = s.value; s.addEventListener('change', () => { c.state.f[s.dataset.key] = s.value; c.state.page = 1; renderList(name); }); });
    const q = $(`[data-search-for="${name}"]`); if (q) q.addEventListener('input', () => { c.state.q = q.value.trim().toLowerCase(); c.state.page = 1; renderList(name); });
    if (c.pager) c.pager.addEventListener('click', e => { const b = e.target.closest('button[data-p]'); if (!b || b.disabled) return; c.state.page = +b.dataset.p; renderList(name); });
  }

  /* ---------- Pesanan: baris, panel detail, perubahan status ---------- */
  const NEED = ['Belum dibayar', 'Diterima'];
  const waitText = o => { const m = Math.max(0, Math.floor((nowSec() - o.dibuat) / 60)); return m < 1 ? 'baru saja' : m < 60 ? m + ' mnt' : Math.floor(m / 60) + ' jam ' + (m % 60) + ' mnt'; };
  const orderRow = o => {
    const rd = o.status === 'Siap diambil' || o.status === 'Diantar', need = NEED.includes(o.status);
    return `<div class="lrow ${rd ? 'rdy' : ''} ${need ? 'need' : ''}"><b data-l="Kode">${esc(o.kode)}${need ? `<span class="sub wait ${payLate(o) ? 'late' : ''}" title="Menunggu tindakan">${waitText(o)}${payLate(o) ? ' · lewat batas bayar' : ''}</span>` : ''}</b><b data-l="Pelanggan">${esc(o.nama)}</b><b data-l="Pesanan">${itemCount(o)} item</b><b data-l="Penerimaan">${o.jenis}</b><b data-l="Total Dan Bayar">${rp(o.total)}<span class="sub">${o.bayar}</span></b><span data-l="Status"><span class="chip ${statusCls(o.status)}">${o.status}</span></span><button type="button" class="btn ${rd ? 'dark' : 'primary'}" data-order="${esc(o.kode)}">${need ? (o.status === 'Belum dibayar' ? 'Cek bayar' : 'Proses') : 'Aksi'}</button></div>`;
  };
  const matchQ = (o, q) => !q || (o.kode + ' ' + o.nama).toLowerCase().includes(q);
  /** Yang perlu tindakan Pemilik tampil paling atas (yang menunggu paling lama dulu); sisanya terbaru dulu. */
  const urgentSort = (a, b) => { const x = NEED.includes(a.status), y = NEED.includes(b.status); return x !== y ? (x ? -1 : 1) : x ? a.dibuat - b.dibuat : b.dibuat - a.dibuat || (a.kode < b.kode ? 1 : -1); };
  Lists.orders = {
    data: st => Orders.all().filter(o => (!st.f.status || o.status === st.f.status) && (!st.f.jenis || o.jenis === st.f.jenis) && (st.f.range === undefined || st.f.range === '' || o.hari <= +st.f.range) && matchQ(o, st.q)).sort(urgentSort),
    row: orderRow, empty: 'Belum ada pesanan yang cocok dengan filter ini.'
  };
  Lists.queue = {
    data: st => Orders.all().filter(o => !['Selesai', 'Dibatalkan'].includes(o.status) && (!st.f.status || o.status === st.f.status)).sort(urgentSort),
    row: orderRow, empty: 'Tidak ada pesanan yang perlu diproses. 🎉'
  };
  function actionsFor(o) {
    switch (o.status) {
      case 'Belum dibayar': return [{ l: 'Tandai lunas', to: 'Diterima' }, { l: 'Batalkan', cancel: 1 }];
      case 'Diterima': return [{ l: 'Proses', to: 'Diproses' }, { l: 'Batalkan', cancel: 1 }];
      case 'Diproses': return [{ l: o.jenis === 'Antar' ? 'Antar pesanan' : 'Siap diambil', to: readyOf(o) }, { l: 'Batalkan', cancel: 1 }];
      case 'Siap diambil': case 'Diantar': return [{ l: 'Selesaikan', to: 'Selesai' }];
      default: return [];
    }
  }
  const payText = o => o.status === 'Dibatalkan' ? '-' : o.bayar === 'QRIS' ? (o.status === 'Belum dibayar' ? 'Belum dibayar' : 'Lunas (QRIS)') : (o.status === 'Selesai' ? 'Lunas (tunai)' : 'Tunai saat diterima');
  /* ---------- Cetak struk (printer kasir 58/80 mm atau printer biasa) ---------- */
  const strukHTML = o => {
    const c = Cfg.get(), r = (a, b, cls) => `<div class="sr ${cls || ''}"><span>${a}</span><span>${b}</span></div>`, pt = payText(o), stt = o.status === 'Dibatalkan' ? 'DIBATALKAN' : /^Lunas/.test(pt) ? 'LUNAS' : pt === 'Belum dibayar' ? 'BELUM DIBAYAR' : 'BAYAR SAAT TERIMA';
    const items = o.items.map(i => `<div class="si">${esc(i.nama)}</div>` + r(`${i.qty} x ${rp(i.harga).replace('Rp ', '')}`, rp(i.qty * i.harga).replace('Rp ', ''))).join('');
    const sub = o.items.reduce((a, i) => a + i.qty * i.harga, 0);
    return `<div class="s-c s-t">${esc(c.toko || 'Snack.Snackin')}</div>` + (c.alamat ? `<div class="s-c">${esc(c.alamat)}</div>` : '') + `<div class="s-c">WA ${esc(c.wa || '')}</div><hr>` +
      r('No', esc(o.kode)) + r('Tanggal', `${esc(o.tgl)} ${esc(o.jam)}`) + r('Pelanggan', esc(o.nama)) + (o.telp && o.telp !== '-' ? r('Telp', esc(o.telp)) : '') + r('Terima', o.jenis === 'Antar' ? 'Diantar' : 'Ambil di toko') +
      (o.jenis === 'Antar' && o.alamat ? `<div class="s-a">${esc(o.alamat)}</div>` : '') + '<hr>' + items + '<hr>' +
      r('Subtotal', rp(sub).replace('Rp ', '')) + (o.ongkir ? r('Ongkir', rp(o.ongkir).replace('Rp ', '')) : '') + r('TOTAL', rp(o.total), 's-b') +
      r('Bayar', esc(o.bayar)) + (o.uang ? r('Diterima', rp(o.uang)) + r('Kembali', rp(Math.max(0, o.uang - o.total))) : '') + r('Status', esc(stt), 's-b') +
      (o.catatan ? `<div class="s-a">Catatan: ${esc(o.catatan)}</div>` : '') + `<hr><div class="s-c">${esc(c.footer || 'Terima kasih!')}</div><div class="s-c s-s">Cek status pesanan di menu Transaksi dengan kode ${esc(o.kode)}</div>`;
  };
  function printStruk(o) {
    if (!o) return; const w = String(Cfg.get().kertas) === '58' ? 58 : 80;
    let box = $('#struk'); if (!box) { box = document.createElement('div'); box.id = 'struk'; box.setAttribute('aria-hidden', 'true'); document.body.appendChild(box); }
    let st = $('#struk-page'); if (!st) { st = document.createElement('style'); st.id = 'struk-page'; document.head.appendChild(st); }
    st.textContent = `@page{size:${w}mm auto;margin:2mm}@media print{#struk{width:${w - 6}mm}}`;
    box.innerHTML = strukHTML(o); document.body.classList.add('printing');
    const done = () => { document.body.classList.remove('printing'); window.removeEventListener('afterprint', done); };
    window.addEventListener('afterprint', done); setTimeout(() => window.print(), 60);
  }
  window.snkPrint = printStruk;
  let curOrder = null;
  function timeline(o) {
    const steps = ['Diterima', 'Diproses', readyOf(o), 'Selesai'];
    const rank = { 'Belum dibayar': -1, 'Diterima': 0, 'Diproses': 1, 'Siap diambil': 2, 'Diantar': 2, 'Selesai': 3, 'Dibatalkan': -2 }[o.status];
    if (o.status === 'Dibatalkan') return '<li>Pesanan dibatalkan</li>';
    return steps.map((s, i) => `<li class="${i <= rank ? 'done' : ''}"><i></i>${s}</li>`).join('');
  }
  const WAS = { 'Belum dibayar': 'menunggu pembayaran QRIS', 'Diterima': 'sudah kami terima', 'Diproses': 'sedang kami buat', 'Siap diambil': 'sudah siap diambil', 'Diantar': 'sedang diantar', 'Selesai': 'sudah selesai. Terima kasih!', 'Dibatalkan': 'dibatalkan' };
  const telpWa = t => { let d = String(t || '').replace(/\D/g, ''); if (d.startsWith('0')) d = '62' + d.slice(1); return d.length >= 10 ? d : ''; };
  /** Rincian pembayaran, tombol ubah, dan tombol kabari pelanggan lewat WhatsApp. */
  function paintPay(o) {
    const box = $('#d-pay'), sel = o.selisih || 0, c = Cfg.get();
    const rows = [];
    if (o.diubah) rows.push('<div class="kv"><span class="muted">Catatan sistem</span><b>Pesanan sudah diubah Pemilik</b></div>');
    if (o.bayar === 'QRIS' && o.dibayar > 0) rows.push(`<div class="kv"><span class="muted">Sudah dibayar (QRIS)</span><b>${rp(o.dibayar)}</b></div>`);
    if (sel) rows.push(`<div class="kv warnrow"><span><b>${sel > 0 ? 'Kurang bayar' : 'Lebih bayar'}</b></span><b>${rp(Math.abs(sel))}</b></div><p class="hint">${sel > 0 ? 'Tagih kekurangannya ke pelanggan (tunai atau QRIS) saat pesanan diserahkan.' : 'Kembalikan kelebihannya ke pelanggan (tunai atau transfer).'} Setelah beres, tekan tombol di bawah.</p><button type="button" class="btn primary" id="d-settle">Selisih sudah beres</button>`);
    box.hidden = !rows.length; box.innerHTML = rows.join('');
    const st = $('#d-settle'); if (st) st.addEventListener('click', async () => { st.disabled = true; try { Orders.put((await Api.post('/admin/api/orders/' + encodeURIComponent(o.kode) + '/selisih')).order); toast('Selisih ditandai beres'); renderAll(); openOrder(o.kode); } catch (e) { fail(e); st.disabled = false; } });
    const ed = $('#d-edit'); ed.hidden = !['Belum dibayar', 'Diterima', 'Diproses'].includes(o.status);
    const wa = $('#d-wa'), n = telpWa(o.telp); wa.hidden = !n;
    if (n) {
      let t = `Halo ${o.nama}, pesanan ${o.kode} di ${c.toko || 'Snack.Snackin'} ${WAS[o.status] || ''}. Total ${rp(o.total)}.`;
      if (o.diubah) t += ' Isi pesanan sudah kami ubah.';
      if (sel > 0) t += ` Ada kekurangan pembayaran ${rp(sel)}.`; else if (sel < 0) t += ` Ada kelebihan pembayaran ${rp(-sel)} yang akan kami kembalikan.`;
      wa.href = `https://wa.me/${n}?text=${encodeURIComponent(t)}`;
    }
  }
  /* ---------- Ubah isi pesanan ---------- */
  const E = { kode: '', lines: [], locked: [], ongkir: 0, bayar: '', dibayar: 0 };
  const eSum = () => E.lines.reduce((a, l) => a + l.qty * l.harga, 0) + E.locked.reduce((a, l) => a + l.qty * l.harga, 0) + E.ongkir;
  function eDraw() {
    $('#e-lines').innerHTML = E.lines.map((l, i) => `<div class="eline"><div><b>${esc(l.nama)}</b><span class="muted"> · ${rp(l.harga)}</span></div><div class="qty"><button type="button" data-eq="${i}" data-d="-1" aria-label="Kurangi ${esc(l.nama)}">−</button><b>${l.qty}</b><button type="button" data-eq="${i}" data-d="1" aria-label="Tambah ${esc(l.nama)}">+</button></div><button type="button" class="btn sm" data-erm="${i}" aria-label="Hapus ${esc(l.nama)}"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>`).join('') +
      E.locked.map(l => `<div class="eline"><div><b>${esc(l.nama)}</b><span class="muted"> · ${l.qty}× ${rp(l.harga)} (menu sudah dihapus, tidak bisa diubah)</span></div></div>`).join('');
    const used = new Set(E.lines.map(l => l.id)); $('#e-add').innerHTML = Menu.get().filter(m => m.stok && !used.has(m.id)).map(m => `<option value="${m.id}">${esc(m.nama)} · ${rp(m.harga)}</option>`).join('') || '<option value="">Semua menu sudah ada</option>';
    const t = eSum(), d = E.bayar === 'QRIS' && E.dibayar > 0 ? t - E.dibayar : 0;
    $('#e-sum').innerHTML = `<div class="kv"><b>Total baru</b><b>${rp(t)}</b></div>` + (E.ongkir ? `<div class="kv"><span class="muted">Sudah termasuk ongkir</span><span>${rp(E.ongkir)}</span></div>` : '') +
      (d ? `<div class="kv warnrow"><b>${d > 0 ? 'Pelanggan kurang bayar' : 'Pelanggan lebih bayar'}</b><b>${rp(Math.abs(d))}</b></div><p class="hint">${d > 0 ? 'Tagih kekurangannya.' : 'Kembalikan kelebihannya.'} Pesanan baru bisa diselesaikan setelah selisih ditandai beres.</p>` : E.bayar === 'QRIS' && E.dibayar === 0 ? '<p class="hint">Pelanggan belum membayar, jadi ia cukup membayar total baru.</p>' : '');
  }
  const ed0 = $('#d-edit');
  if (ed0) {
    ed0.addEventListener('click', () => {
      const o = Orders.find(curOrder); if (!o) return;
      Object.assign(E, { kode: o.kode, ongkir: o.ongkir, bayar: o.bayar, dibayar: o.dibayar || 0, lines: o.items.filter(i => i.id != null).map(i => ({ id: i.id, nama: i.nama, harga: i.harga, qty: i.qty })), locked: o.items.filter(i => i.id == null) });
      $('#e-cat').value = o.catatan || ''; $('#e-err').classList.remove('on'); eDraw(); openO('editorder');
    });
    document.addEventListener('click', e => {
      const q = e.target.closest('[data-eq]'); if (q) { const l = E.lines[+q.dataset.eq]; l.qty = Math.min(99, Math.max(1, l.qty + +q.dataset.d)); eDraw(); }
      const r = e.target.closest('[data-erm]'); if (r) { E.lines.splice(+r.dataset.erm, 1); eDraw(); }
    });
    $('#e-addbtn').addEventListener('click', () => { const id = +$('#e-add').value, m = Menu.get().find(x => x.id === id); if (!m) return; E.lines.push({ id: m.id, nama: m.nama, harga: m.harga, qty: 1 }); eDraw(); });
    $('#e-save').addEventListener('click', async () => {
      const er = $('#e-err'), b = $('#e-save'); er.classList.remove('on');
      if (!E.lines.length && !E.locked.length) { er.textContent = 'Pesanan harus berisi minimal satu menu. Untuk membatalkan, pakai tombol Batalkan.'; er.classList.add('on'); return; }
      b.disabled = true;
      try { Orders.put((await Api.post('/admin/api/orders/' + encodeURIComponent(E.kode), { items: E.lines.map(l => ({ id: l.id, qty: l.qty })), catatan: $('#e-cat').value.trim() })).order); closeO($('#editorder')); toast('Pesanan diubah'); renderAll(); openOrder(E.kode); }
      catch (x) { er.textContent = x.message; er.classList.add('on'); if (x.status === 409) { await pullOrders(); renderAll(); } }
      finally { b.disabled = false; }
    });
  }
  function openOrder(kode) {
    const o = Orders.find(kode); if (!o) return; curOrder = kode;
    $('#d-kode').textContent = o.kode;
    $('#d-chips').innerHTML = `<span class="chip ${statusCls(o.status)}">${o.status}</span> <span class="chip plain">${o.bayar}</span> <span class="chip plain">${o.jenis}</span>`;
    $('#d-info').innerHTML = `<div class="kv"><span class="muted">Pelanggan</span><b>${esc(o.nama)} · ${esc(o.telp)}</b></div>` +
      (o.jenis === 'Antar' ? `<div class="kv"><span class="muted">Alamat</span><b>${esc(o.alamat)}</b></div>` : '') +
      `<div class="kv"><span class="muted">Catatan</span><b>${esc(o.catatan || '-')}</b></div><div class="kv"><span class="muted">Waktu</span><b>${esc(o.tgl)} · ${esc(o.jam)}</b></div><div class="kv"><span class="muted">Pembayaran</span><b>${payText(o)}</b></div>`;
    $('#d-items').innerHTML = o.items.map(i => `<div class="kv"><span>${i.qty}× ${esc(i.nama)}</span><b>${rp(i.qty * i.harga)}</b></div>`).join('') +
      (o.ongkir ? `<div class="kv"><span class="muted">Ongkir</span><b>${rp(o.ongkir)}</b></div>` : '') + `<div class="kv"><b>Total</b><b>${rp(o.total)}</b></div>`;
    $('#d-timeline').innerHTML = timeline(o);
    paintPay(o);
    const acts = actionsFor(o);
    $('#d-actions').innerHTML = acts.length ? acts.map((a, i) => `<button type="button" class="btn ${a.cancel ? 'danger' : 'primary'}" data-act="${i}">${a.l}</button>`).join('') : '<span class="muted">Pesanan ini sudah ditutup.</span>';
    $$('#d-actions [data-act]').forEach(b => b.addEventListener('click', async () => {
      const a = acts[+b.dataset.act];
      if (a.cancel) { openO('batal'); return; }
      b.disabled = true;
      try { Orders.put((await Api.post('/admin/api/orders/' + encodeURIComponent(o.kode) + '/status', { status: a.to })).order); toast(`${o.kode}: ${a.to}`); renderAll(); openOrder(o.kode); }
      catch (e) { fail(e); b.disabled = false; if (e.status === 409) { await pullOrders(); renderAll(); openOrder(o.kode); } }
    }));
    const dm = $('#d-map'); if (dm) { dm.hidden = !(o.jenis === 'Antar' && o.lat); if (o.lat) dm.href = MapKit.gmaps(o); }
    openO('detail');
  }
  const dpr = $('#d-print'); if (dpr) dpr.addEventListener('click', () => printStruk(Orders.find(curOrder)));
  document.addEventListener('click', e => { const b = e.target.closest('[data-order]'); if (b) openOrder(b.dataset.order); });
  const bok = $('#batal-ok');
  if (bok) bok.addEventListener('click', async () => {
    bok.disabled = true;
    try { Orders.put((await Api.post('/admin/api/orders/' + encodeURIComponent(curOrder) + '/status', { status: 'Dibatalkan' })).order); closeO($('#batal')); toast(curOrder + ' dibatalkan'); renderAll(); openOrder(curOrder); }
    catch (e) { closeO($('#batal')); fail(e); await pullOrders(); renderAll(); openOrder(curOrder); }
    finally { bok.disabled = false; }
  });

  /* ---------- Data & grafik (semua dihitung dari data pesanan) ---------- */
  const NS = 'http://www.w3.org/2000/svg';
  const reduce = () => window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  const MON = { Jan: 0, Feb: 1, Mar: 2, Apr: 3, Mei: 4, Jun: 5, Jul: 6, Agu: 7, Sep: 8, Okt: 9, Nov: 10, Des: 11 }, HR = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
  const short = t => t.split(' ').slice(0, 2).join(' ');
  const wday = t => { const p = t.split(' '); return HR[new Date(+p[2], MON[p[1]], +p[0]).getDay()]; };
  const num = n => Math.round(n).toLocaleString('id-ID');
  const fmtAx = v => v >= 1e6 ? (v / 1e6).toFixed(v % 1e6 ? 1 : 0).replace('.', ',') + ' jt' : v >= 1000 ? Math.round(v / 1000) + ' rb' : String(Math.round(v));
  function niceStep(max, parts, int) {
    const raw = Math.max(max, 1) / parts, mag = Math.pow(10, Math.floor(Math.log10(raw)));
    return (int ? [1, 2, 5, 10] : [1, 2, 2.5, 5, 10]).map(m => Math.max(int ? 1 : 0, m * mag)).find(v => v >= raw);
  }
  /** Ringkasan per hari (hari 0 = hari ini) dari semua pesanan; pesanan Dibatalkan tidak dihitung sebagai penjualan. */
  function daily() {
    const days = [];
    for (let h = 0; h < 30; h++) days[h] = { hari: h, tgl: '', cnt: 0, rev: 0, ambil: 0, antar: 0, ongkir: 0, qris: 0, tunai: 0, qrisN: 0, tunaiN: 0, batal: 0 };
    Orders.all().forEach(o => {
      const d = days[o.hari]; if (!d) return; if (!d.tgl) d.tgl = o.tgl;
      if (o.status === 'Dibatalkan') { d.batal++; return; }
      d.cnt++; d.rev += o.total; d.ongkir += o.ongkir || 0;
      if (o.jenis === 'Antar') d.antar++; else d.ambil++;
      if (o.bayar === 'QRIS') { d.qris += o.total; d.qrisN++; } else { d.tunai += o.total; d.tunaiN++; }
    });
    const nw = new Date(), ref = days.find(d => d.tgl) || { hari: 0, tgl: nw.getDate() + ' ' + Object.keys(MON)[nw.getMonth()] + ' ' + nw.getFullYear() }; days.forEach(d => { if (!d.tgl) { const p = ref.tgl.split(' '), dt = new Date(+p[2], MON[p[1]], +p[0] - (d.hari - ref.hari)); d.tgl = dt.getDate() + ' ' + Object.keys(MON)[dt.getMonth()] + ' ' + dt.getFullYear(); } });
    return days;
  }
  const ARR = { up: '<svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 10V2M2.5 5.5L6 2l3.5 3.5"/></svg>', dn: '<svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2v8M2.5 6.5L6 10l3.5-3.5"/></svg>' };
  const sum = (a, f) => a.reduce((x, y) => x + f(y), 0);
  const pctDelta = (now, prev) => prev > 0 ? (now - prev) / prev * 100 : null;

  function paintDelta(key, pct, invert) {
    $$(`[data-delta="${key}"]`).forEach(el => {
      if (pct == null || !isFinite(pct)) { el.className = 'dl'; el.innerHTML = '<span class="muted">–</span>'; return; }
      const up = pct >= 0, bad = invert ? up : !up;
      el.className = 'dl' + (bad ? ' dn' : ''); el.innerHTML = `<i>${up ? ARR.up : ARR.dn}</i>${Math.abs(pct).toFixed(Math.abs(pct) < 10 ? 1 : 0).replace('.', ',')}%`;
    });
  }
  /** Sparkline interaktif: garis menggambar sendiri, titik mengikuti kursor, tooltip hanya muncul saat hover. */
  function spark(key, vals, labels, fmt, unit, red) {
    $$(`[data-spark="${key}"]`).forEach(box => {
      const w = box.clientWidth || 218, h = 56, n = vals.length; if (!n) return;
      const max = Math.max(...vals, 1), min = Math.min(...vals, 0), rng = (max - min) || 1, pad = 7;
      const X = i => n === 1 ? w / 2 : pad + i * (w - pad * 2) / (n - 1), Y = v => h - 8 - (v - min) / rng * (h - 20);
      const pts = vals.map((v, i) => [X(i), Y(v)]);
      let d = 'M' + pts[0][0] + ' ' + pts[0][1];
      for (let i = 1; i < n; i++) { const [x0, y0] = pts[i - 1], [x1, y1] = pts[i], cx = (x0 + x1) / 2; d += `C${cx} ${y0} ${cx} ${y1} ${x1} ${y1}`; }
      const col = red ? '#F43F5E' : '#00A650', gid = 'g' + key;
      const animate = box.dataset.n !== String(n) && !reduce();
      box.dataset.n = n;
      box.innerHTML = `<svg viewBox="0 0 ${w} ${h}" width="${w}" height="${h}" aria-hidden="true"><defs><linearGradient id="${gid}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${col}" stop-opacity=".28"/><stop offset="1" stop-color="${col}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${pts[n - 1][0]} ${h}L${pts[0][0]} ${h}Z" fill="url(#${gid})"/><path class="ln" d="${d}" stroke="${col}"/></svg><span class="dot${red ? ' r' : ''}"></span><span class="sptip"></span>`;
      const ln = $('.ln', box), dot = $('.dot', box), tip = $('.sptip', box);
      const place = i => { dot.style.left = pts[i][0] + 'px'; dot.style.top = pts[i][1] + 'px'; return i; };
      place(n - 1);
      if (animate) { const L = ln.getTotalLength(); ln.style.strokeDasharray = L; ln.style.strokeDashoffset = L; ln.getBoundingClientRect(); ln.style.transition = 'stroke-dashoffset 1s ease'; ln.style.strokeDashoffset = 0; dot.style.opacity = 0; dot.style.transition = 'opacity .3s ease .9s,left .12s,top .12s'; requestAnimationFrame(() => dot.style.opacity = 1); }
      const move = e => {
        const r = box.getBoundingClientRect(), i = Math.max(0, Math.min(n - 1, Math.round(((e.clientX - r.left) - pad) / ((w - pad * 2) / Math.max(n - 1, 1)))));
        place(i); box.classList.add('hov'); tip.innerHTML = `<b>${esc(labels[i])}</b><br>${fmt(vals[i])} ${unit}`; tip.style.left = Math.max(46, Math.min(w - 46, pts[i][0])) + 'px';
      };
      box.onpointermove = move; box.onpointerdown = move;
      box.onpointerleave = () => { box.classList.remove('hov'); place(n - 1); };
    });
  }
  function statCard(key, text, vals, labels, fmt, unit, pct, invert) {
    $$(`[data-stat="${key}"]`).forEach(x => x.textContent = text);
    paintDelta(key, pct, invert);
    const bad = pct != null && (invert ? pct > 0 : pct < 0);
    spark(key, vals, labels, fmt, unit, bad);
  }

  /* Dashboard: 4 kartu */
  function dashStats(days) {
    if (!$('[data-stat="today"]')) return;
    const all = Orders.all(), chrono = days.slice().reverse(), lab = chrono.map(d => short(d.tgl));
    const w = f => { const a = chrono.slice(-7), b = chrono.slice(-14, -7); return pctDelta(sum(a, f), sum(b, f)); };
    const t = days[0];
    statCard('today', t.cnt, chrono.map(d => d.cnt), lab, num, 'pesanan', w(d => d.cnt));
    statCard('revenue', rp(t.rev), chrono.map(d => d.rev), lab, rp, '', w(d => d.rev));
    statCard('action', all.filter(o => ['Belum dibayar', 'Diterima'].includes(o.status)).length, chrono.map(d => d.qrisN), lab, num, 'pesanan QRIS', w(d => d.qrisN));
    statCard('delivering', all.filter(o => o.status === 'Diantar').length, chrono.map(d => d.antar), lab, num, 'pesanan antar', w(d => d.antar));
  }

  /* Donut: menu terlaris hari ini (per kategori) */
  const DCOL = ['#8B45E0', '#FDA31B', '#0EAEA3', '#484074'], DPOS = ['TR', 'BR', 'BL', 'TL'];
  function donut() {
    const host = $('#ch-donut'); if (!host) return;
    const kat = {}; Menu.get().forEach(m => kat[m.nama] = m.kategori);
    const g = {}; let total = 0;
    Orders.all().filter(o => o.hari === 0 && o.status !== 'Dibatalkan').forEach(o => o.items.forEach(i => { const k = kat[i.nama] || 'Lainnya'; g[k] = (g[k] || 0) + i.qty; total += i.qty; }));
    let seg = Object.entries(g).sort((a, b) => b[1] - a[1]);
    if (seg.length > 4) { const rest = seg.slice(3).reduce((a, s) => a + s[1], 0); seg = [...seg.slice(0, 3), ['Lainnya', rest]]; }
    if (!total) { host.innerHTML = '<div class="empty sm">Belum ada pesanan hari ini.</div>'; return; }
    const C = 100 * 20 / (2 * Math.PI * 80) * 1; // lebar tutup bulat dalam satuan pathLength(100)
    let acc = 0; const arcs = seg.map(([n, v], i) => { const p = v / total * 100, st = acc; acc += p; return { n, v, p, st, i }; });
    const circles = arcs.map(a => { const len = Math.max(.6, a.p - C * 2), off = -(a.st + C); return `<g class="sg" data-i="${a.i}" tabindex="0"><circle class="o" cx="100" cy="100" r="80" pathLength="100" stroke-dasharray="${len} ${100 - len}" stroke-dashoffset="${off}"/><circle class="c" cx="100" cy="100" r="80" pathLength="100" stroke="${DCOL[a.i]}" data-len="${len}" stroke-dasharray="0 100" stroke-dashoffset="${off}"/></g>`; }).join('');
    const lab = arcs.map(a => `<div class="dlab ${DPOS[a.i]}" data-i="${a.i}"><small>${a.v} porsi</small><span><i style="background:${DCOL[a.i]}"></i>${esc(a.n)}</span></div>`).join('');
    const pills = arcs.map(a => `<div class="pill" data-i="${a.i}"><span><i style="background:${DCOL[a.i]}"></i>${esc(a.n)}</span><b>${Math.round(a.p)}%</b></div>`).join('');
    host.innerHTML = `<div class="dn3"><div class="donutwrap2"><svg viewBox="0 0 200 200" role="img" aria-label="Menu terlaris hari ini">${circles}</svg><div class="dcenter"><b>${total}</b><span>porsi</span></div></div>${lab}</div><div class="pills">${pills}</div>`;
    const cen = $('.dcenter', host), setC = a => { cen.innerHTML = a ? `<b>${esc(a.n)}</b><span>${a.v} porsi · ${Math.round(a.p)}%</span>` : `<b>${total}</b><span>porsi</span>`; };
    const hot = i => { $$('.pill', host).forEach(p => p.classList.toggle('hot', p.dataset.i == i)); $$('.dlab', host).forEach(p => p.classList.toggle('dim', i != null && p.dataset.i != i)); setC(i == null ? null : arcs[i]); };
    $$('.sg', host).forEach(s => { const i = +s.dataset.i; ['mouseenter', 'focus'].forEach(ev => s.addEventListener(ev, () => hot(i))); ['mouseleave', 'blur'].forEach(ev => s.addEventListener(ev, () => hot(null))); });
    $$('.pill', host).forEach(p => { const i = +p.dataset.i, sg = $(`.sg[data-i="${i}"]`, host); p.addEventListener('mouseenter', () => { hot(i); sg.style.transform = 'scale(1.07)'; }); p.addEventListener('mouseleave', () => { hot(null); sg.style.transform = ''; }); });
    const go = () => $$('.c', host).forEach(c => c.setAttribute('stroke-dasharray', `${c.dataset.len} ${100 - c.dataset.len}`));
    if (reduce()) go(); else requestAnimationFrame(() => requestAnimationFrame(go));
  }

  /* 7 hari terakhir: batang = metrik terpilih, garis putus-putus = metrik lainnya */
  let wkMetric = 'rev';
  function week(days) {
    const host = $('#ch-week'); if (!host) return;
    const rows = days.slice(0, 7).reverse(), bar = wkMetric, oth = bar === 'rev' ? 'cnt' : 'rev';
    const fmt = { rev: rp, cnt: v => num(v) + ' pesanan' }, ax = { rev: fmtAx, cnt: v => String(Math.round(v)) };
    const scale = k => { const step = niceStep(Math.max(...rows.map(r => r[k])), 4, k === 'cnt'); return { step, max: step * 4 }; };
    const sb = scale(bar), so = scale(oth);
    const ticks = [4, 3, 2, 1, 0].map(i => `<span>${ax[bar](sb.step * i)}</span>`).join('');
    const tks = rows.map((r, i) => `<div class="tk" tabindex="0" data-i="${i}"><div class="track"><div class="b" data-h="${r[bar] / sb.max * 100}" style="transition-delay:${i * 60}ms"></div></div><small>${wday(r.tgl)}</small><div class="tip"><small>${wday(r.tgl)}, ${esc(short(r.tgl))}</small><div><span><i style="background:#9347E0"></i>Penjualan</span><b>${rp(r.rev)}</b></div><div><span><i style="background:#14B8A6"></i>Pesanan</span><b>${r.cnt}</b></div></div></div>`).join('');
    host.innerHTML = `<div class="wk"><div class="yax">${ticks}</div><div class="plot"><div class="tracks">${tks}</div><svg class="trend" aria-hidden="true"><defs><linearGradient id="tg" x1="0" x2="1"><stop offset="0" stop-color="#14B8A6"/><stop offset="1" stop-color="#10C98A"/></linearGradient></defs><path class="tl" stroke="url(#tg)"/></svg></div></div><div class="wk-leg"><span><i style="background:#9347E0"></i>${bar === 'rev' ? 'Pendapatan (batang)' : 'Pesanan (batang)'}</span><span><i style="background:#14B8A6"></i>${oth === 'rev' ? 'Pendapatan (garis)' : 'Pesanan (garis)'}</span></div>`;
    const plot = $('.plot', host), svg = $('.trend', host), path = $('.tl', host), tkEls = $$('.tk', host);
    const pts = rows.map((r, i) => { const el = tkEls[i]; return [el.offsetLeft + el.offsetWidth / 2, 295 - r[oth] / so.max * 295]; });
    svg.setAttribute('viewBox', `0 0 ${plot.clientWidth} 295`); svg.setAttribute('width', plot.clientWidth);
    let d = 'M' + pts[0].join(' '); for (let i = 1; i < pts.length; i++) { const c = (pts[i - 1][0] + pts[i][0]) / 2; d += `C${c} ${pts[i - 1][1]} ${c} ${pts[i][1]} ${pts[i][0]} ${pts[i][1]}`; }
    path.setAttribute('d', d);
    const dots = pts.map((p, i) => { const s = document.createElement('span'); s.className = 'tdot'; s.style.left = p[0] + 'px'; s.style.top = p[1] + 'px'; s.style.transitionDelay = (400 + i * 60) + 'ms'; plot.appendChild(s); return s; });
    tkEls.forEach((el, i) => { const on = v => () => dots[i].classList.toggle('hot', v); el.addEventListener('mouseenter', on(true)); el.addEventListener('mouseleave', on(false)); el.addEventListener('focus', () => { if (el.matches(':focus-visible')) dots[i].classList.add('hot'); }); el.addEventListener('blur', on(false)); });
    const go = () => { $$('.b', host).forEach(b => b.style.height = b.dataset.h + '%'); dots.forEach(x => x.classList.add('in')); path.style.strokeDasharray = ''; };
    if (reduce()) { $$('.b', host).forEach(b => b.style.transition = 'none'); go(); return; }
    const L = path.getTotalLength(); path.style.strokeDasharray = L; path.style.strokeDashoffset = L; path.getBoundingClientRect();
    requestAnimationFrame(() => { go(); path.style.transition = 'stroke-dashoffset 1.1s ease .25s'; path.style.strokeDashoffset = 0; setTimeout(() => { path.style.strokeDasharray = '7 6'; path.style.strokeDashoffset = 0; }, 1450); });
  }
  const wm = $('#wk-metric');
  if (wm) wm.addEventListener('click', e => { const b = e.target.closest('button[data-m]'); if (!b) return; wkMetric = b.dataset.m; $$('button', wm).forEach(x => x.classList.toggle('on', x === b)); week(daily()); });

  /* Grafik diverging 12 hari: atas = sisi A, bawah = sisi B; satu skala untuk kedua sisi */
  function diverge(sel, days, A, B, fmtV, fmtAxis, int) {
    const host = $(sel); if (!host) return;
    const rows = days.slice(0, 12).reverse(), H = 193, GAP = 5;
    const step = niceStep(Math.max(...rows.map(r => Math.max(A.f(r), B.f(r)))), 4, int);
    const nu = Math.max(1, Math.ceil(Math.max(...rows.map(A.f)) / step - 1e-9)), nd = Math.max(1, Math.ceil(Math.max(...rows.map(B.f)) / step - 1e-9));
    const zy = H * nu / (nu + nd), pu = zy / nu / step, pd = (H - zy) / nd / step;
    const ytick = []; for (let i = nu; i >= 1; i--) ytick.push([zy - i * step * pu, fmtAxis(i * step)]); ytick.push([zy, '0']); for (let i = 1; i <= nd; i++) ytick.push([zy + i * step * pd, fmtAxis(i * step)]);
    const cols = rows.map((r, i) => {
      const hu = Math.max(A.f(r) ? 3 : 0, Math.min(zy - GAP, A.f(r) * pu - GAP)), hd = Math.max(B.f(r) ? 3 : 0, Math.min(H - zy - GAP, B.f(r) * pd - GAP));
      return `<div class="dc ${i >= 7 ? 'rt' : ''}" tabindex="0" data-i="${i}"><div class="bu" style="top:${zy - GAP - hu}px;height:${hu}px;transition-delay:${i * 35}ms"></div><div class="bd" style="top:${zy + GAP}px;height:${hd}px;transition-delay:${i * 35}ms"></div><div class="tip"><small>${esc(short(r.tgl))}</small><div><span><i style="background:#9B51E0"></i>${A.n}</span><b>${fmtV(A.f(r))}</b></div><div><span><i style="background:#04C378"></i>${B.n}</span><b>${fmtV(B.f(r))}</b></div></div></div>`;
    }).join('');
    host.innerHTML = `<div class="dv"><div class="yax2">${ytick.map(t => `<span style="top:${t[0]}px">${t[1]}</span>`).join('')}</div><div class="dvplot" style="--zy:${zy}px"><div class="zero" style="top:${zy}px"></div>${cols}</div></div><div class="xl"></div>`;
    const plot = $('.dvplot', host), xl = $('.xl', host), dcs = $$('.dc', host);
    [1, 5, 10].forEach(i => { const c = dcs[i], x = plot.offsetLeft + c.offsetLeft + c.offsetWidth / 2; const s = document.createElement('span'); s.style.left = x + 'px'; s.textContent = short(rows[i].tgl); xl.appendChild(s); const v = document.createElement('i'); v.className = 'vg'; v.style.left = (c.offsetLeft + c.offsetWidth / 2) + 'px'; plot.insertBefore(v, plot.firstChild.nextSibling); });
    if (reduce()) plot.classList.add('go'); else requestAnimationFrame(() => requestAnimationFrame(() => plot.classList.add('go')));
  }
  function charts() {
    const days = daily();
    dashStats(days); donut(); week(days);
    diverge('#ch-ab', days, { n: 'Ambil', f: r => r.ambil }, { n: 'Antar', f: r => r.antar }, v => num(v) + ' pesanan', v => String(v), true);
    diverge('#ch-qt', days, { n: 'QRIS', f: r => r.qris }, { n: 'Tunai', f: r => r.tunai }, rp, fmtAx);
  }
  let chartsReady = false;
  function refreshStats() {
    if (!$('#ch-week') && !$('[data-stat="today"]')) return;
    const days = daily();
    if (chartsReady && $('#ch-week')) { dashStats(days); return donutWeekIfChanged(days); }
    chartsReady = true; charts();
  }
  /* Setelah aksi (mis. status berubah), sesuaikan grafik tanpa mengulang animasi bila datanya sama. */
  let lastSig = '';
  function donutWeekIfChanged(days) {
    const sig = JSON.stringify([days.slice(0, 12), Orders.all().filter(o => o.hari === 0).map(o => o.status === 'Dibatalkan' ? 0 : o.items.length)]);
    if (sig !== lastSig) { lastSig = sig; charts(); }
  }
  let rzT; window.addEventListener('resize', () => { clearTimeout(rzT); rzT = setTimeout(() => { if ($('#ch-week')) charts(); const c = Lists.rep; if (c && c.el) c.after(c.data(c.state)); }, 180); });

  /* ---------- Menu: daftar, form, hapus, ketersediaan ---------- */
  Lists.menu = {
    data: st => Menu.get().filter(m => (!st.f.kategori || m.kategori === st.f.kategori) && (!st.f.stok || String(+m.stok) === st.f.stok) && (!st.q || m.nama.toLowerCase().includes(st.q))),
    row: m => `<div class="lrow ${m.stok ? '' : 'off'}"><b data-l="Menu">${esc(m.emoji)} ${esc(m.nama)}</b><span data-l="Kategori">${esc(m.kategori)}</span><span data-l="Harga">${rp(m.harga)}</span><span data-l="Tersedia"><label class="sw"><input type="checkbox" data-stok="${m.id}" ${m.stok ? 'checked' : ''} aria-label="Tersedia ${esc(m.nama)}"><span></span></label></span><div class="acts"><button type="button" class="btn sm" data-edit="${m.id}">Ubah</button><button type="button" class="btn sm danger" data-del="${m.id}">Hapus</button></div></div>`,
    empty: 'Menu tidak ditemukan.'
  };
  const putMenu = m => { const l = Menu.get(), i = l.findIndex(x => x.id === m.id); if (i >= 0) l[i] = m; else l.push(m); };
  document.addEventListener('change', async e => {
    const s = e.target.closest('[data-stok]'); if (!s) return;
    const it = Menu.get().find(x => x.id == s.dataset.stok); if (!it) return; const want = s.checked; s.disabled = true;
    try { putMenu((await Api.post(`/admin/api/menu/${it.id}/stok`, { stok: want })).menu); toast(`${it.nama}: ${want ? 'tersedia' : 'habis'}`); }
    catch (x) { s.checked = !want; fail(x); }
    finally { s.disabled = false; }
    if (Lists.menu && Lists.menu.el) renderList('menu'); renderHabis(); renderKasirGrid();
  });
  const mf = $('#menuform');
  let mfFoto = null /* alamat foto yang tampil */, mfBlob = null /* foto baru untuk diunggah */, mfHapus = false;
  const showFoto = () => { const pv = $('#mf-prev'); if (!pv) return; pv.hidden = !mfFoto; if (mfFoto) pv.src = mfFoto; const rm = $('#mf-fotodel'); if (rm) rm.hidden = !mfFoto; };
  // perkecil foto (maks. 640 px) sebelum diunggah agar cepat dan hemat kuota
  const shrink = (file, cb) => { const r = new FileReader(); r.onload = () => { const im = new Image(); im.onload = () => { const k = Math.min(1, 640 / Math.max(im.width, im.height)), c = document.createElement('canvas'); c.width = Math.round(im.width * k); c.height = Math.round(im.height * k); c.getContext('2d').drawImage(im, 0, 0, c.width, c.height); c.toBlob(b => cb(b, c.toDataURL('image/jpeg', .5)), 'image/jpeg', .85); }; im.onerror = () => cb(null); im.src = r.result; }; r.readAsDataURL(file); };
  const ffile = $('#mf-foto');
  if (ffile) {
    ffile.addEventListener('change', e => { const f = e.target.files[0]; if (!f) return; if (!/^image\/(png|jpeg)$/.test(f.type)) { toast('Gunakan foto JPG atau PNG', true); e.target.value = ''; return; } if (f.size > 2e6) { toast('Foto terlalu besar (maks. 2 MB)', true); e.target.value = ''; return; } shrink(f, (b, prev) => { if (!b) { toast('Foto tidak dapat dibaca', true); return; } mfBlob = b; mfHapus = false; mfFoto = URL.createObjectURL(b); showFoto(); }); });
    const rm = $('#mf-fotodel'); if (rm) rm.addEventListener('click', () => { mfFoto = null; mfBlob = null; mfHapus = true; ffile.value = ''; showFoto(); });
  }
  function openMenuForm(id) {
    const m = id ? Menu.get().find(x => x.id == id) : null;
    $('#mf-title').textContent = m ? 'Ubah Menu' : 'Tambah Menu'; $('#mf-id').value = m ? m.id : '';
    $('#mf-nama').value = m ? m.nama : ''; $('#mf-kat').value = m ? m.kategori : 'Bakaran'; $('#mf-harga').value = m ? m.harga : '';
    $('#mf-emoji').value = m ? m.emoji : ''; $('#mf-desk').value = m ? m.desk || '' : ''; $('#mf-stok').checked = m ? m.stok : true; mfFoto = m && m.foto ? m.foto : null; mfBlob = null; mfHapus = false; showFoto(); const ff = $('#mf-foto'); if (ff) ff.value = '';
    $$('.errmsg', mf).forEach(x => x.classList.remove('on')); $$('.err', mf).forEach(x => x.classList.remove('err')); openO('formmenu'); $('#mf-nama').focus();
  }
  let delId = null;
  document.addEventListener('click', e => {
    if (e.target.closest('[data-menu-add]')) openMenuForm(null);
    const ed = e.target.closest('[data-edit]'); if (ed && $('#menuform')) openMenuForm(ed.dataset.edit);
    const de = e.target.closest('[data-del]'); if (de && $('#hapusmenu')) { delId = de.dataset.del; const m = Menu.get().find(x => x.id == delId); $('#hm-text').textContent = `Hapus "${m.nama}"? Menu tidak akan tampil lagi bagi pelanggan. Riwayat pesanan tetap tersimpan.`; openO('hapusmenu'); }
  });
  if (mf) mf.addEventListener('submit', async e => {
    e.preventDefault();
    const nama = $('#mf-nama').value.trim(), harga = +($('#mf-harga').value.replace(/\D/g, '')), okN = nama.length > 0, okH = harga > 0;
    setErr(mf, 'nama', !okN); mark($('#mf-nama'), !okN); setErr(mf, 'harga', !okH); mark($('#mf-harga'), !okH); if (!okN || !okH) return;
    const id = $('#mf-id').value, fd = new FormData(), btn = $('button[type=submit]', mf);
    fd.append('nama', nama); fd.append('kategori', $('#mf-kat').value); fd.append('harga', harga); fd.append('emoji', $('#mf-emoji').value.trim() || '🍽️'); fd.append('desk', $('#mf-desk').value.trim()); fd.append('stok', $('#mf-stok').checked ? '1' : '0');
    if (mfBlob) fd.append('foto', mfBlob, 'foto.jpg'); else if (mfHapus) fd.append('hapus_foto', '1');
    btn.disabled = true;
    try { putMenu((await Api.post(id ? '/admin/api/menu/' + id : '/admin/api/menu', fd)).menu); closeO($('#formmenu')); toast(id ? 'Menu diperbarui' : 'Menu ditambahkan'); renderAll(); renderHabis(); renderKasirGrid(); }
    catch (x) { fail(x); if (x.errors && x.errors.nama) { setErr(mf, 'nama', true); mark($('#mf-nama'), true); } if (x.errors && x.errors.harga) { setErr(mf, 'harga', true); mark($('#mf-harga'), true); } }
    finally { btn.disabled = false; }
  });
  const hmok = $('#hm-ok');
  if (hmok) hmok.addEventListener('click', async () => {
    hmok.disabled = true;
    try { await Api.del('/admin/api/menu/' + delId); Menu.set(Menu.get().filter(x => x.id != delId)); closeO($('#hapusmenu')); toast('Menu dihapus'); renderAll(); renderHabis(); renderKasirGrid(); }
    catch (x) { closeO($('#hapusmenu')); fail(x); } finally { hmok.disabled = false; }
  });
  function renderHabis() {
    const box = $('#habis-list'); if (!box) return;
    box.innerHTML = Menu.get().map(m => `<div class="switchrow"><span>${esc(m.emoji)} ${esc(m.nama)}</span><label class="sw"><input type="checkbox" data-stok="${m.id}" ${m.stok ? 'checked' : ''} aria-label="Tersedia ${esc(m.nama)}"><span></span></label></div>`).join('');
  }

  /* ---------- Akun ---------- */
  const me = () => (SEED.me || {}).email;
  const activeCount = () => Akun.get().filter(a => a.aktif).length;
  Lists.akun = {
    data: st => Akun.get().filter(a => (!st.f.aktif || String(+a.aktif) === st.f.aktif) && (!st.q || (a.nama + ' ' + a.email).toLowerCase().includes(st.q))),
    row: a => `<div class="lrow ${a.aktif ? '' : 'off'}"><b data-l="Nama">${esc(a.nama)}</b><span data-l="Email">${esc(a.email)}</span><span data-l="Peran">${esc(a.peran)}</span><span data-l="Status"><span class="chip ${a.aktif ? 'ok' : 'plain'}">${a.aktif ? 'Aktif' : 'Nonaktif'}</span></span><span data-l="Terakhir Masuk">${esc(a.terakhir)}</span><div class="acts"><button type="button" class="btn sm" data-aedit="${a.id}">Ubah</button><button type="button" class="btn sm" data-atog="${a.id}">${a.aktif ? 'Nonaktifkan' : 'Aktifkan'}</button><button type="button" class="btn sm danger" data-adel="${a.id}" ${a.email === me() ? 'disabled title="Tidak bisa menghapus akun yang sedang dipakai"' : ''}>Hapus</button></div></div>`,
    empty: 'Akun tidak ditemukan.'
  };
  const af = $('#akunform');
  function openAkunForm(id) {
    const a = id ? Akun.get().find(x => x.id == id) : null;
    $('#af-title').textContent = a ? 'Ubah Akun' : 'Tambah Akun'; $('#af-id').value = a ? a.id : '';
    $('#af-nama').value = a ? a.nama : ''; $('#af-email').value = a ? a.email : ''; $('#af-sandi').value = ''; $('#af-aktif').checked = a ? a.aktif : true;
    $('#af-sandi-l').textContent = a ? 'Kata sandi baru (kosongkan jika tidak diubah)' : 'Kata sandi sementara';
    $$('.errmsg', af).forEach(x => x.classList.remove('on')); $$('.err', af).forEach(x => x.classList.remove('err')); openO('formakun'); $('#af-nama').focus();
  }
  const ab = $('#akun-add'); if (ab) ab.addEventListener('click', () => openAkunForm(null));
  let adelId = null;
  const putAkun = a => { const l = Akun.get(), i = l.findIndex(x => x.id === a.id); if (i >= 0) l[i] = a; else l.push(a); };
  document.addEventListener('click', async e => {
    const ed = e.target.closest('[data-aedit]'); if (ed) openAkunForm(ed.dataset.aedit);
    const tg = e.target.closest('[data-atog]');
    if (tg) {
      const a = Akun.get().find(x => x.id == tg.dataset.atog); if (!a) return;
      if (a.aktif && activeCount() <= 1) { toast('Minimal harus ada satu akun aktif', true); return; }
      if (a.aktif && a.email === me()) { toast('Anda tidak bisa menonaktifkan akun yang sedang dipakai', true); return; }
      tg.disabled = true;
      try { const n = (await Api.post(`/admin/api/akun/${a.id}/aktif`, { aktif: !a.aktif })).akun; putAkun(n); toast(`${n.nama} ${n.aktif ? 'diaktifkan' : 'dinonaktifkan'}`); }
      catch (x) { fail(x); }
      renderList('akun');
    }
    const dl = e.target.closest('[data-adel]');
    if (dl && !dl.disabled) { adelId = dl.dataset.adel; const a = Akun.get().find(x => x.id == adelId); if (a.aktif && activeCount() <= 1) { toast('Minimal harus ada satu akun aktif', true); return; } $('#ha-text').textContent = `Akun "${a.nama}" tidak akan bisa masuk lagi.`; openO('hapusakun'); }
  });
  const haok = $('#ha-ok');
  if (haok) haok.addEventListener('click', async () => {
    haok.disabled = true;
    try { await Api.del('/admin/api/akun/' + adelId); Akun.set(Akun.get().filter(x => x.id != adelId)); closeO($('#hapusakun')); toast('Akun dihapus'); }
    catch (x) { closeO($('#hapusakun')); fail(x); } finally { haok.disabled = false; }
    renderList('akun');
  });
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (af) af.addEventListener('submit', async e => {
    e.preventDefault(); const id = $('#af-id').value, list = Akun.get();
    const nama = $('#af-nama').value.trim(), email = $('#af-email').value.trim().toLowerCase(), sandi = $('#af-sandi').value;
    const okN = !!nama, okE = emailRe.test(email) && !list.some(a => a.email === email && a.id != id), okS = id ? (sandi === '' || sandi.length >= 8) : sandi.length >= 8;
    setErr(af, 'nama', !okN); mark($('#af-nama'), !okN); setErr(af, 'email', !okE); mark($('#af-email'), !okE); setErr(af, 'sandi', !okS); mark($('#af-sandi'), !okS);
    if (!okN || !okE || !okS) return;
    const aktif = $('#af-aktif').checked, btn = $('button[type=submit]', af); btn.disabled = true;
    try { putAkun((await Api.post(id ? '/admin/api/akun/' + id : '/admin/api/akun', { nama, email, password: sandi, aktif })).akun); closeO($('#formakun')); toast(id ? 'Akun diperbarui' : 'Akun ditambahkan'); renderList('akun'); }
    catch (x) { fail(x); if (x.errors && x.errors.email) { setErr(af, 'email', true); mark($('#af-email'), true); } }
    finally { btn.disabled = false; }
  });

  /* ---------- Laporan ---------- */
  /** Satu baris per hari, dihitung dari pesanan; metode memfilter pendapatan. */
  function repDay(d, met) { return Object.assign({}, d, { pend: met === 'QRIS' ? d.qris : met === 'Tunai' ? d.tunai : d.rev, pes: met === 'QRIS' ? d.qrisN : met === 'Tunai' ? d.tunaiN : d.cnt }); }
  Lists.rep = {
    data: st => {
      const met = st.f.metode, days = +(st.f.range || 7);
      const rows = daily().filter(r => r.hari < days).map(r => repDay(r, met));
      const u = st.f.urut || 'tgl'; rows.sort((a, b) => u === 'pend' ? b.pend - a.pend : u === 'pes' ? b.pes - a.pes : a.hari - b.hari);
      return rows.some(r => r.pes || r.batal) ? rows : [];
    },
    empty: 'Belum ada penjualan pada periode ini.',
    row: r => `<div class="lrow"><b data-l="Tanggal">${esc(r.tgl)}</b><span class="r" data-l="Pesanan">${r.pes}</span><span class="r" data-l="Ambil">${r.ambil}</span><span class="r" data-l="Antar">${r.antar}</span><b class="r" data-l="Pendapatan">${rp(r.pend)}</b><span class="r" data-l="Ongkir">${rp(r.ongkir)}</span></div>`,
    after: () => {
      if (!$('[data-stat="rev"]')) return;
      const st = Lists.rep.state, met = st.f.metode, days = +(st.f.range || 7), all = daily().map(d => repDay(d, met));
      const cur = all.filter(r => r.hari < days).reverse(), prev = all.filter(r => r.hari >= days && r.hari < days * 2).reverse(), lab = cur.map(r => short(r.tgl));
      const tot = a => ({ rev: sum(a, r => r.pend), cnt: sum(a, r => r.pes), bat: sum(a, r => r.batal) });
      const t = tot(cur), p = tot(prev), has = prev.length === days;
      const avg = a => { const x = tot(a); return x.cnt ? x.rev / x.cnt : 0; };
      statCard('rev', rp(t.rev), cur.map(r => r.pend), lab, rp, '', has ? pctDelta(t.rev, p.rev) : null);
      statCard('cnt', t.cnt, cur.map(r => r.pes), lab, num, 'pesanan', has ? pctDelta(t.cnt, p.cnt) : null);
      statCard('bat', t.bat, cur.map(r => r.batal), lab, num, 'dibatalkan', has ? pctDelta(t.bat, p.bat) : null, true);
      statCard('avg', rp(t.cnt ? t.rev / t.cnt : 0), cur.map(r => r.pes ? r.pend / r.pes : 0), lab, rp, '/ pesanan', has ? pctDelta(avg(cur), avg(prev)) : null);
    }
  };
  const csvBtn = $('#csv');
  if (csvBtn) csvBtn.addEventListener('click', () => {
    const c = Lists.rep, rows = c.data(c.state);
    const out = ['Tanggal,Pesanan,Ambil,Antar,Pendapatan,Ongkir,Dibatalkan', ...rows.map(r => [r.tgl, r.pes, r.ambil, r.antar, r.pend, r.ongkir, r.batal].join(','))].join('\n');
    const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([out], { type: 'text/csv;charset=utf-8' })); a.download = 'laporan-snackin.csv'; document.body.appendChild(a); a.click(); a.remove(); toast('Laporan diunduh');
  });

  /* ---------- Kasir ---------- */
  let kCart = [], kCat = '', kQ = '';
  function renderKasirGrid() {
    const g = $('#k-grid'); if (!g) return;
    const cats = ['', ...new Set(Menu.get().map(m => m.kategori))];
    $('#k-tabs').innerHTML = cats.map(c => `<button type="button" class="tab ${c === kCat ? 'on' : ''}" data-kc="${esc(c)}">${c || 'Semua'}</button>`).join('');
    const list = Menu.get().filter(m => (!kCat || m.kategori === kCat) && (!kQ || m.nama.toLowerCase().includes(kQ)));
    g.innerHTML = list.length ? list.map(m => `<button type="button" class="mi ${m.stok ? '' : 'off'}" data-kadd="${m.id}" ${m.stok ? '' : 'disabled'}><span class="em">${esc(m.emoji)}</span><b>${esc(m.nama)}</b><span class="p">${m.stok ? rp(m.harga) : 'Habis'}</span></button>`).join('') : '<div class="empty">Menu tidak ditemukan.</div>';
  }
  const kTotal = () => kCart.reduce((a, i) => a + i.qty * i.harga, 0);
  function renderKasirCart() {
    const box = $('#k-cart'); if (!box) return;
    box.innerHTML = kCart.length ? kCart.map((i, ix) => `<div class="cartline"><div><b>${esc(i.nama)}</b><div class="muted">${rp(i.harga)}</div></div><div class="qty"><button type="button" data-kq="${ix}" data-d="-1" aria-label="Kurangi">−</button><b>${i.qty}</b><button type="button" data-kq="${ix}" data-d="1" aria-label="Tambah">+</button></div></div>`).join('') : '<div class="empty sm" style="margin-top:12px">Pilih menu di sebelah kiri.</div>';
    $('#k-total').textContent = rp(kTotal()); kChange();
  }
  function kChange() {
    const uang = +($('#k-uang').value.replace(/\D/g, '')) || 0, t = kTotal(), k = uang - t;
    $('#k-kembali').textContent = rp(Math.max(0, k)); $('#k-err').classList.remove('on'); $('#k-uang').classList.remove('err');
  }
  const kform = $('#kform');
  if (kform) {
    renderKasirGrid(); renderKasirCart();
    $('#k-cari').addEventListener('input', e => { kQ = e.target.value.trim().toLowerCase(); renderKasirGrid(); });
    document.addEventListener('click', e => {
      const t = e.target.closest('[data-kc]'); if (t) { kCat = t.dataset.kc; renderKasirGrid(); }
      const a = e.target.closest('[data-kadd]'); if (a) { const m = Menu.get().find(x => x.id == a.dataset.kadd), it = kCart.find(x => x.id === m.id); if (it) it.qty++; else kCart.push({ id: m.id, nama: m.nama, harga: m.harga, qty: 1 }); renderKasirCart(); }
      const q = e.target.closest('[data-kq]'); if (q) { const it = kCart[+q.dataset.kq]; it.qty += +q.dataset.d; if (it.qty < 1) kCart.splice(+q.dataset.kq, 1); renderKasirCart(); }
    });
    $('#k-uang').addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g, ''); kChange(); });
    const kq = Cfg.get().qris; $('#k-qrbox').innerHTML = kq ? `<img src="${esc(kq)}" alt="Kode QRIS toko">` : '<span>QRIS belum diatur. Unggah di Pengaturan.</span>';
    $$('input[name=kb]', kform).forEach(r => r.addEventListener('change', () => { const q = $('input[name=kb]:checked', kform).value === 'QRIS'; $('#k-tunai').hidden = q; $('#k-qris').hidden = !q; }));
    $('#k-reset').addEventListener('click', () => { kCart = []; $('#k-uang').value = ''; $('#k-nama').value = ''; renderKasirCart(); });
    let lastKasir = null; $('#ks-print').addEventListener('click', () => printStruk(lastKasir));
    kform.addEventListener('submit', async e => {
      e.preventDefault(); if (!kCart.length) { toast('Pilih menu terlebih dahulu', true); return; }
      const met = $('input[name=kb]:checked', kform).value, t = kTotal(), uang = +($('#k-uang').value.replace(/\D/g, '')) || 0;
      if (met === 'Tunai' && uang < t) { $('#k-err').classList.add('on'); $('#k-uang').classList.add('err'); return; }
      const btn = $('button[type=submit]', kform); btn.disabled = true;
      try {
        const ko = Orders.put((await Api.post('/admin/api/orders/kasir', { nama: $('#k-nama').value.trim(), bayar: met, uang: met === 'Tunai' ? uang : null, items: kCart.map(i => ({ id: i.id, qty: i.qty })) })).order); lastKasir = ko;
        if (Cfg.get().autoprint) printStruk(ko); else { $('#ks-info').textContent = `${ko.kode} · ${rp(ko.total)}` + (met === 'Tunai' ? ` · kembalian ${rp(Math.max(0, uang - ko.total))}` : ' · QRIS'); openO('ksukses'); }
        toast(`${ko.kode} dicatat (${rp(ko.total)})`); kCart = []; $('#k-uang').value = ''; $('#k-nama').value = ''; renderKasirCart(); renderAll();
      } catch (x) { fail(x); if (x.status === 409) { await pullState(); renderKasirGrid(); } }
      finally { btn.disabled = false; }
    });
  }

  /* ---------- Pengaturan ---------- */
  const sf = $('#setform');
  if (sf) {
    /* lokasi dapur: cari alamat, ketuk peta, atau geser penanda */
    let kPt = null, kMap = null, kMk = null;
    if (MapKit.ok()) {
      const c0 = Cfg.get(); kPt = { lat: +c0.lat || -7.9608, lng: +c0.lng || 112.6173 };
      kMap = MapKit.create($('#s-map'), kPt, 16);
      kMk = L.marker([kPt.lat, kPt.lng], { icon: MapKit.icon.k(), draggable: true, title: 'Lokasi dapur (geser untuk memindah)', alt: 'Penanda lokasi dapur' }).addTo(kMap);
      const mv = (lat, lng, addr, zoom) => { kPt = { lat: +lat.toFixed(6), lng: +lng.toFixed(6) }; kMk.setLatLng([lat, lng]); if (zoom) kMap.setView([lat, lng], zoom); $('#s-koord').textContent = kPt.lat + ', ' + kPt.lng; if (addr) $('#s-alamat').value = addr; else MapKit.reverse(kPt).then(t => { if (t && !$('#s-alamat').dataset.edit) $('#s-alamat').value = t; }); };
      kMk.on('dragend', () => { const q = kMk.getLatLng(); mv(q.lat, q.lng); });
      kMap.on('click', e => mv(e.latlng.lat, e.latlng.lng));
      MapKit.bindSearch({ q: $('#s-q'), go: $('#s-go'), res: $('#s-res'), note: $('#s-note'), near: () => kPt, onPick: x => mv(x.lat, x.lng, x.nama, 17) });
      $('#s-alamat').addEventListener('input', e => { e.target.dataset.edit = '1'; });
      setTimeout(() => kMap.invalidateSize(), 100);
    } else { $('#s-mapbox').hidden = true; $('#s-nomap').hidden = false; }
    const showQris = u => { $('#s-qprev').hidden = !u; if (u) $('#s-qprev').src = u; $('#s-qdel').hidden = !u; };
    const fill = () => { const c = Cfg.get(); $('#s-koord').textContent = (+c.lat || -7.9608) + ', ' + (+c.lng || 112.6173); $('#s-toko').value = c.toko; $('#s-wa').value = c.wa; $('#s-buka').value = String(c.buka).replace('.', ':'); $('#s-tutup').value = String(c.tutup).replace('.', ':'); $('#s-aktif').checked = !!c.aktif; $('#s-ongkir').value = c.ongkir; $('#s-area').value = c.area; $('#s-alamat').value = c.alamat || ''; $('#s-kertas').value = String(c.kertas || '80'); $('#s-footer').value = c.footer || ''; $('#s-autoprint').checked = !!c.autoprint; $('#s-radius').value = c.radius || ''; kPt = { lat: +c.lat || -7.9608, lng: +c.lng || 112.6173 }; if (kMk) { kMk.setLatLng([kPt.lat, kPt.lng]); kMap.setView([kPt.lat, kPt.lng], 16); } $('#s-estimasi').value = c.estimasi != null ? c.estimasi : 20; $('#s-bayarbatas').value = c.bayarbatas != null ? c.bayarbatas : 30; showQris(c.qris); };
    fill();
    $('#s-qris').addEventListener('change', async e => {
      const f = e.target.files[0]; if (!f) return;
      if (!/^image\/(png|jpeg)$/.test(f.type)) { toast('Gunakan gambar JPG atau PNG', true); e.target.value = ''; return; }
      if (f.size > 2e6) { toast('Gambar terlalu besar (maks. 2 MB)', true); e.target.value = ''; return; }
      const fd = new FormData(); fd.append('qris', f);
      try { Cfg.set((await Api.post('/admin/api/settings/qris', fd)).settings); showQris(Cfg.get().qris); toast('QRIS disimpan'); } catch (x) { fail(x); }
      e.target.value = '';
    });
    $('#s-qdel').addEventListener('click', async () => { try { Cfg.set((await Api.del('/admin/api/settings/qris')).settings); showQris(''); toast('QRIS dihapus'); } catch (x) { fail(x); } });
    const dz = $('#dz-in'), db = $('#dz-btn');
    if (dz) {
      dz.addEventListener('input', () => { db.disabled = dz.value.trim() !== 'HAPUS'; });
      db.addEventListener('click', async () => { db.disabled = true; try { const j = await Api.del('/admin/api/orders', { confirm: dz.value.trim() }); Orders.d = []; dz.value = ''; toast(j.dihapus + ' pesanan dihapus. Nomor mulai dari SNK-001 lagi.'); } catch (x) { fail(x); db.disabled = dz.value.trim() !== 'HAPUS'; } });
    }
    $('#s-reset').addEventListener('click', () => { $$('.errmsg', sf).forEach(x => x.classList.remove('on')); fill(); toast('Perubahan yang belum disimpan dibatalkan'); });
    sf.addEventListener('submit', async e => {
      e.preventDefault(); const toko = $('#s-toko').value.trim(), wa = $('#s-wa').value.replace(/\D/g, ''), ongkir = $('#s-ongkir').value.trim();
      const okT = !!toko, okW = wa.length >= 9 && wa.length <= 14, okO = /^\d+$/.test(ongkir);
      setErr(sf, 'toko', !okT); mark($('#s-toko'), !okT); setErr(sf, 'wa', !okW); mark($('#s-wa'), !okW); setErr(sf, 'ongkir', !okO); mark($('#s-ongkir'), !okO);
      if (!okT || !okW || !okO) return;
      const btn = $('button[type=submit]', sf); btn.disabled = true;
      try {
        Cfg.set((await Api.post('/admin/api/settings', { toko, wa: $('#s-wa').value.trim(), buka: $('#s-buka').value.replace(':', '.'), tutup: $('#s-tutup').value.replace(':', '.'), aktif: $('#s-aktif').checked, ongkir: +ongkir, area: $('#s-area').value.trim(), kertas: $('#s-kertas').value, footer: $('#s-footer').value.trim(), autoprint: $('#s-autoprint').checked, alamat: $('#s-alamat').value.trim(), radius: $('#s-radius').value.trim() === '' ? 0 : +$('#s-radius').value.replace(',', '.'), estimasi: $('#s-estimasi').value.trim() === '' ? 0 : +$('#s-estimasi').value, bayarbatas: $('#s-bayarbatas').value.trim() === '' ? 0 : +$('#s-bayarbatas').value, lat: kPt ? kPt.lat : Cfg.get().lat, lng: kPt ? kPt.lng : Cfg.get().lng })).settings);
        toast('Pengaturan disimpan'); fill();
      } catch (x) { fail(x); } finally { btn.disabled = false; }
    });
  }

  /* ---------- Login ---------- */
  const lf = $('#loginform');
  if (lf) lf.addEventListener('submit', async e => {
    e.preventDefault(); const email = $('#email').value.trim().toLowerCase(), pw = $('#pw').value, er = $('#loginerr');
    const okE = emailRe.test(email), okP = pw.length > 0; setErr(lf, 'email', !okE); mark($('#email'), !okE); setErr(lf, 'pw', !okP); mark($('#pw'), !okP); er.hidden = true; if (!okE || !okP) return;
    const btn = $('button[type=submit]', lf); btn.disabled = true; btn.textContent = 'Memeriksa…';
    try { await Api.post('/admin/api/login', { email, password: pw }); location.href = '/admin'; }
    catch (x) { er.textContent = x.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.' : x.status === 0 || x.status >= 500 ? x.message : 'Email atau kata sandi salah, atau akun nonaktif.'; er.hidden = false; btn.disabled = false; btn.textContent = 'Masuk'; }
  });

  /* ---------- Halaman pengguna ---------- */
  const cartBadge = () => { const n = Cart.count(); $$('.cartbadge').forEach(x => { x.textContent = n; x.hidden = !n; }); $$('[data-cart-n]').forEach(x => x.textContent = n + ' item'); $$('[data-cart-num]').forEach(x => x.textContent = n); const cl = $('#cf-list'); if (cl) cl.innerHTML = Cart.get().map(i => `<li><span>${i.qty}× ${esc(i.nama)}</span><b>${rp(i.qty * i.harga)}</b></li>`).join(''); $$('[data-cart-sub]').forEach(x => x.textContent = rp(Cart.sub())); const h = $('#hcart'); if (h) h.classList.toggle('has', n > 0); const ct = $('#cta'); if (ct) ct.hidden = !n; };
  const open = () => Cfg.get().open !== false;
  const paintOpen = () => { const oc = $('#opennow'); if (!oc) return; const c = Cfg.get(); oc.textContent = open() ? `Buka sampai ${c.tutup}` : c.aktif === false ? 'Sedang tutup' : `Tutup · buka ${c.buka}`; oc.className = 'chip ' + (open() ? 'ok' : 'bad'); };
  const syncCartNote = () => { const d = Cart.sync(); if (d.length) toast(d.length === 1 ? `${d[0]} sudah habis dan dihapus dari keranjang` : `${d.length} menu sudah habis dan dihapus dari keranjang`, true); return d; };
  /** Kaki halaman: alamat, jam, WhatsApp. */
  const paintFoot = () => {
    const c = Cfg.get(), f = $('#ufoot'); if (!f) return;
    $('[data-f="alamat"]', f).textContent = c.alamat || 'Alamat dapur belum diatur';
    $('[data-f="jam"]', f).textContent = `Buka setiap hari ${c.buka} – ${c.tutup}` + (c.aktif === false ? ' (sementara tutup)' : '');
    const w = $('[data-f="wa"]', f); w.href = waLink('Halo ' + (c.toko || 'Snack.Snackin') + ', saya mau bertanya.'); $('[data-f="wanum"]', f).textContent = c.wa || '';
  };
  if (AREA === 'user') { paintOpen(); paintFoot(); }
  cartBadge();
  if (PAGE === 'umenu') {
    let uc = '', uq = '', sel = null, qty = 1, token = 0;
    const art = m => m.foto ? `<img src="${esc(m.foto)}" alt="">` : `<span class="em" aria-hidden="true">${esc(m.emoji)}</span>`;
    const TN = { Bakaran: 1, Cilok: 2, Camilan: 3, 'Nasi Bakar': 4, Minuman: 5 }, tint = k => 't' + (TN[k] || (([...String(k)].reduce((a, c) => a + c.charCodeAt(0), 0) % 5) + 1));
    const qtyOf = id => (Cart.get().find(x => x.id === id) || {}).qty || 0;
    const ctl = m => {
      const q = qtyOf(m.id);
      if (!m.stok) return '<span class="habis">Habis</span>';
      if (!open()) return '<span class="habis">Tutup</span>';
      return q ? `<div class="qty big" role="group" aria-label="Jumlah ${esc(m.nama)}"><button type="button" data-dec="${m.id}" aria-label="Kurangi ${esc(m.nama)}">−</button><b>${q}</b><button type="button" data-inc="${m.id}" aria-label="Tambah ${esc(m.nama)}">+</button></div>`
        : `<button type="button" class="btn primary xlg addb" data-inc="${m.id}" aria-label="Tambah ${esc(m.nama)} ke keranjang">+ Tambah</button>`;
    };
    const card = m => `<article class="mc ${m.stok ? '' : 'off'} ${qtyOf(m.id) ? 'in' : ''} ${m.id === sel ? 'picked' : ''}" data-id="${m.id}"><div class="mp ${tint(m.kategori)}" data-pick="${m.id}">${art(m)}</div><div class="mt" data-pick="${m.id}" role="button" tabindex="0" aria-label="Lihat ${esc(m.nama)} lebih besar"><b class="mn">${esc(m.nama)}</b><span class="mcat">${esc(m.kategori)}</span></div><div class="mfoot"><span class="mpr">${rp(m.harga)}</span><div class="mact">${ctl(m)}</div></div></article>`;
    const list = () => Menu.get().filter(m => (!uc || m.kategori === uc) && (!uq || m.nama.toLowerCase().includes(uq)));
    const split = n => { const w = n.trim().split(/\s+/); return w.length > 1 ? [w[0], w.slice(1).join(' ')] : ['', w[0]]; };
    const setBuy = m => {
      $('#k-qty').textContent = qty; const b = $('#k-add'), ok = m && m.stok && open(); b.disabled = !ok;
      b.innerHTML = !open() ? 'Toko sedang tutup' : !m || !m.stok ? 'Menu habis' : `<span>Tambah<span class="bl2"> ke Keranjang</span> · ${rp(m.harga * qty)}</span>`;
    };
    const fill = m => {
      $('#plate-in').innerHTML = art(m); $('#plate').className = 'plate ' + tint(m.kategori) + (m.stok ? '' : ' off'); $('#k-desc').textContent = m.desk || ''; $('#k-desc').hidden = !m.desk; $('#soldout').hidden = !!m.stok;
      const [a, b] = split(m.nama); $('#k-l1').textContent = a; $('#k-l1').hidden = !a; $('#k-l2').textContent = b;
      $('#k-cat').textContent = m.kategori; $('#k-price').textContent = rp(m.harga); $('#k-avail').textContent = 'Maaf, menu ini sedang habis'; $('#k-avail').hidden = !!m.stok; setBuy(m);
    };
    const animOut = (el, dir, ms) => el.animate([{ transform: 'translateX(0) scale(1)', opacity: 1 }, { transform: `translateX(${-dir * 14}%) scale(.96)`, opacity: 0 }], { duration: ms, easing: 'ease-in', fill: 'forwards' }).finished;
    const animIn = (el, dir, ms) => el.animate([{ transform: `translateX(${dir * 14}%) scale(.96)`, opacity: 0 }, { transform: 'translateX(0) scale(1)', opacity: 1 }], { duration: ms, easing: 'cubic-bezier(.2,.8,.3,1)', fill: 'both' }).finished;
    const textAnim = (dir, out) => $$('#info .mname,#info .price,#info .kicker,#info .avail').forEach((e, i) => e.animate(out ? [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: `translateX(${-dir * 24}px)` }] : [{ opacity: 0, transform: `translateX(${dir * 24}px)` }, { opacity: 1, transform: 'none' }], { duration: out ? 180 : 320, delay: out ? 0 : i * 40, easing: 'ease', fill: 'both' }));
    async function select(id, dir, animate) {
      const L = list(), i = L.findIndex(x => x.id === id); if (i < 0) return; const m = L[i], my = ++token, prev = L.findIndex(x => x.id === sel);
      sel = id; qty = 1; $$('.mc').forEach(c => c.classList.toggle('picked', +c.dataset.id === id));
      const pi = $('#plate-in'); if (!animate || reduce() || !pi.animate) { fill(m); return; }
      dir = dir || (i >= prev ? 1 : -1);
      textAnim(dir, true); await animOut(pi, dir, 160).catch(() => {}); if (my !== token) return;
      fill(m); textAnim(dir, false); animIn(pi, dir, 320);
    }
    const step = d => { const L = list(); if (!L.length) return; const i = L.findIndex(x => x.id === sel); select(L[(i + d + L.length) % L.length].id, d, true); };
    const intro = () => {
      const c = Cfg.get(), el = $('#intro'); el.classList.toggle('closed', !open());
      el.innerHTML = open() ? `Ambil di toko, atau diantar dengan ongkir ${rp(c.ongkir || 0)}.` : 'Toko sedang tutup. Anda masih bisa melihat menu.';
    };
    const draw = () => {
      const all = Menu.get(), cats = ['', ...new Set(all.map(m => m.kategori))];
      $('#u-tabs').innerHTML = cats.map(c => `<button type="button" role="tab" class="tab ${c === uc ? 'on' : ''}" data-uc="${esc(c)}">${c || 'Semua'}</button>`).join('');
      const L = all.filter(m => (!uc || m.kategori === uc) && (!uq || m.nama.toLowerCase().includes(uq)));
      $('#u-grid').innerHTML = L.length ? L.map(card).join('') : '<div class="uempty">Menu tidak ditemukan.</div>';
      $('#stage').hidden = !L.length; $('#seemore').innerHTML = 'Lihat semua ' + L.length + ' menu <span aria-hidden="true">↓</span>'; $('.split').classList.toggle('nostage', !L.length); ['#p-prev', '#p-next'].forEach(q => $(q).disabled = L.length < 2);
      if (L.length && !L.some(x => x.id === sel)) { sel = L[0].id; qty = 1; fill(L[0]); $$('.mc')[0].classList.add('picked'); }
    };
    const refresh = (id, pop) => {
      cartBadge(); const m = Menu.get().find(x => x.id === id), el = $(`.mc[data-id="${id}"]`); if (!m || !el) return;
      el.className = `mc ${m.stok ? '' : 'off'} ${qtyOf(id) ? 'in' : ''}`; $('.mact', el).innerHTML = ctl(m);
      if (pop && !reduce()) { const p = $('.mp', el); p.classList.remove('pop'); void p.offsetWidth; p.classList.add('pop'); const f = document.createElement('span'); f.className = 'fly'; f.textContent = '+1'; $('.mact', el).appendChild(f); setTimeout(() => f.remove(), 850); const h = $('.cf-btn'); if (h) { h.classList.remove('bump'); void h.offsetWidth; h.classList.add('bump'); } }
      const nb = $(`.mact [data-inc="${id}"]`, el) || $(`.mact [data-dec="${id}"]`, el); if (nb && pop) { const k = $(`.mact [data-inc="${id}"]`, el); if (k) k.focus({ preventScroll: true }); }
    };
    const change = (id, d) => {
      const m = Menu.get().find(x => x.id === id); if (!m || !m.stok || !open()) return;
      const c = Cart.get(), it = c.find(x => x.id === id);
      if (it) { it.qty = Math.min(50, it.qty + d); if (it.qty < 1) c.splice(c.indexOf(it), 1); } else if (d > 0) c.push({ id: m.id, nama: m.nama, harga: m.harga, qty: 1 });
      Cart.set(c); refresh(id, d > 0);
    };
    intro(); draw(); cartBadge();
    $('#p-prev').addEventListener('click', () => step(-1)); $('#p-next').addEventListener('click', () => step(1));
    $('#q-min').addEventListener('click', () => { qty = Math.max(1, qty - 1); setBuy(list().find(x => x.id === sel)); }); $('#q-plus').addEventListener('click', () => { qty = Math.min(20, qty + 1); setBuy(list().find(x => x.id === sel)); });
    document.addEventListener('keydown', e => { if (e.target.closest('input,textarea,select')) return; if (e.key === 'ArrowLeft') step(-1); if (e.key === 'ArrowRight') step(1); const t = e.target.closest('.mt[data-pick]'); if (t && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); select(+t.dataset.pick, 0, true); } });
    let sx = null; const st = $('#stage'); st.addEventListener('pointerdown', e => { sx = e.clientX; }); st.addEventListener('pointerup', e => { if (sx == null) return; const d = e.clientX - sx; sx = null; if (Math.abs(d) > 60) step(d < 0 ? 1 : -1); });
    $('#u-cari').addEventListener('input', e => { uq = e.target.value.trim().toLowerCase(); draw(); });
    document.addEventListener('click', e => {
      if (e.target.closest('#seemore')) { const f = $('.filters'); if (f) f.scrollIntoView({ behavior: 'smooth', block: 'start' }); return; }
      const t = e.target.closest('[data-uc]'); if (t) { uc = t.dataset.uc; draw(); return; }
      const pk = e.target.closest('[data-pick]'); if (pk) { const id = +pk.dataset.pick; if (id !== sel) select(id, 0, true); if (innerWidth < 1000) $('#stage').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' }); return; }
      if (e.target.closest('#k-add')) { const m = list().find(x => x.id === sel); if (m && m.stok && open()) { const c = Cart.get(), it = c.find(x => x.id === m.id); if (it) it.qty = Math.min(50, it.qty + qty); else c.push({ id: m.id, nama: m.nama, harga: m.harga, qty }); Cart.set(c); toast(`${qty}× ${m.nama} masuk keranjang`); qty = 1; setBuy(m); refresh(m.id, true); } return; }
      const a = e.target.closest('[data-inc]'); if (a) { change(+a.dataset.inc, 1); return; }
      const d = e.target.closest('[data-dec]'); if (d) change(+d.dataset.dec, -1);
    });
    window.addEventListener('storage', () => { draw(); cartBadge(); });
    syncCartNote(); cartBadge();
    pollState(() => { syncCartNote(); intro(); paintOpen(); paintFoot(); draw(); cartBadge(); const m = list().find(x => x.id === sel); if (m) fill(m); });
  }
  if (PAGE === 'ukeranjang') {
    const draw = () => {
      const c = Cart.get(), box = $('#cartlines'), em = id => (Menu.get().find(m => m.id === id) || {}).emoji || '🍽️';
      box.innerHTML = c.length ? c.map((i, ix) => `<div class="cline"><span class="cth">${esc(em(i.id))}</span><div class="cmeta"><b>${esc(i.nama)}</b><span>${rp(i.harga)} per porsi</span></div><div class="qty big"><button type="button" data-cq="${ix}" data-d="-1" aria-label="Kurangi ${esc(i.nama)}">−</button><b>${i.qty}</b><button type="button" data-cq="${ix}" data-d="1" aria-label="Tambah ${esc(i.nama)}">+</button></div><button type="button" class="crm" data-cq="${ix}" data-d="-999" aria-label="Hapus ${esc(i.nama)}">Hapus</button><b class="csub">${rp(i.qty * i.harga)}</b></div>`).join('') : '<div class="cempty"><span class="cempty-ic" aria-hidden="true">🛍️</span><h2>Keranjang masih kosong</h2><p>Belum ada menu yang dipilih. Yuk lihat menu hari ini dan pilih jajanan favorit Anda.</p><a class="btn primary xlg" href="/">Lihat menu</a><a class="cempty-lnk" href="/transaksi">Cek pesanan saya</a></div>';
      $('#sub').textContent = rp(Cart.sub()); $('#cartfoot').hidden = !c.length; cartBadge(); $('.u2').classList.toggle('cart-empty', !c.length);
    };
    syncCartNote(); draw();
    pollState(() => { syncCartNote(); paintOpen(); paintFoot(); draw(); });
    document.addEventListener('click', e => {
      const q = e.target.closest('[data-cq]'); if (q) { const c = Cart.get(), it = c[+q.dataset.cq]; it.qty += +q.dataset.d; if (it.qty < 1) c.splice(+q.dataset.cq, 1); Cart.set(c); draw(); }
      if (e.target.closest('#cart-clear')) { Cart.set([]); draw(); }
      if (e.target.closest('#to-pay') && !Cart.count()) { e.preventDefault(); toast('Keranjang masih kosong', true); }
    });
  }
  if (PAGE === 'ukonfirmasi') {
    if (!Cart.count()) { location.replace('/keranjang'); return; }
    syncCartNote(); if (!Cart.count()) { location.replace('/keranjang'); return; }
    const f = $('#coform');
    const paintItems = () => { $('#c-items').innerHTML = Cart.get().map(i => `<div class="kv"><span>${i.qty}× ${esc(i.nama)}</span><b>${rp(i.qty * i.harga)}</b></div>`).join(''); };
    paintItems();
    const pf = Profile.get(); if (pf.nama) $('#n').value = pf.nama; if (pf.telp) $('#w').value = pf.telp;
    const qrOk = !!Cfg.get().qris, qr = $('input[name=bayar][value=QRIS]', f);
    $('#c-qrbox').innerHTML = qrOk ? `<img src="${esc(Cfg.get().qris)}" alt="Kode QRIS ${esc(Cfg.get().toko)}">` : '';
    const qrSmall = $('small', qr.closest('label')), tunaiR = $('input[name=bayar][value=Tunai]', f);
    $('#qris-kasir').hidden = true;
    /* tanpa gambar QRIS: pembayaran QRIS dilakukan di kasir (hanya untuk ambil di toko) */
    const applyQris = () => {
      const ant = $('input[name=jenis]:checked', f).value === 'Antar';
      if (qrOk) { qrSmall.textContent = 'Scan lalu bayar'; qr.disabled = false; return; }
      if (ant) { qr.disabled = true; qrSmall.textContent = 'Khusus ambil di toko'; if (qr.checked) { tunaiR.checked = true; tunaiR.dispatchEvent(new Event('change', { bubbles: true })); } }
      else { qr.disabled = false; qrSmall.textContent = 'Bayar & scan di kasir'; }
    };
    $$('input[name=jenis]', f).forEach(r => r.addEventListener('change', applyQris)); applyQris();
    const tot = () => { const ant = $('input[name=jenis]:checked', f).value === 'Antar', ong = ant ? +Cfg.get().ongkir || 0 : 0; $('#c-sub').textContent = rp(Cart.sub()); $('#c-ongkir').textContent = ant ? rp(ong) : 'Gratis (ambil di toko)'; $('#c-total').textContent = rp(Cart.sub() + ong); $('#alamat').hidden = !ant; if (typeof drawMap === 'function') drawMap(ant); return ong; };
    /* peta: ambil -> lokasi dapur; antar -> cari alamat + pin lokasi rumah */
    const K = MapKit.kitchen(), mapOk = MapKit.ok(), aIn = $('#a'); let mp = null, uMk = null, line = null, pt = null, autoAddr = true;
    aIn.addEventListener('input', () => { autoAddr = false; if (aIn.value.trim()) { setErr(f, 'a', false); mark(aIn, false); } });
    const fillAddr = t => { if (t && (autoAddr || !aIn.value.trim())) { aIn.value = t; autoAddr = true; setErr(f, 'a', false); mark(aIn, false); } };
    const info = () => { const d = pt ? MapKit.km(K, pt) : null; $('#m-info').innerHTML = pt ? `Jarak sekitar <b>${d.toFixed(1)} km</b> dari dapur.` + (K.radius && d > K.radius ? ` <span class="warn">Di luar area antar ${K.radius} km; Pemilik akan mengonfirmasi lewat WhatsApp.</span>` : '') : 'Belum ada lokasi dipilih. Cari alamat, ketuk peta, atau pakai lokasi saya.'; const g = $('#m-gmaps'); g.hidden = !pt; if (pt) g.href = MapKit.gmaps(pt); };
    const setPoint = (lat, lng, rev, addr, zoom) => {
      pt = { lat: +lat.toFixed(6), lng: +lng.toFixed(6) };
      if (!uMk) { uMk = L.marker([lat, lng], { icon: MapKit.icon.u(), draggable: true, title: 'Lokasi pengantaran (geser untuk memindah)', alt: 'Penanda lokasi pengantaran' }).addTo(mp); uMk.on('dragend', () => { const q = uMk.getLatLng(); setPoint(q.lat, q.lng, true); }); } else uMk.setLatLng([lat, lng]);
      if (!line) line = L.polyline([[K.lat, K.lng], [lat, lng]], { color: '#5B21B6', weight: 3, dashArray: '6 8' }).addTo(mp); else line.setLatLngs([[K.lat, K.lng], [lat, lng]]);
      if (zoom) mp.setView([lat, lng], zoom); setErr(f, 'm', false); info();
      if (addr) fillAddr(addr); else if (rev) MapKit.reverse(pt).then(fillAddr);
    };
    const initMap = () => {
      mp = MapKit.create($('#map'), K, 15);
      L.marker([K.lat, K.lng], { icon: MapKit.icon.k(), title: K.toko, keyboard: false }).addTo(mp).bindTooltip(K.toko, { direction: 'top', offset: [0, -22] });
      mp.on('click', e => { if ($('input[name=jenis]:checked', f).value === 'Antar') setPoint(e.latlng.lat, e.latlng.lng, true); });
      MapKit.bindSearch({ q: $('#m-q'), go: $('#m-go'), res: $('#m-res'), note: $('#m-note'), near: () => K, onPick: x => setPoint(x.lat, x.lng, false, x.nama, 17) });
      $('#m-loc').addEventListener('click', () => {
        if (!navigator.geolocation) { toast('Perangkat ini tidak mendukung lokasi', true); return; }
        toast('Mencari lokasi Anda…'); navigator.geolocation.getCurrentPosition(p => setPoint(p.coords.latitude, p.coords.longitude, true, '', 17), () => toast('Lokasi tidak dapat diakses. Izinkan lokasi atau cari alamat.', true), { enableHighAccuracy: true, timeout: 10000 });
      });
    };
    const drawMap = ant => {
      $('#mapwrap').hidden = !mapOk; $('#m-nomap').hidden = mapOk; if (!mapOk) return;
      $('#m-ambil').hidden = ant; $('#m-antar').hidden = !ant; $('#m-info').hidden = !ant; $('#m-loc').hidden = !ant; $('#m-gmaps').hidden = !ant || !pt;
      if (!mp) initMap();
      $('#m-dapur').hidden = ant; if (uMk) { ant ? uMk.addTo(mp) : uMk.remove(); } if (line) { ant ? line.addTo(mp) : line.remove(); }
      setTimeout(() => { mp.invalidateSize(); if (ant && pt) mp.fitBounds([[K.lat, K.lng], [pt.lat, pt.lng]], { padding: [40, 40], maxZoom: 16 }); else mp.setView([K.lat, K.lng], 16); }, 80);
      if (ant) info();
    };
    $('#m-dapur-al').textContent = K.alamat || 'Alamat dapur belum diatur'; $('#m-dapur-rute').href = MapKit.route(K);
    $$('input[name=jenis]', f).forEach(r => r.addEventListener('change', tot)); tot();
    $$('input[name=bayar]', f).forEach(r => r.addEventListener('change', () => { const on = $('input[name=bayar]:checked', f).value === 'QRIS'; $('#qris').hidden = !on; $('#c-qrbox').hidden = !qrOk; $('#qris-kasir').hidden = qrOk; $('#qris-scan').hidden = !qrOk; }));
    f.addEventListener('submit', async e => {
      e.preventDefault(); if (!open()) { toast('Toko sedang tutup', true); return; }
      const nama = $('#n').value.trim(), wa = $('#w').value.replace(/\D/g, ''), jenis = $('input[name=jenis]:checked', f).value, alamat = $('#a').value.trim();
      const okN = !!nama, okW = wa.length >= 9 && wa.length <= 14, okA = jenis !== 'Antar' || alamat.length >= 8; const okM = jenis !== 'Antar' || !mapOk || !!pt; setErr(f, 'm', !okM);
      setErr(f, 'n', !okN); mark($('#n'), !okN); setErr(f, 'w', !okW); mark($('#w'), !okW); setErr(f, 'a', !okA); mark($('#a'), !okA); if (!okN || !okW || !okA || !okM) { const bad = !okN ? '#n' : !okW ? '#w' : !okM ? '#mapwrap' : '#a'; const el = $(bad); el.scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' }); if (el.focus && bad !== '#mapwrap') el.focus({ preventScroll: true }); return; }
      const bayar = $('input[name=bayar]:checked', f).value, btn = $('#c-submit'), label = btn.textContent; btn.disabled = true; btn.textContent = 'Mengirim pesanan…';
      try {
        const j = await Api.post('/api/orders', { nama, telp: $('#w').value.trim(), jenis, alamat: jenis === 'Antar' ? alamat : '', lat: jenis === 'Antar' && pt ? pt.lat : null, lng: jenis === 'Antar' && pt ? pt.lng : null, catatan: $('#c').value.trim(), bayar, items: Cart.get().map(i => ({ id: i.id, qty: i.qty })) });
        My.add(j.order.kode, j.token); Profile.set({ nama, telp: $('#w').value.trim() }); Cart.set([]); location.href = '/berhasil?k=' + encodeURIComponent(j.order.kode);
      } catch (x) {
        btn.disabled = false; btn.textContent = label; fail(x);
        const em = { nama: 'n', telp: 'w', alamat: 'a' }; Object.keys(x.errors || {}).forEach(k => { if (em[k]) { setErr(f, em[k], true); mark($('#' + em[k]), true); } });
        if (x.status === 409) { await pullState(); paintOpen(); syncCartNote(); if (!Cart.count()) { location.replace('/keranjang'); return; } paintItems(); tot(); }
      }
    });
  }
  /* ---------- Pesanan berhasil ---------- */
  if (PAGE === 'usukses') {
    const box = $('#ok'), kode = new URLSearchParams(location.search).get('k') || '';
    const render = o => {
      const c = Cfg.get(), est = +c.estimasi || 0, qr = o.bayar === 'QRIS' && o.status === 'Belum dibayar';
      const next = qr ? [c.qris ? 'Scan kode QRIS di bawah, lalu bayar <b>tepat ' + rp(o.total) + '</b>.' : 'Datang ke kasir, sebutkan kode <b>' + esc(o.kode) + '</b>, lalu scan QRIS di sana.', 'Pemilik akan memeriksa pembayaran, lalu pesanan Anda mulai dibuat.', 'Pantau statusnya di menu Transaksi.']
        : o.bayar === 'QRIS' ? ['Pembayaran QRIS Anda sudah tercatat.', 'Pantau statusnya di menu Transaksi.']
        : o.jenis === 'Antar' ? ['Pesanan Anda sudah diterima toko.', 'Siapkan uang tunai <b>' + rp(o.total) + '</b> untuk dibayar saat pesanan tiba.', 'Pantau statusnya di menu Transaksi.']
        : ['Pesanan Anda sudah diterima toko.', 'Bayar tunai <b>' + rp(o.total) + '</b> saat mengambil di toko.', 'Pantau statusnya di menu Transaksi.'];
      box.innerHTML = `<div class="ok-top"><span class="ok-ic" aria-hidden="true">${ICON.check}</span><h1 class="ptitle" id="ok-h" tabindex="-1">Pesanan berhasil dibuat</h1><p class="muted lead">Terima kasih, ${esc(o.nama)}. Simpan kode pesanan ini.</p>
        <div class="ok-code"><span>Kode pesanan</span><b id="ok-kode">${esc(o.kode)}</b><div class="ok-codebtns"><button type="button" class="btn sm" id="ok-copy">Salin kode</button><a class="btn sm" target="_blank" rel="noopener" href="${esc(selfWa(o))}">Kirim kode ke WhatsApp saya</a></div></div></div>
        ${est && o.status !== 'Belum dibayar' ? `<p class="ok-est">Perkiraan siap sekitar pukul <b>${clockOf(o.dibuat + est * 60)}</b> (± ${est} menit).</p>` : est ? `<p class="ok-est">Setelah pembayaran dikonfirmasi, pesanan siap sekitar ${est} menit.</p>` : ''}
        ${qr ? dueHtml(o) : ''}
        <div class="ucard ok-next"><h2>Langkah selanjutnya</h2><ol>${next.map(t => `<li>${t}</li>`).join('')}</ol>
          ${qr ? (c.qris ? `<div class="qrbox big"><img src="${esc(c.qris)}" alt="Kode QRIS ${esc(c.toko)}"></div><p class="hint" style="text-align:center">Nominal: <b>${rp(o.total)}</b></p>` : `<div class="ucard kasirbox"><b>Bayar di kasir</b><p>Datang ke kasir, sebutkan kode <b>${esc(o.kode)}</b>, lalu scan QRIS di sana sebesar <b>${rp(o.total)}</b>.</p></div>`) : ''}
          ${o.jenis === 'Ambil' ? `<p class="ok-addr"><b>Ambil di:</b> ${esc(c.alamat || 'alamat dapur belum diatur')}</p>` : `<p class="ok-addr"><b>Diantar ke:</b> ${esc(o.alamat)}</p>`}</div>
        <div class="ucard ok-sum"><h2>Ringkasan</h2>${o.items.map(i => `<div class="kv"><span>${i.qty}× ${esc(i.nama)}</span><b>${rp(i.qty * i.harga)}</b></div>`).join('')}${o.ongkir ? `<div class="kv"><span class="muted">Ongkir</span><b>${rp(o.ongkir)}</b></div>` : ''}<div class="kv big"><b>Total</b><b>${rp(o.total)}</b></div></div>
        <div class="ok-acts"><a class="btn primary xlg" href="/transaksi">Lihat status pesanan</a><a class="btn xlg" target="_blank" rel="noopener" href="${esc(waLink('Halo ' + c.toko + ', saya mau tanya pesanan ' + o.kode))}">Tanya lewat WhatsApp</a></div>`;
      $('#ok-copy').addEventListener('click', async () => { try { await navigator.clipboard.writeText(o.kode); toast('Kode disalin'); } catch (e) { toast('Catat kode: ' + o.kode); } });
      const h = $('#ok-h'); if (h) h.focus({ preventScroll: true });
    };
    (async () => { try { await My.load(); } catch (e) { /* lihat di bawah */ } const o = Orders.find(kode); if (!o) { location.replace('/transaksi'); return; } render(o); })();
  }

  /* ---------- Transaksi (pesanan milik perangkat ini) ---------- */
  if (PAGE === 'utransaksi') {
    let loaded = false;
    const SAY = { 'Belum dibayar': ['Silakan bayar lewat QRIS. Pesanan diproses setelah pemilik mengonfirmasi pembayaran Anda.', ''], 'Diterima': ['Pesanan Anda sudah diterima toko.', ''], 'Diproses': ['Pesanan Anda sedang dibuat.', ''], 'Siap diambil': ['Pesanan siap! Silakan ambil di toko.', 'okk'], 'Diantar': ['Pesanan sedang diantar ke alamat Anda.', ''], 'Selesai': ['Pesanan selesai. Terima kasih sudah memesan!', 'okk'], 'Dibatalkan': ['Pesanan ini dibatalkan.', 'bad'] };
    const prog = o => {
      if (o.status === 'Dibatalkan') return '';
      const steps = ['Diterima', 'Diproses', readyOf(o), 'Selesai'], cur = steps.indexOf(o.status);
      return `<ol class="prog" aria-label="Tahap pesanan">${steps.map((s, i) => `<li class="${i < cur ? 'done' : i === cur ? 'now' : ''}">${s}</li>`).join('')}</ol>`;
    };
    const eta = o => { const e = +Cfg.get().estimasi || 0; return e && ['Diterima', 'Diproses'].includes(o.status) ? `<p class="eta">Perkiraan siap sekitar pukul <b>${clockOf(o.dibuat + e * 60)}</b>.</p>` : ''; };
    const done = o => ['Selesai', 'Dibatalkan'].includes(o.status);
    Lists.trx = {
      data: st => Orders.all().filter(o => !st.f.tab || (st.f.tab === 'aktif' ? !done(o) : done(o))).sort((a, b) => b.dibuat - a.dibuat || (a.kode < b.kode ? 1 : -1)),
      row: o => `<article class="trx ucard"><div class="th2"><b>${esc(o.kode)}</b><span class="chip ${statusCls(o.status)}">${o.status}</span></div><p class="meta">${esc(o.tgl)} · ${esc(o.jam)} · ${o.jenis === 'Antar' ? 'Diantar' : 'Ambil di toko'} · ${o.bayar}</p><p class="its">${o.items.map(i => i.qty + '× ' + esc(i.nama)).join(', ')}</p>${prog(o)}<p class="say ${SAY[o.status][1]}" style="margin-top:12px">${SAY[o.status][0]}</p>${dueHtml(o)}${eta(o)}${o.diubah && o.status !== 'Dibatalkan' ? `<p class="say vio" style="margin-top:10px">Toko sudah mengubah isi pesanan ini. Total terbaru ada di bawah.${o.selisih > 0 ? ` Ada kekurangan pembayaran ${rp(o.selisih)}, dibayar saat pesanan diserahkan.` : o.selisih < 0 ? ` Ada kelebihan pembayaran ${rp(-o.selisih)}, akan dikembalikan oleh toko.` : ''}</p>` : ''}<div class="tot"><span>Total</span><span>${rp(o.total)}</span></div><div class="tacts">${o.bayar === 'QRIS' && o.status === 'Belum dibayar' ? `<button type="button" class="btn primary" data-uqris="${esc(o.kode)}">Bayar dengan QRIS</button>` : ''}${done(o) ? `<button type="button" class="btn primary" data-urepeat="${esc(o.kode)}">Pesan lagi</button>` : ''}<a class="btn" target="_blank" rel="noopener" href="${esc(waLink('Halo ' + Cfg.get().toko + ', saya mau tanya pesanan ' + o.kode))}">Tanya pesanan ini</a>${NEED.includes(o.status) ? `<button type="button" class="btn danger" data-ucancel="${esc(o.kode)}">Batalkan pesanan</button>` : ''}</div></article>`,
      empty: st => !loaded ? 'Memuat pesanan Anda…' : !Orders.all().length
        ? `<span class="em0" aria-hidden="true">🧾</span><b>Belum ada pesanan di perangkat ini</b><br>Pesanan yang Anda buat dari perangkat ini akan muncul di sini. Sudah pernah memesan dari perangkat lain? Masukkan kode pesanan di atas.<br><a class="btn primary xlg" style="margin-top:16px" href="/">Pesan sekarang</a>`
        : st.f.tab === 'aktif' ? 'Tidak ada pesanan yang sedang berjalan.' : 'Belum ada pesanan yang selesai.'
    };
    let ucan = null;
    document.addEventListener('click', e => {
      const b = e.target.closest('[data-ucancel]'); if (b) { ucan = b.dataset.ucancel; openO('ubatal'); }
      const q = e.target.closest('[data-uqris]'); if (q) { const o = Orders.find(q.dataset.uqris), u = Cfg.get().qris; $('#uq-box').innerHTML = u ? `<img src="${esc(u)}" alt="Kode QRIS ${esc(Cfg.get().toko)}">` : '<span>QRIS belum tersedia. Hubungi toko lewat WhatsApp.</span>'; $('#uq-nom').textContent = rp(o.total); $('#uq-kode').textContent = o.kode; openO('uqris'); }
      const rp2 = e.target.closest('[data-urepeat]');
      if (rp2) {
        const o = Orders.find(rp2.dataset.urepeat); if (!o) return; const c = Cart.get(); let n = 0, skip = [];
        o.items.forEach(i => { const m = Menu.get().find(x => x.nama === i.nama); if (!m || !m.stok) { skip.push(i.nama); return; } const it = c.find(x => x.id === m.id); if (it) it.qty = Math.min(50, it.qty + i.qty); else c.push({ id: m.id, nama: m.nama, harga: m.harga, qty: i.qty }); n++; });
        if (!n) { toast('Menu pada pesanan ini sedang tidak tersedia', true); return; }
        Cart.set(c); location.href = '/keranjang';
      }
    });
    $('#ubatal-ok').addEventListener('click', async () => {
      const b = $('#ubatal-ok'); b.disabled = true;
      try { Orders.put((await Api.post('/api/orders/' + encodeURIComponent(ucan) + '/cancel', { token: My.token(ucan) })).order); toast('Pesanan dibatalkan'); }
      catch (x) { fail(x); try { await My.load(); } catch (y) { /* abaikan */ } }
      closeO($('#ubatal')); b.disabled = false; renderList('trx');
    });
    $$('#t-tabs .tab').forEach(t => t.addEventListener('click', () => { $$('#t-tabs .tab').forEach(x => x.classList.remove('on')); t.classList.add('on'); Lists.trx.state.f.tab = t.dataset.f; Lists.trx.state.page = 1; renderList('trx'); }));
    // muat pesanan milik perangkat ini, lalu perbarui otomatis tiap 5 detik
    let sig = '';
    const load = async (force) => { try { await My.load(); } catch (e) { if (!loaded) { loaded = true; renderList('trx'); } return; } loaded = true; const n = JSON.stringify(Orders.d) + JSON.stringify(Cfg.get().estimasi) + Math.floor(nowSec() / 60); if (force || n !== sig) { sig = n; renderList('trx'); } };
    load(true); setInterval(() => { if (!document.hidden) load(); }, 5000); document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });
    pollState(() => { paintOpen(); paintFoot(); renderList('trx'); });
    const tf = $('#t-find');
    if (tf) tf.addEventListener('submit', async e => {
      e.preventDefault(); const raw = $('#t-kode').value.trim().toUpperCase().replace(/\s+/g, ''), t4 = $('#t-telp').value.replace(/\D/g, ''), er = $('#t-err');
      const kode = /^\d+$/.test(raw) ? 'SNK-' + raw.padStart(3, '0') : raw, bad = m => { er.textContent = m; er.classList.add('on'); };
      if (!/^SNK-\d{3,}$/.test(kode)) { bad('Kode pesanan tidak valid. Contoh: SNK-345'); return; }
      if (t4.length !== 4) { bad('Isi 4 angka terakhir nomor WhatsApp yang dipakai saat memesan.'); return; }
      const btn = $('button[type=submit]', tf); btn.disabled = true;
      try { const j = await Api.post('/api/orders/lookup', { kode, telp4: t4 }); er.classList.remove('on'); My.add(j.order.kode, j.token); await My.load(); sig = ''; $('#t-kode').value = ''; $('#t-telp').value = ''; Lists.trx.state.page = 1; renderList('trx'); toast('Pesanan ' + j.order.kode + ' ditemukan'); }
      catch (x) { bad(x.status === 404 ? 'Kode atau 4 angka terakhir WhatsApp tidak cocok. Periksa kembali pada struk atau pesan WhatsApp.' : x.message); }
      finally { btn.disabled = false; }
    });
  }

  /* ---------- Pemberitahuan pesanan baru (Pemilik): tanya server tiap 6 detik ---------- */
  if (AREA === 'admin' && PAGE !== 'login') {
    const need = () => Orders.all().filter(o => NEED.includes(o.status)).length;
    const base = document.title.replace(/^\(\d+\)\s*/, '');
    const known = new Set(Orders.all().map(o => o.kode));
    const beep = () => { try { const A = window.AudioContext || window.webkitAudioContext, c = new A(), o = c.createOscillator(), g = c.createGain(); o.connect(g); g.connect(c.destination); o.frequency.value = 880; g.gain.setValueAtTime(.0001, c.currentTime); g.gain.exponentialRampToValueAtTime(.15, c.currentTime + .02); g.gain.exponentialRampToValueAtTime(.0001, c.currentTime + .5); o.start(); o.stop(c.currentTime + .5); } catch (e) { /* diabaikan */ } };
    const badge = () => { const n = need(), b = $('#nb-pesanan'); if (b) { b.textContent = n; b.hidden = !n; } document.title = (n ? '(' + n + ') ' : '') + base; };
    const stamp = () => JSON.stringify(Orders.all()) + (need() ? Math.floor(nowSec() / 60) : '');
    const today = $('#today'); if (today) today.textContent = new Date(nowSec() * 1000).toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'long', year: 'numeric' });
    const check = async () => {
      if (document.hidden) return;
      const before = stamp(); await pullOrders();
      const fresh = Orders.all().filter(o => !known.has(o.kode)); fresh.forEach(o => known.add(o.kode));
      const online = fresh.filter(o => o.sumber === 'online');
      if (online.length) { toast('Pesanan baru ' + online[0].kode + ' dari ' + online[0].nama + (online.length > 1 ? ' (+' + (online.length - 1) + ' lagi)' : '')); beep(); }
      if (stamp() !== before) renderAll();
      badge();
    };
    badge(); setInterval(check, 6000); document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
  }

  /* ---------- Mulai ---------- */
  Object.keys(Lists).forEach(initList);
  renderHabis(); renderAll();
})();
