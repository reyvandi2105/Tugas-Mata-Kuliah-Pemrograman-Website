{{-- Bagian form yang dipakai bersama oleh create dan edit --}}
<div class="space-y-5">
    <div>
        <label for="judul" class="block font-medium mb-1">Judul</label>
        <input
            type="text"
            id="judul"
            name="judul"
            value="{{ old('judul', $buku->judul ?? '') }}"
            class="w-full rounded-lg border px-3 py-2 @error('judul') border-red-500 @else border-slate-300 @enderror"
        >

        @error('judul')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="penulis" class="block font-medium mb-1">Penulis</label>
        <input
            type="text"
            id="penulis"
            name="penulis"
            value="{{ old('penulis', $buku->penulis ?? '') }}"
            class="w-full rounded-lg border px-3 py-2 @error('penulis') border-red-500 @else border-slate-300 @enderror"
        >

        @error('penulis')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tahun_terbit" class="block font-medium mb-1">Tahun Terbit</label>
        <input
            type="number"
            id="tahun_terbit"
            name="tahun_terbit"
            value="{{ old('tahun_terbit', $buku->tahun_terbit ?? '') }}"
            class="w-40 rounded-lg border px-3 py-2 @error('tahun_terbit') border-red-500 @else border-slate-300 @enderror"
        >

        @error('tahun_terbit')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="sinopsis" class="block font-medium mb-1">
            Sinopsis
            <span class="text-slate-400">(opsional)</span>
        </label>

        <textarea
            id="sinopsis"
            name="sinopsis"
            rows="4"
            class="w-full rounded-lg border border-slate-300 px-3 py-2"
        >{{ old('sinopsis', $buku->sinopsis ?? '') }}</textarea>
    </div>
</div>
