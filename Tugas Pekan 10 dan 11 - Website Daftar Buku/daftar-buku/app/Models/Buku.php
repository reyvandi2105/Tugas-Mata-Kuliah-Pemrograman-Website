<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    // Nama tabel di database
    protected $table = 'buku';

    // Kolom yang boleh diisi melalui form
    protected $fillable = [
        'judul',
        'penulis',
        'tahun_terbit',
        'sinopsis',
    ];
}