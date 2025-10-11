<?php

namespace App\Http\Services\User;

use App\Models\User;
use Illuminate\Http\Request;

class UserService
{
    /**
     * Get all users with pagination and filtering
     */
    public function getUsers(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::latest('id')
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($filters['role'] ?? null, fn($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['sort_by'] ?? null, function ($query, $sortBy) use ($filters) {
                $sortOrder = $filters['sort_order'] ?? 'desc';
                
                // Sử dụng latest/oldest khi có thể cho consistency
                if (in_array($sortBy, ['id', 'created_at', 'updated_at']) && $sortOrder === 'desc') {
                    return $query->latest($sortBy);
                } elseif (in_array($sortBy, ['id', 'created_at', 'updated_at']) && $sortOrder === 'asc') {
                    return $query->oldest($sortBy);
                }
                
                return $query->orderBy($sortBy, $sortOrder);
            })
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'blocked_users' => User::where('status', 'blocked')->count(),
            'users_by_role' => [
                'customers' => User::where('role', 'customer')->count(),
                'staff' => User::where('role', 'staff')->count(),
                'admins' => User::where('role', 'admin')->count()
            ],
            'recent_registrations' => [
                'today' => User::whereDate('created_at', now()->toDateString())->count(),
                'this_week' => User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'this_month' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()
            ],
            'top_customers_by_points' => User::where('role', 'customer')
                ->latest('points')
                ->limit(5)
                ->select('id', 'fullname', 'email', 'points')
                ->get()
        ];
    }

    /**
     * Find user by ID
     */
    public function findUserById(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Update user information
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Toggle user status (active/blocked)
     */
    public function toggleUserStatus(User $user): User
    {
        $newStatus = $user->status === 'active' ? 'blocked' : 'active';
        $user->update(['status' => $newStatus]);

        // Revoke all tokens if blocking user
        if ($newStatus === 'blocked') {
            $user->tokens()->delete();
        }

        return $user->fresh();
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        // Revoke all tokens before deletion
        $user->tokens()->delete();

        // Delete user
        return $user->delete();
    }

    /**
     * Check if user has permission to perform action
     */
    public function hasPermission(User $user, string $permission, User $targetUser = null): bool
    {
        return match ($permission) {
            'view_users' => in_array($user->role, ['admin', 'staff']),
            'view_statistics' => in_array($user->role, ['admin', 'staff']),
            'view_user_details' => in_array($user->role, ['admin', 'staff']),
            'update_user' => $user->role === 'admin',
            'toggle_user_status' => $user->role === 'admin' && ($targetUser ? $targetUser->id !== $user->id : true),
            'delete_user' => $user->role === 'admin' && ($targetUser ? $targetUser->id !== $user->id : true),
            default => false
        };
    }
}