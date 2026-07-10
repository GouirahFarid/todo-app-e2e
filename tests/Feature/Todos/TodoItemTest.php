<?php

namespace Tests\Feature\Todos;

use App\Models\TodoItem;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodoItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_list_page(): void
    {
        $list = TodoList::factory()->create();

        $response = $this->get(route('todos.show', $list));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_their_own_list(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->get(route('todos.show', $list));

        $response->assertOk();
        $response->assertSee($list->name);
    }

    public function test_user_cannot_view_another_users_list(): void
    {
        $user = User::factory()->create();
        $otherList = TodoList::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('todos.show', $otherList));

        $response->assertForbidden();
    }

    public function test_user_can_add_an_item(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.show', ['list' => $list])
            ->set('title', 'Buy milk')
            ->set('description', 'Two bottles')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('todo_items', [
            'todo_list_id' => $list->id,
            'title' => 'Buy milk',
            'description' => 'Two bottles',
            'is_completed' => false,
        ]);
    }

    public function test_blank_description_is_stored_as_null(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.show', ['list' => $list])
            ->set('title', 'Buy milk')
            ->set('description', '')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('todo_items', [
            'todo_list_id' => $list->id,
            'title' => 'Buy milk',
            'description' => null,
        ]);
    }

    public function test_item_title_is_required(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.show', ['list' => $list])
            ->set('title', '')
            ->call('addItem')
            ->assertHasErrors(['title' => 'required']);

        $this->assertDatabaseCount('todo_items', 0);
    }

    public function test_user_can_toggle_item_completion(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();
        $item = TodoItem::factory()->for($list, 'list')->create();

        $this->actingAs($user);

        $component = Livewire::test('pages::todos.show', ['list' => $list]);

        $component->call('toggleItem', $item->id);
        $this->assertTrue($item->refresh()->is_completed);

        $component->call('toggleItem', $item->id);
        $this->assertFalse($item->refresh()->is_completed);
    }

    public function test_user_can_edit_an_item(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();
        $item = TodoItem::factory()->for($list, 'list')->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.show', ['list' => $list])
            ->call('startEditing', $item->id)
            ->set('editingTitle', 'Updated title')
            ->set('editingDescription', 'Updated description')
            ->call('updateItem')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('todo_items', [
            'id' => $item->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_can_delete_an_item(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();
        $item = TodoItem::factory()->for($list, 'list')->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.show', ['list' => $list])
            ->call('deleteItem', $item->id);

        $this->assertDatabaseMissing('todo_items', ['id' => $item->id]);
    }

    public function test_items_are_ordered_incomplete_first(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();
        TodoItem::factory()->for($list, 'list')->completed()->create(['title' => 'Completed item']);
        TodoItem::factory()->for($list, 'list')->create(['title' => 'Pending item']);

        $this->actingAs($user);

        $response = $this->get(route('todos.show', $list));

        $response->assertSeeInOrder(['Pending item', 'Completed item']);
    }

    public function test_user_cannot_manage_items_of_another_users_list(): void
    {
        $user = User::factory()->create();
        $otherList = TodoList::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.show', ['list' => $otherList])
            ->assertForbidden();
    }
}
