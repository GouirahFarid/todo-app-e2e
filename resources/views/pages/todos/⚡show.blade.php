<?php

use App\Models\TodoList;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Todo list')] class extends Component {
    public TodoList $list;

    public string $title = '';

    public string $description = '';

    public ?int $editingItemId = null;

    public string $editingTitle = '';

    public string $editingDescription = '';

    /**
     * Mount the component.
     */
    public function mount(TodoList $list): void
    {
        abort_unless($list->user_id === Auth::id(), 403);

        $this->list = $list;
    }

    /**
     * Get the list's items, incomplete first.
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->list->items()
            ->orderBy('is_completed')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Add a new item to the list.
     */
    public function addItem(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $this->list->items()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
        ]);

        $this->reset('title', 'description');

        unset($this->items);

        Flux::toast(variant: 'success', text: __('Item added.'));
    }

    /**
     * Toggle the completion state of the given item.
     */
    public function toggleItem(int $itemId): void
    {
        $item = $this->list->items()->findOrFail($itemId);

        $item->update(['is_completed' => ! $item->is_completed]);

        unset($this->items);
    }

    /**
     * Open the edit modal for the given item.
     */
    public function startEditing(int $itemId): void
    {
        $item = $this->list->items()->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->editingTitle = $item->title;
        $this->editingDescription = $item->description ?? '';

        $this->resetValidation(['editingTitle', 'editingDescription']);

        Flux::modal('edit-item')->show();
    }

    /**
     * Update the item being edited.
     */
    public function updateItem(): void
    {
        $validated = $this->validate([
            'editingTitle' => ['required', 'string', 'max:255'],
            'editingDescription' => ['nullable', 'string', 'max:65535'],
        ]);

        $this->list->items()->findOrFail($this->editingItemId)->update([
            'title' => $validated['editingTitle'],
            'description' => $validated['editingDescription'] ?: null,
        ]);

        $this->reset('editingItemId', 'editingTitle', 'editingDescription');

        unset($this->items);

        Flux::modal('edit-item')->close();

        Flux::toast(variant: 'success', text: __('Item updated.'));
    }

    /**
     * Delete the given item.
     */
    public function deleteItem(int $itemId): void
    {
        $this->list->items()->findOrFail($itemId)->delete();

        unset($this->items);

        Flux::toast(variant: 'success', text: __('Item deleted.'));
    }
}; ?>

<section class="w-full">
    <div>
        <flux:link :href="route('todos.index')" wire:navigate class="text-sm">
            {{ __('← All lists') }}
        </flux:link>

        <div class="mt-2 flex items-center gap-3">
            <flux:heading size="xl">{{ $list->name }}</flux:heading>

            <flux:badge size="sm" :color="$this->items->isNotEmpty() && $this->items->every->is_completed ? 'green' : 'zinc'">
                {{ $this->items->where('is_completed', true)->count() }}/{{ $this->items->count() }}
            </flux:badge>
        </div>
    </div>

    <flux:separator variant="subtle" class="my-6" />

    <form wire:submit="addItem" class="space-y-4">
        <flux:input wire:model="title" :label="__('Title')" type="text" :placeholder="__('What needs to be done?')" />

        <flux:textarea wire:model="description" :label="__('Description')" rows="2" :placeholder="__('Optional details')" />

        <flux:button variant="primary" type="submit" icon="plus" data-test="add-item-button">
            {{ __('Add item') }}
        </flux:button>
    </form>

    <flux:separator variant="subtle" class="my-6" />

    @if ($this->items->isEmpty())
        <flux:text class="text-center">{{ __('No items yet — add your first one above.') }}</flux:text>
    @else
        <div class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->items as $item)
                <div wire:key="item-{{ $item->id }}" class="flex items-start gap-3 p-4">
                    <flux:checkbox
                        :checked="$item->is_completed"
                        wire:click="toggleItem({{ $item->id }})"
                        class="mt-1"
                        data-test="toggle-item-{{ $item->id }}"
                    />

                    <div class="min-w-0 flex-1 {{ $item->is_completed ? 'opacity-50' : '' }}">
                        <flux:text class="font-medium {{ $item->is_completed ? 'line-through' : '' }}">
                            {{ $item->title }}
                        </flux:text>

                        @if ($item->description)
                            <flux:text class="mt-1 text-sm">{{ $item->description }}</flux:text>
                        @endif
                    </div>

                    <div class="flex items-center gap-1">
                        <flux:button variant="ghost" size="sm" icon="pencil" inset="top bottom" wire:click="startEditing({{ $item->id }})" data-test="edit-item-{{ $item->id }}" />

                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            inset="top bottom"
                            wire:click="deleteItem({{ $item->id }})"
                            wire:confirm="{{ __('Delete this item?') }}"
                            data-test="delete-item-{{ $item->id }}"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="edit-item" class="max-w-lg">
        <form wire:submit="updateItem" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit item') }}</flux:heading>
            </div>

            <flux:input wire:model="editingTitle" :label="__('Title')" type="text" />

            <flux:textarea wire:model="editingDescription" :label="__('Description')" rows="3" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-item-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
