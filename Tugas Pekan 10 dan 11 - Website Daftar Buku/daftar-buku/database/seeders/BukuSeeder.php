<?php

namespace Database\Seeders;

use App\Models\Buku;
use Illuminate\Database\Seeder;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'judul' => 'Laskar Pelangi',
                'penulis' => 'Andrea Hirata',
                'tahun_terbit' => 2005,
                'sinopsis' => 'Kisah sepuluh anak Belitung yang berjuang bersekolah di tengah keterbatasan.',
            ],
            [
                'judul' => 'Bumi Manusia',
                'penulis' => 'Pramoedya Ananta Toer',
                'tahun_terbit' => 1980,
                'sinopsis' => 'Kisah Minke, pemuda pribumi pada masa kolonial Hindia Belanda.',
            ],
            [
                'judul' => 'Negeri 5 Menara',
                'penulis' => 'Ahmad Fuadi',
                'tahun_terbit' => 2009,
                'sinopsis' => 'Perjalanan Alif dan lima sahabatnya menuntut ilmu di sebuah pesantren.',
            ],
            [
                'judul' => 'Ronggeng Dukuh Paruk',
                'penulis' => 'Ahmad Tohari',
                'tahun_terbit' => 1982,
                'sinopsis' => 'Kisah Srintil, penari ronggeng dari sebuah dukuh kecil di Banyumas.',
            ],
            [
                'judul' => 'Cantik Itu Luka',
                'penulis' => 'Eka Kurniawan',
                'tahun_terbit' => 2002,
                'sinopsis' => 'Saga keluarga yang membentang dari masa kolonial hingga pascakemerdekaan.',
            ],
            [
                'judul' => 'Pulang',
                'penulis' => 'Leila S. Chudori',
                'tahun_terbit' => 2012,
                'sinopsis' => 'Kisah para eksil politik Indonesia di Paris dan kerinduan akan tanah air.',
            ],
        ];

        foreach ($data as $buku) {
            Buku::create($buku);
        }
    }
}
