<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'technician', 'cashier'];

    public const MODULES = [
        'dashboard' => 'Dashboard',
        'point-of-sale' => 'Product Transaction',
        'job-orders' => 'Job Orders',
        'user-management' => 'User Management',
        'forecasting' => 'Sales Forecasting',
    ];

    /**
     * Module access granted automatically for each position.
     */
    public const ROLE_MODULES = [
        'admin' => ['dashboard', 'point-of-sale', 'job-orders', 'user-management', 'forecasting'],
        'cashier' => ['dashboard', 'point-of-sale', 'job-orders', 'forecasting'],
        'technician' => ['job-orders'],
    ];

    /**
     * Resolve the module list a position should have access to.
     *
     * @return list<string>
     */
    public static function modulesForRole(?string $role): array
    {
        return self::ROLE_MODULES[$role] ?? ['dashboard'];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'contact_number',
        'avatar_path',
        'position',
        'bio',
        'password',
        'role',
        'status',
        'modules',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'last_login_at' => 'datetime',
            'modules' => 'array',
        ];
    }

    public function technician(): HasOne
    {
        return $this->hasOne(Technician::class);
    }

    public function jobOrders(): HasManyThrough
    {
        return $this->hasManyThrough(JobOrder::class, Technician::class, 'user_id', 'technician_id');
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Admin',
            'technician' => 'Technician',
            'cashier' => 'Cashier',
            default => ucfirst($this->role),
        };
    }

    public function roleTone(): string
    {
        return match ($this->role) {
            'admin' => 'brand',
            'technician' => 'indigo',
            'cashier' => 'teal',
            default => 'slate',
        };
    }

    public function userCode(): string
    {
        return sprintf('USR_%03d', $this->id);
    }

    /**
     * The route a user should land on after login (and when they hit a page
     * their role can't see). Admins get the dashboard; everyone else gets
     * the first tool their role actually uses.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'technician' => 'job-orders.index',
            'cashier' => 'products.index',
            default => 'dashboard',
        };
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? route('media.show', ['path' => $this->avatar_path]) : null;
    }

    public function initials(): string
    {
        return strtoupper(substr($this->name ?? 'A', 0, 1));
    }
}
