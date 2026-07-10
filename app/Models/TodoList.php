<?php

namespace App\Models;

use Database\Factories\TodoListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class TodoList extends Model
{
    /** @use HasFactory<TodoListFactory> */
    use HasFactory;

    /**
     * Get the user that owns the list.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items of the list.
     *
     * @return HasMany<TodoItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TodoItem::class);
    }
}
