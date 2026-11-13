@extends('layouts.app')

@section('judul', $buku->judul)

@section('konten')
    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-3xl font-bold">{{ $buku->judul }}</h1>

        <p class="text-slate-500 mt-1">
            {{ $buku->penulis }} &middot; {{ $buku->tahun_terbit }}
        </p>

        <div class="mt-6 text-slate-700 leading-relaxed">
            {{ $buku->sinopsis ?: 'Belum ada sinopsis.' }}
        </div>

        <div class="mt-8 flex gap-3">
            <a href="{{ route('buku.edit', $buku) }}"
               class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600">
                Ubah
            </a>

            <a href="{{ route('buku.index') }}"
               class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50">
                ← Kembali
            </a>
        </div>
    </div>
@endsection
