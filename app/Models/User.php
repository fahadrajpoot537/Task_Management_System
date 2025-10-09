<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'manager_id',
        'phone',
        'bio',
        'avatar',
        'is_online',
        'last_seen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'last_seen' => 'datetime',
        ];
    }

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the manager that owns the user.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the team members for the user.
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Get the projects created by the user.
     */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by_user_id');
    }

    /**
     * Get the tasks assigned to the user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    /**
     * Get the tasks assigned by the user.
     */
    public function assignedByTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by_user_id');
    }

    /**
     * Get the attachments uploaded by the user.
     */
    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by_user_id');
    }

    /**
     * Get the logs for the user.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Get the messages sent by the user.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the channels the user belongs to.
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_members')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    /**
     * Get messages sent by the user.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    /**
     * Get messages received by the user.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'receiver_id');
    }

    /**
     * Get unread messages count for the user.
     */
    public function unreadMessagesCount(): int
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    /**
     * Get conversations for the user.
     */
    public function conversations()
    {
        $sentMessages = $this->sentMessages()->with('receiver')->get();
        $receivedMessages = $this->receivedMessages()->with('sender')->get();
        
        $conversations = collect();
        
        // Add conversations from sent messages
        foreach ($sentMessages as $message) {
            $conversations->put($message->receiver_id, [
                'user' => $message->receiver,
                'last_message' => $message,
                'unread_count' => 0
            ]);
        }
        
        // Add conversations from received messages
        foreach ($receivedMessages as $message) {
            if ($conversations->has($message->sender_id)) {
                // Update existing conversation
                $conversation = $conversations->get($message->sender_id);
                if ($message->created_at > $conversation['last_message']->created_at) {
                    $conversation['last_message'] = $message;
                }
                if (!$message->is_read) {
                    $conversation['unread_count']++;
                }
                $conversations->put($message->sender_id, $conversation);
            } else {
                // Create new conversation
                $conversations->put($message->sender_id, [
                    'user' => $message->sender,
                    'last_message' => $message,
                    'unread_count' => $message->is_read ? 0 : 1
                ]);
            }
        }
        
        return $conversations->sortByDesc(function ($conversation) {
            return $conversation['last_message']->created_at;
        });
    }

    /**
     * Mark user as online.
     */
    public function markAsOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_seen' => now(),
        ]);
    }

    /**
     * Mark user as offline.
     */
    public function markAsOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_seen' => now(),
        ]);
    }

    /**
     * Get online status with last seen time.
     */
    public function getOnlineStatusAttribute(): string
    {
        if ($this->is_online) {
            return 'online';
        }

        if ($this->last_seen) {
            $diff = now()->diffInMinutes($this->last_seen);
            
            if ($diff < 1) {
                return 'just now';
            } elseif ($diff < 60) {
                return $diff . 'm ago';
            } elseif ($diff < 1440) { // 24 hours
                return floor($diff / 60) . 'h ago';
            } else {
                return floor($diff / 1440) . 'd ago';
            }
        }

        return 'offline';
    }

    /**
     * Get custom permissions assigned directly to this user.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * Check if user has a specific permission (from role or custom).
     */
    public function hasPermission(string $permission): bool
    {
        // Check role permissions
        $hasRolePermission = $this->role->permissions()->where('name', $permission)->exists();
        
        // Check custom permissions
        $hasCustomPermission = $this->permissions()->where('name', $permission)->exists();
        
        return $hasRolePermission || $hasCustomPermission;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->name === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role && $this->role->name === 'manager';
    }

    public function isEmployee(): bool
    {
        return $this->role && $this->role->name === 'employee';
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    /**
     * Get users that this user can manage.
     */
    public function manageableUsers()
    {
        if ($this->isSuperAdmin()) {
            return User::all();
        }
        
        if ($this->isAdmin()) {
            return User::where('role_id', '!=', 1)->get(); // Admin can manage all except super admin
        }
        
        if ($this->isManager()) {
            return $this->teamMembers;
        }
        
        return collect([$this]);
    }

    /**
     * Get the user's avatar URL or default avatar.
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }
        
        // Return default avatar with user's initial
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=3b82f6&background=f0f9ff';
    }

    /**
     * Check if user is online (active within last 5 minutes).
     */
    public function isOnline()
    {
        if (!$this->last_seen) {
            return false;
        }
        
        return $this->last_seen->diffInMinutes(now()) < 5;
    }

    /**
     * Get formatted last seen time.
     */
    public function getFormattedLastSeenAttribute()
    {
        if (!$this->last_seen) {
            return 'Never';
        }
        
        return $this->last_seen->diffForHumans();
    }
}