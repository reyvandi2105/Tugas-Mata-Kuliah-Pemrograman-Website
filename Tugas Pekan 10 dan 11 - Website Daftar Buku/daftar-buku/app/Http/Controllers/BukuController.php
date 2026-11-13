<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;

class BukuController extends Controller
{
    // Menampilkan daftar buku (dengan pencarian)
    public function index(Request $request)
    {
        $cari = $request->query('cari');

        $daftarBuku = Buku::query()
            ->when($cari, function ($query, $cari) {
                $query->where('judul', 'like', "%{$cari}%")
                      ->orWhere('penulis', 'like', "%{$cari}%");
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('buku.index', compact('daftarBuku', 'cari'));
    }

    // Menampilkan form tambah buku
    public function create()
    {
        return view('buku.create');
    }

    // Menyimpan buku baru ke database
    public function store(Request $request)
    {
        $data = $request->validate($this->aturan());

        Buku::create($data);

        return redirect()->route('buku.index')
            ->with('sukses', 'Buku berhasil ditambahkan.');
    }

    // Menampilkan detail satu buku
    public function show(Buku $buku)
    {
        return view('buku.show', compact('buku'));
    }

    // Menampilkan form ubah buku
    public function edit(Buku $buku)
    {
        return view('buku.edit', compact('buku'));
    }

    // Menyimpan perubahan buku
    public function update(Request $request, Buku $buku)
    {
        $data = $request->validate($this->aturan());

        $buku->update($data);

        return redirect()->route('buku.index')
            ->with('sukses', 'Buku berhasil diperbarui.');
    }

    // Menghapus buku
    public function destroy(Buku $buku)
    {
        $buku->delete();

        return redirect()->route('buku.index')
            ->with('sukses', 'Buku berhasil dihapus.');
    }

    // Aturan validasi yang dipakai bersama oleh store() dan update()
    private function aturan(): array
    {
        return [
            'judul'        => 'required|string|max:255',
            'penulis'      => 'required|string|max:255',
            'tahun_terbit' => 'required|integer|min:1000|max:' . date('Y'),
            'sinopsis'     => 'nullable|string',
        ];
    }
}
