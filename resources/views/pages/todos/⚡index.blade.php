<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Todo lists')] class extends Component {
    public string $name = '';

    public ?int $renamingListId = null;

    public string $renameName = '';

    /**
     * Get the authenticated user's lists with item counts.
     */
    #[Computed]
    public function lists(): Collection
    {
        return Auth::user()->todoLists()
            ->withCount([
                'items',
                'items as completed_items_count' => fn ($query) => $query->where('is_completed', true),
            ])
            ->latest()
            ->get();
    }

    /**
     * Create a new list for the authenticated user.
     */
    public function createList(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Auth::user()->todoLists()->create($validated);

        $this->reset('name');

        unset($this->lists);

        Flux::modal('create-list')->close();

        Flux::toast(variant: 'success', text: __('List created.'));
    }

    /**
     * Open the rename modal for the given list.
     */
    public function startRenaming(int $listId): void
    {
        $list = Auth::user()->todoLists()->findOrFail($listId);

        $this->renamingListId = $list->id;
        $this->renameName = $list->name;

        $this->resetValidation('renameName');

        Flux::modal('rename-list')->show();
    }

    /**
     * Rename the list being edited.
     */
    public function renameList(): void
    {
        $validated = $this->validate([
            'renameName' => ['required', 'string', 'max:255'],
        ]);

        Auth::user()->todoLists()
            ->findOrFail($this->renamingListId)
            ->update(['name' => $validated['renameName']]);

        $this->reset('renamingListId', 'renameName');

        unset($this->lists);

        Flux::modal('rename-list')->close();

        Flux::toast(variant: 'success', text: __('List renamed.'));
    }

    /**
     * Delete the given list and all of its items.
     */
    public function deleteList(int $listId): void
    {
        Auth::user()->todoLists()->findOrFail($listId)->delete();

        unset($this->lists);

        Flux::toast(variant: 'success', text: __('List deleted.'));
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Todo lists') }}</flux:heading>
            <flux:subheading>{{ __('Organize your tasks into lists') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="create-list">
            <flux:button variant="primary" icon="plus" data-test="create-list-button">
                {{ __('New list') }}
            </flux:button>
        </flux:modal.trigger>
    </div>

    <flux:separator variant="subtle" class="my-6" />

    @if ($this->lists->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-zinc-200 py-16 dark:border-zinc-700">
            <flux:icon.list-bullet class="size-8 text-zinc-400" />
            <flux:heading>{{ __('No lists yet') }}</flux:heading>
            <flux:text>{{ __('Create your first list to start tracking your tasks.') }}</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Progress') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->lists as $list)
                    <flux:table.row wire:key="list-{{ $list->id }}">
                        <flux:table.cell>
                            <flux:link :href="route('todos.show', $list)" wire:navigate>
                                {{ $list->name }}
                            </flux:link>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" :color="$list->items_count > 0 && $list->completed_items_count === $list->items_count ? 'green' : 'zinc'">
                                {{ $list->completed_items_count }}/{{ $list->items_count }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" data-test="list-menu-{{ $list->id }}" />

                                <flux:menu>
                                    <flux:menu.item icon="pencil" wire:click="startRenaming({{ $list->id }})" data-test="rename-list-menu-{{ $list->id }}">
                                        {{ __('Rename') }}
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item
                                        variant="danger"
                                        icon="trash"
                                        wire:click="deleteList({{ $list->id }})"
                                        wire:confirm="{{ __('Delete this list and all of its items?') }}"
                                        data-test="delete-list-menu-{{ $list->id }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    <flux:modal name="create-list" class="max-w-lg">
        <form wire:submit="createList" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('New list') }}</flux:heading>
                <flux:subheading>{{ __('Give your list a name.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-list-button">
                    {{ __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="rename-list" class="max-w-lg">
        <form wire:submit="renameList" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Rename list') }}</flux:heading>
            </div>

            <flux:input wire:model="renameName" :label="__('Name')" type="text" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="rename-list-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
