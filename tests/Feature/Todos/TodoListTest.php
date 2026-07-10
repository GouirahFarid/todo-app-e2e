<?php

namespace Tests\Feature\Todos;

use App\Models\TodoItem;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodoListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_todos_index(): void
    {
        $response = $this->get(route('todos.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_todos_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('todos.index'));

        $response->assertOk();
    }

    public function test_index_only_shows_own_lists(): void
    {
        $user = User::factory()->create();
        $ownList = TodoList::factory()->for($user)->create(['name' => 'My own list']);
        $otherList = TodoList::factory()->create(['name' => 'Someone elses list']);

        $this->actingAs($user);

        $response = $this->get(route('todos.index'));

        $response->assertSee($ownList->name);
        $response->assertDontSee($otherList->name);
    }

    public function test_user_can_create_a_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.index')
            ->set('name', 'Groceries')
            ->call('createList')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('todo_lists', [
            'user_id' => $user->id,
            'name' => 'Groceries',
        ]);
    }

    public function test_list_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.index')
            ->set('name', '')
            ->call('createList')
            ->assertHasErrors(['name' => 'required']);

        $this->assertDatabaseCount('todo_lists', 0);
    }

    public function test_user_can_rename_a_list(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create(['name' => 'Old name']);

        $this->actingAs($user);

        Livewire::test('pages::todos.index')
            ->call('startRenaming', $list->id)
            ->set('renameName', 'New name')
            ->call('renameList')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('todo_lists', [
            'id' => $list->id,
            'name' => 'New name',
        ]);
    }

    public function test_user_cannot_rename_another_users_list(): void
    {
        $user = User::factory()->create();
        $otherList = TodoList::factory()->create();

        $this->actingAs($user);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test('pages::todos.index')
            ->call('startRenaming', $otherList->id);
    }

    public function test_user_can_delete_a_list(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.index')
            ->call('deleteList', $list->id);

        $this->assertDatabaseMissing('todo_lists', ['id' => $list->id]);
    }

    public function test_deleting_a_list_deletes_its_items(): void
    {
        $user = User::factory()->create();
        $list = TodoList::factory()->for($user)->create();
        $item = TodoItem::factory()->for($list, 'list')->create();

        $this->actingAs($user);

        Livewire::test('pages::todos.index')
            ->call('deleteList', $list->id);

        $this->assertDatabaseMissing('todo_items', ['id' => $item->id]);
    }

    public function test_user_cannot_delete_another_users_list(): void
    {
        $user = User::factory()->create();
        $otherList = TodoList::factory()->create();

        $this->actingAs($user);

        try {
            Livewire::test('pages::todos.index')
                ->call('deleteList', $otherList->id);

            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException) {
            // Expected.
        }

        $this->assertDatabaseHas('todo_lists', ['id' => $otherList->id]);
    }
}
