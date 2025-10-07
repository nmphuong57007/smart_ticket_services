<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fullname',
        'email',
        'phone',
        'address',
        'gender',
        'password',
        'avatar',
        'role',
        'points',
        'status',
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
        ];
    }

    /**
     * Scope to order by ID descending (newest first)
     */
    public function scopeNewestFirst($query)
    {
        return $query->orderBy('id', 'desc');
    }

    /**
     * Scope to order by ID ascending (oldest first)
     */
    public function scopeOldestFirst($query)
    {
        return $query->orderBy('id', 'asc');
    }

    /**
     * Relationship với PointsHistory
     */
    public function pointsHistory()
    {
        return $this->hasMany(PointsHistory::class);
    }

    /**
     * Phương thức để cộng/trừ điểm với lịch sử
     */
    public function addPoints(int $points, string $type, string $source, string $description, array $options = [])
    {
        $balanceBefore = $this->points;
        $balanceAfter = $balanceBefore + $points;
        
        // Cập nhật điểm trong bảng users
        $this->update(['points' => $balanceAfter]);
        
        // Tạo bản ghi lịch sử
        return $this->pointsHistory()->create([
            'points' => $points,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'type' => $type,
            'source' => $source,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'description' => $description,
            'metadata' => $options['metadata'] ?? null,
            'created_by' => $options['created_by'] ?? null
        ]);
    }
}
