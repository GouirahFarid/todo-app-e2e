<?php

use App\Models\User;

it('registers a new user through the browser', function () {
    visit('/register')
        ->type('name', 'John Doe')
        ->type('email', 'john@example.com')
        ->type('password', 'password')
        ->type('password_confirmation', 'password')
        ->click('@register-user-button')
        ->assertPathIs('/todos')
        ->assertSee('Todo lists');

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click('@login-button')
        ->assertPathIs('/todos')
        ->assertSee('Todo lists');
});

it('rejects a wrong password', function () {
    $user = User::factory()->create();

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'wrong-password')
        ->click('@login-button')
        ->assertPathIs('/login');

    $this->assertGuest();
});
