<?php

use App\Models\TodoList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('creates a list', function () {
    visit('/todos')
        ->assertSee('No lists yet')
        ->click('@create-list-button')
        ->type('name', 'Groceries')
        ->click('@save-list-button')
        ->waitForText('List created.')
        ->assertSee('Groceries')
        ->assertNoJavascriptErrors();

    $this->assertDatabaseHas('todo_lists', [
        'user_id' => $this->user->id,
        'name' => 'Groceries',
    ]);
});

it('shows a validation error when the list name is empty', function () {
    visit('/todos')
        ->click('@create-list-button')
        ->click('@save-list-button')
        ->waitForText('required');

    $this->assertDatabaseCount('todo_lists', 0);
});

it('renames a list', function () {
    $list = TodoList::factory()->for($this->user)->create(['name' => 'Old name']);

    visit('/todos')
        ->click('@list-menu-'.$list->id)
        ->click('@rename-list-menu-'.$list->id)
        ->fill('renameName', 'New name')
        ->click('@rename-list-button')
        ->waitForText('List renamed.')
        ->assertSee('New name')
        ->assertDontSee('Old name');

    expect($list->refresh()->name)->toBe('New name');
});

it('deletes a list after confirming', function () {
    $list = TodoList::factory()->for($this->user)->create(['name' => 'Groceries']);

    $page = visit('/todos');
    $page->script('window.confirm = () => true');
    $page->click('@list-menu-'.$list->id)
        ->click('@delete-list-menu-'.$list->id)
        ->waitForText('List deleted.')
        ->assertSee('No lists yet');

    $this->assertDatabaseMissing('todo_lists', ['id' => $list->id]);
});

it('opens a list from the index', function () {
    $list = TodoList::factory()->for($this->user)->create(['name' => 'Groceries']);

    visit('/todos')
        ->click('Groceries')
        ->waitForText('No items yet — add your first one above.')
        ->assertPathIs('/todos/'.$list->id)
        ->assertSee('Groceries')
        ->assertNoJavascriptErrors();
});
