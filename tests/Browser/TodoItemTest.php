<?php

use App\Models\TodoItem;
use App\Models\TodoList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->list = TodoList::factory()->for($this->user)->create(['name' => 'Groceries']);
});

it('adds an item with a description', function () {
    visit('/todos/'.$this->list->id)
        ->type('title', 'Buy milk')
        ->type('description', 'Two liters')
        ->click('@add-item-button')
        ->waitForText('Item added.')
        ->assertSee('Buy milk')
        ->assertSee('Two liters')
        ->assertNoJavascriptErrors();

    $this->assertDatabaseHas('todo_items', [
        'todo_list_id' => $this->list->id,
        'title' => 'Buy milk',
        'description' => 'Two liters',
    ]);
});

it('adds an item without a description', function () {
    visit('/todos/'.$this->list->id)
        ->type('title', 'Buy bread')
        ->click('@add-item-button')
        ->waitForText('Item added.')
        ->assertSee('Buy bread');

    expect($this->list->items()->first()->description)->toBeNull();
});

it('shows a validation error when the item title is empty', function () {
    visit('/todos/'.$this->list->id)
        ->click('@add-item-button')
        ->waitForText('required');

    $this->assertDatabaseCount('todo_items', 0);
});

it('toggles an item complete and back', function () {
    $item = TodoItem::factory()->for($this->list, 'list')->create(['title' => 'Buy milk']);

    $page = visit('/todos/'.$this->list->id);

    $page->assertSee('0/1')
        ->click('@toggle-item-'.$item->id)
        ->waitForText('1/1');

    expect($item->refresh()->is_completed)->toBeTrue();

    $page->click('@toggle-item-'.$item->id)
        ->waitForText('0/1');

    expect($item->refresh()->is_completed)->toBeFalse();
});

it('edits an item through the modal', function () {
    $item = TodoItem::factory()->for($this->list, 'list')->create([
        'title' => 'Buy milk',
        'description' => 'Two liters',
    ]);

    visit('/todos/'.$this->list->id)
        ->click('@edit-item-'.$item->id)
        ->fill('editingTitle', 'Buy oat milk')
        ->fill('editingDescription', 'One liter')
        ->click('@save-item-button')
        ->waitForText('Item updated.')
        ->assertSee('Buy oat milk')
        ->assertSee('One liter');

    expect($item->refresh())
        ->title->toBe('Buy oat milk')
        ->description->toBe('One liter');
});

it('deletes an item after confirming', function () {
    $item = TodoItem::factory()->for($this->list, 'list')->create(['title' => 'Buy milk']);

    $page = visit('/todos/'.$this->list->id);
    $page->script('window.confirm = () => true');
    $page->click('@delete-item-'.$item->id)
        ->waitForText('Item deleted.')
        ->assertSee('No items yet — add your first one above.');

    $this->assertDatabaseMissing('todo_items', ['id' => $item->id]);
});
