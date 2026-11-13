@extends('layouts.app')

@section('judul', 'Tambah Buku')

@section('konten')
    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-6">Tambah Buku</h1>

        <form action="{{ route('buku.store') }}" method="POST">
            @csrf

            @include('buku._form')

            <div class="mt-6 flex gap-3">
                <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700">
                    Simpan
                </button>

                <a href="{{ route('buku.index') }}"
                   class="px-5 py-2 rounded-lg border border-slate-300 hover:bg-slate-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
