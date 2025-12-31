<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Auth\Role;
use App\Models\Company\Company;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
        'is_active',
        'user_type',
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

    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_user'
        )->withPivot(['is_owner', 'is_active'])->withTimestamps();
    }

    public function rolesForCompany(int $companyId)
    {
        return $this->belongsToMany(
            Role::class,
            'company_user_role'
        )->wherePivot('company_id', $companyId);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'SUPER_ADMIN';
    }

    public function isAdmin(): bool
    {
        return in_array($this->user_type, ['SUPER_ADMIN', 'ADMIN']);
    }

    public function isStaff(): bool
    {
        return $this->user_type === 'STAFF';
    }

    /**
     * Cache key for user's company permission list.
     */
    public function companyPermissionCacheKey(int $companyId): string
    {
        // You can include updated_at to auto-bust cache when user changes,
        // but company permissions change via pivots; so we handle invalidation manually.
        return "perm:user:{$this->id}:company:{$companyId}";
    }

    /**
     * Load all permission slugs for this user within a company.
     * - Memoized in-memory per request
     * - Cached across requests for performance
     */
    public function companyPermissionSlugs(?int $companyId = null): array
    {
        if ($this->isSuperAdmin()) {
            return ['*'];
        }

        $companyId ??= (int) session('current_company_id');
        if (!$companyId) {
            return [];
        }

        // 1) Request-level memo (zero DB after first call in same request)
        static $memo = [];
        $memoKey = $this->id . ':' . $companyId;

        if (array_key_exists($memoKey, $memo)) {
            return $memo[$memoKey];
        }

        // 2) Persistent cache (Redis/file) to avoid DB across requests
        $cacheKey = $this->companyPermissionCacheKey($companyId);

        $slugs = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId) {

            /**
             * Single query:
             * - Validate user is active in company_user
             * - Resolve user's role in company_user_role
             * - Get role permissions via role_permission -> permissions.slug
             */
            return DB::table('company_user as cu')
                ->join('company_user_role as cur', function ($join) {
                    $join->on('cur.user_id', '=', 'cu.user_id')
                        ->on('cur.company_id', '=', 'cu.company_id');
                })
                ->join('role_permission as rp', 'rp.role_id', '=', 'cur.role_id')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('cu.company_id', $companyId)
                ->where('cu.user_id', $this->id)
                ->where('cu.is_active', true)
                ->where('p.is_active', true)
                ->pluck('p.slug')
                ->unique()
                ->values()
                ->all();
        });

        return $memo[$memoKey] = $slugs;
    }

    /**
     * Check a permission slug for current company.
     */
    public function hasCompanyPermission(string $permissionSlug, ?int $companyId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $slugs = $this->companyPermissionSlugs($companyId);

        // No wildcard for non-super-admins in this setup.
        return in_array($permissionSlug, $slugs, true);
    }

    /**
     * Invalidate cache when user's company access/role/permissions change.
     */
    public function forgetCompanyPermissionsCache(int $companyId): void
    {
        Cache::forget($this->companyPermissionCacheKey($companyId));

        // also clear request memo (safe no-op; new request anyway)
        // (memo is per request, so no action needed)
    }
}
