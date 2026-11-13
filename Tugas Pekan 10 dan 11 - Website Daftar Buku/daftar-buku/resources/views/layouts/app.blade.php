<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Daftar Buku')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">

    {{-- Navigasi --}}
    <nav class="bg-indigo-700 text-white shadow">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('buku.index') }}" class="text-xl font-bold">
                📚 Perpustakaan Mini
            </a>

            <a href="{{ route('buku.create') }}"
               class="bg-white text-indigo-700 px-4 py-2 rounded-lg font-semibold hover:bg-indigo-50">
                + Tambah Buku
            </a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">

        {{-- Pesan sukses (flash message) --}}
        @if (session('sukses'))
            <div class="mb-6 rounded-lg bg-green-100 border border-green-300 text-green-800 px-4 py-3">
                {{ session('sukses') }}
            </div>
        @endif

        {{-- Isi halaman dari setiap view --}}
        @yield('konten')

    </main>

    <footer class="text-center text-sm text-slate-500 py-6">
        Dibuat dengan Laravel, Blade &amp; Tailwind CSS
    </footer>

</body>
</html>
