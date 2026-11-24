<?php

use App\Livewire\SystemStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', SystemStatus::class)->name('home');
