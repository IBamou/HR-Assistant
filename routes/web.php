<?php

use App\Http\Controllers\OfferController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Volt::route('offers', 'livewire.offers.index')->name('offers.index');
    Volt::route('offers/create', 'livewire.offers.create')->name('offers.create');
    Volt::route('offers/archived', 'livewire.offers.archived')->name('offers.archived');
    Volt::route('offers/{offer}', 'livewire.offers.show')->name('offers.show');
    Volt::route('offers/{offer}/edit', 'livewire.offers.edit')->name('offers.edit');

    Route::delete('offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
    Route::post('offers/{offer}/restore', [OfferController::class, 'restore'])->name('offers.restore')->withTrashed();
    Route::delete('offers/{offer}/force', [OfferController::class, 'forceDelete'])->name('offers.forceDelete')->withTrashed();
});

require __DIR__.'/settings.php';
