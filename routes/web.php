<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('todos', 'pages::todos.index')->name('todos.index');
    Route::livewire('todos/{list}', 'pages::todos.show')->name('todos.show');
});

require __DIR__.'/settings.php';
