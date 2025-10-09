<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'is_private',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($channel) {
            if (empty($channel->slug)) {
                $channel->slug = Str::slug($channel->name);
            }
        });
    }

    /**
     * Get the user who created the channel.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the messages for the channel.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Get the users that belong to the channel.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_members')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    /**
     * Get the members of the channel.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_members')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    /**
     * Check if a user is a member of this channel.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Add a user to the channel.
     */
    public function addMember(User $user): void
    {
        if (!$this->hasMember($user)) {
            $this->members()->attach($user->id, ['joined_at' => now()]);
        }
    }

    /**
     * Remove a user from the channel.
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }
}
