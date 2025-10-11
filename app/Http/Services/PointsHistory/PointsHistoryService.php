<?php

namespace App\Http\Services\PointsHistory;

use App\Models\PointsHistory;
use App\Models\User;

class PointsHistoryService
{
    /**
     * Get user's points history with filters
     */
    public function getUserPointsHistory(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $user->pointsHistory()
            ->with(['creator:id,fullname,email,role'])
            ->when($filters['type'] ?? null, fn($query, $type) => $query->byType($type))
            ->when($filters['source'] ?? null, fn($query, $source) => $query->bySource($source))
            ->when($filters['from_date'] ?? null, fn($query, $fromDate) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn($query, $toDate) => $query->whereDate('created_at', '<=', $toDate))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get user points statistics
     */
    public function getUserPointsStatistics(User $user): array
    {
        return [
            'current_balance' => $user->points,
            'total_earned' => $user->pointsHistory()->where('points', '>', 0)->sum('points'),
            'total_spent' => abs($user->pointsHistory()->where('points', '<', 0)->sum('points')),
            'total_transactions' => $user->pointsHistory()->count()
        ];
    }

    /**
     * Add points manually for user
     */
    public function addPointsManually(User $user, array $data, User $creator): PointsHistory
    {
        // Thêm điểm với lịch sử
        return $user->addPoints(
            $data['points'],
            $data['type'],
            'manual',
            $data['description'],
            [
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $creator->id
            ]
        );
    }

    /**
     * Find points history by ID
     */
    public function findPointsHistoryById(int $id): PointsHistory
    {
        return PointsHistory::with(['user:id,fullname,email', 'creator:id,fullname,email,role'])
            ->findOrFail($id);
    }

    /**
     * Check if user can view points history
     */
    public function canViewPointsHistory(User $currentUser, PointsHistory $pointsHistory): bool
    {
        // Admin/staff can view all, customers can only view their own
        return in_array($currentUser->role, ['admin', 'staff']) || 
               ($currentUser->role === 'customer' && $pointsHistory->user_id === $currentUser->id);
    }

    /**
     * Check if user has permission for points operations
     */
    public function hasPermission(User $user, string $permission): bool
    {
        return match ($permission) {
            'view_user_history' => in_array($user->role, ['admin', 'staff']),
            'add_points_manually' => $user->role === 'admin',
            default => false
        };
    }

    /**
     * Get points history statistics for admin
     */
    public function getPointsHistoryStatistics(): array
    {
        $totalEarned = PointsHistory::where('points', '>', 0)->sum('points');
        $totalSpent = abs(PointsHistory::where('points', '<', 0)->sum('points'));
        
        return [
            'total_transactions' => PointsHistory::count(),
            'total_earned' => $totalEarned,
            'total_spent' => $totalSpent,
            'net_points' => $totalEarned - $totalSpent,
            'transactions_by_type' => PointsHistory::selectRaw('type, COUNT(*) as count, SUM(points) as total_points')
                ->groupBy('type')
                ->get()
                ->keyBy('type')
                ->toArray(),
            'recent_transactions' => PointsHistory::with(['user:id,fullname,email', 'creator:id,fullname,email'])
                ->latest()
                ->limit(10)
                ->get(),
            'top_earners' => User::where('role', 'customer')
                ->latest('points')
                ->limit(10)
                ->select('id', 'fullname', 'email', 'points')
                ->get()
        ];
    }

    /**
     * Get points history by filters for admin
     */
    public function getAllPointsHistory(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PointsHistory::with(['user:id,fullname,email', 'creator:id,fullname,email,role'])
            ->when($filters['user_id'] ?? null, fn($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['type'] ?? null, fn($query, $type) => $query->byType($type))
            ->when($filters['source'] ?? null, fn($query, $source) => $query->bySource($source))
            ->when($filters['from_date'] ?? null, fn($query, $fromDate) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($filters['to_date'] ?? null, fn($query, $toDate) => $query->whereDate('created_at', '<=', $toDate))
            ->when($filters['search'] ?? null, fn($query, $search) => $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }
}