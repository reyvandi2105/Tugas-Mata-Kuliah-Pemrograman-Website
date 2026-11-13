<?php

use App\Http\Controllers\BukuController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/buku');

Route::resource('buku', BukuController::class);
