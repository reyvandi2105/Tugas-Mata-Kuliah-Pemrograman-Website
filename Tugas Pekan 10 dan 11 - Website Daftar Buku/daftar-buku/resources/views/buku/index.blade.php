@extends('layouts.app')

@section('judul', 'Daftar Buku')

@section('konten')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold">Daftar Buku</h1>

        {{-- Form pencarian --}}
        <form action="{{ route('buku.index') }}" method="GET" class="flex gap-2">
            <input
                type="text"
                name="cari"
                value="{{ $cari }}"
                placeholder="Cari judul atau penulis..."
                class="w-64 rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >

            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                Cari
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-sm uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Penulis</th>
                    <th class="px-4 py-3">Tahun</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($daftarBuku as $buku)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium">
                            <a
                                href="{{ route('buku.show', $buku) }}"
                                class="text-indigo-700 hover:underline"
                            >
                                {{ $buku->judul }}
                            </a>
                        </td>

                        <td class="px-4 py-3">
                            {{ $buku->penulis }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $buku->tahun_terbit }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a
                                    href="{{ route('buku.edit', $buku) }}"
                                    class="px-3 py-1 rounded bg-amber-100 text-amber-800 hover:bg-amber-200"
                                >
                                    Ubah
                                </a>

                                <form
                                    action="{{ route('buku.destroy', $buku) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin hapus buku ini?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button class="px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                            Belum ada buku.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Navigasi halaman (pagination) --}}
    <div class="mt-6">
        {{ $daftarBuku->links() }}
    </div>
@endsection
