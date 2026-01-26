<x-layouts.app>
  <section class="max-w-7xl mx-auto py-12 px-6">
    {{-- Breadcrumb --}}
    <nav class="mb-6">
      <div class="breadcrumbs">
        <ul>
          <li><a href="{{ route('home') }}" class="link link-neutral">Beranda</a></li>
          <li><a href="#" class="link link-neutral">Event</a></li>
          <li>{{ $event->judul }}</li>
        </ul>
      </div>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      {{-- kiri --}}
      <div class="lg:col-span-2">
        <div class="card bg-base-100 shadow">
          <figure>
            <img
              src="{{ $event->gambar ? asset('storage/' . $event->gambar) : 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp' }}"
              class="w-full h-96 object-cover"
              alt="{{ $event->judul }}"
            />
          </figure>

          <div class="card-body">
            <h1 class="text-3xl font-extrabold">{{ $event->judul }}</h1>
            <p class="text-sm text-gray-500">
              {{ $event->tanggal_waktu->translatedFormat('d F Y, H:i') }} • 📍 {{ $event->lokasi }}
            </p>

            <div class="mt-3 flex gap-2">
              <span class="badge badge-primary">{{ $event->kategori?->nama }}</span>
              <span class="badge">{{ $event->user?->name }}</span>
            </div>

            <p class="mt-4">{{ $event->deskripsi }}</p>

            <div class="divider"></div>

            <h3 class="text-xl font-bold">Pilih Tiket</h3>

            @forelse($event->tickets as $tiket)
              <div class="card card-side p-4 shadow-sm mt-3">
                <div class="flex-1">
                  <h4 class="font-bold">{{ $tiket->tipe }}</h4>
                  <p class="text-sm text-gray-500">Stok: {{ $tiket->stok }}</p>
                </div>

                <div class="w-44 text-right">
                  <div class="font-bold">
                    {{ $tiket->harga ? 'Rp '.number_format($tiket->harga,0,',','.') : 'Gratis' }}
                  </div>

                  <div class="mt-2 flex justify-end gap-2">
                    <button class="btn btn-sm" data-action="dec" data-id="{{ $tiket->id }}">−</button>
                    <input id="qty-{{ $tiket->id }}" type="number" min="0" max="{{ $tiket->stok }}"
                      value="0" class="input input-bordered w-16 text-center" />
                    <button class="btn btn-sm" data-action="inc" data-id="{{ $tiket->id }}">+</button>
                  </div>

                  <div class="text-sm mt-1">
                    Subtotal: <span id="subtotal-{{ $tiket->id }}">Rp 0</span>
                  </div>
                </div>
              </div>
            @empty
              <div class="alert alert-info">Tiket belum tersedia</div>
            @endforelse
          </div>
        </div>
      </div>

      {{-- kanan --}}
      <aside class="lg:col-span-1">
        <div class="card sticky top-24 p-4 shadow">
          <h4 class="font-bold text-lg">Ringkasan</h4>

          <div class="mt-2">
            <div class="flex justify-between">
              <span>Item</span><span id="summaryItems">0</span>
            </div>
            <div class="flex justify-between font-bold">
              <span>Total</span><span id="summaryTotal">Rp 0</span>
            </div>
          </div>

          <div class="divider"></div>

          <div id="selectedList" class="text-sm text-gray-500">
            Belum ada tiket
          </div>

          <button id="checkoutButton"
            class="btn btn-primary !bg-blue-900 text-white btn-block mt-6"
            onclick="openCheckout()" disabled>
            Checkout
          </button>
        </div>
      </aside>
    </div>

    <!-- Checkout Modal -->
    <dialog id="checkout_modal" class="modal">
      <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-4">Konfirmasi Pembelian</h3>
        <!-- Items -->
        <div id="modalItems" class="space-y-2 text-sm text-gray-700">
          <p class="text-gray-500">Belum ada item.</p>
        </div>
        <div class="divider my-4"></div>
        <!-- Total -->
        <div class="flex justify-between items-center font-bold">
          <span>Total</span>
          <span id="modalTotal" class="text-lg">Rp 0</span>
        </div>
        <!-- Actions -->
        <div class="modal-action flex justify-end gap-3 mt-6">
          <button
            type="button"
            class="btn btn-ghost"
            onclick="checkout_modal.close()">
            Tutup
          </button>

          <button
            type="button"
            id="confirmCheckout"
            class="btn btn-primary !bg-blue-900 text-white px-6">
            Konfirmasi
          </button>
        </div>
      </div>
    </dialog>
  </section>

  {{-- data global --}}
  <script>
    const tickets = {
      @foreach($event->tickets as $tiket)
        {{ $tiket->id }}: {
          id: {{ $tiket->id }},
          price: {{ $tiket->harga ?? 0 }},
          stock: {{ $tiket->stok }},
          tipe: "{{ e($tiket->tipe) }}"
        },
      @endforeach
    };
  </script>

  {{-- skrip ui --}}
  <script>
    const formatRupiah = v => 'Rp ' + Number(v).toLocaleString('id-ID');

    function updateSummary() {
      let qty = 0, total = 0, html = '';

      Object.values(tickets).forEach(t => {
        const q = Number(document.getElementById('qty-'+t.id).value || 0);
        if (q > 0) {
          qty += q;
          total += q * t.price;
          html += `<div class="flex justify-between">
            <span>${t.tipe} x ${q}</span>
            <span>${formatRupiah(q*t.price)}</span>
          </div>`;
        }
        document.getElementById('subtotal-'+t.id).innerText = formatRupiah(q*t.price);
      });

      summaryItems.innerText = qty;
      summaryTotal.innerText = formatRupiah(total);
      selectedList.innerHTML = html || 'Belum ada tiket';
      checkoutButton.disabled = qty === 0;
    }

    document.querySelectorAll('[data-action]').forEach(btn => {
      btn.onclick = () => {
        const id = btn.dataset.id;
        const input = document.getElementById('qty-'+id);
        let v = Number(input.value);
        if (btn.dataset.action === 'inc' && v < tickets[id].stock) v++;
        if (btn.dataset.action === 'dec' && v > 0) v--;
        input.value = v;
        updateSummary();
      };
    });

    function openCheckout() {
      let html = '', total = 0;
      Object.values(tickets).forEach(t => {
        const q = Number(document.getElementById('qty-'+t.id).value || 0);
        if (q > 0) {
          html += `<div class="flex justify-between">
            <span>${t.tipe} x ${q}</span>
            <span>${formatRupiah(q*t.price)}</span>
          </div>`;
          total += q*t.price;
        }
      });
      modalItems.innerHTML = html;
      modalTotal.innerText = formatRupiah(total);
      checkout_modal.showModal();
    }

    updateSummary();
  </script>

  {{-- CHECKOUT  --}}
  <script>
  confirmCheckout.onclick = async () => {

    //CEK LOGIN DI FRONTEND
    @if(!auth()->check())
      window.location.href = "{{ route('login') }}";
      return;
    @endif

    confirmCheckout.disabled = true;
    confirmCheckout.innerText = 'Memproses...';

    const items = [];
    Object.values(tickets).forEach(t => {
      const q = Number(document.getElementById('qty-'+t.id).value || 0);
      if (q > 0) items.push({ tiket_id: t.id, jumlah: q });
    });

    try {
      const res = await fetch("{{ route('orders.store') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          event_id: {{ $event->id }},
          items
        })
      });

      // JIKA SESSION HABIS / BELUM LOGIN
      if (res.status === 401) {
        window.location.href = "{{ route('login') }}";
        return;
      }

      const data = await res.json();
      window.location.href = data.redirect;

    } catch (e) {
      alert('Terjadi kesalahan, silakan coba lagi.');
      confirmCheckout.disabled = false;
      confirmCheckout.innerText = 'Konfirmasi';
    }
  };
</script>
</x-layouts.app>
