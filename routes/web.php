<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;




Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
Route::post('/contacts/store', [ContactController::class, 'store'])->name('contacts.store');
Route::get('/contacts/{id}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
Route::put('/contacts/{id}/update', [ContactController::class, 'update'])->name('contacts.update');
Route::delete('/contacts/delete/{contact}', [ContactController::class, 'destroy']);
Route::get('/contacts/search', [ContactController::class, 'search'])->name('contacts.search');
Route::post('/contacts/get-names', [ContactController::class, 'getContactNames']);
Route::post('/merge-contacts', [ContactController::class, 'merge'])->name('contacts.merge');
