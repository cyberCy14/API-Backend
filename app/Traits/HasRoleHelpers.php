<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Collection;

trait HasRoleHelpers
{
    /**
     * Check if user is a superadmin
     */
    public function isSuperAdmin($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasRole('super_admin'); // Shield uses 'super_admin' with underscore
    }

    /**
     * Check if user is a handler
     */
    public function isHandler($user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasRole('handler');
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles, $user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasAnyRole($roles);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role, $user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasRole($role);
    }

    /**
     * Check if user can access a specific company
     */
    public function canAccessCompany($companyId, $user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->canAccessCompany($companyId);
    }

    /**
     * Get companies available to the current user
     */
    public function getAvailableCompanies($user = null): Collection
    {
        $user = $user ?: Auth::user();

        if (!$user) return collect();

        if ($this->isSuperAdmin($user)) {
            return Company::where('is_active', true)->get();
        }

        if ($this->isHandler($user)) {
            return $user->companies()->where('is_active', true)->get();
        }

        return collect();
    }

    /**
     * Get company options for select fields
     */
    public function getCompanyOptions($user = null): array
    {
        return $this->getAvailableCompanies($user)->pluck('company_name', 'id')->toArray();
    }

    /**
     * Check if current user can access the resource
     * FIXED: Updated to use Shield's role names
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Updated to use Shield's role naming convention
        return $user->hasAnyRole(['super_admin', 'handler']);
    }

    /**
     * Get user's accessible company IDs
     */
    public function getAccessibleCompanyIds($user = null): array
    {
        $user = $user ?: Auth::user();

        if (!$user) return [];

        if ($this->isSuperAdmin($user)) {
            return Company::where('is_active', true)->pluck('id')->toArray();
        }

        if ($this->isHandler($user)) {
            return $user->getCompanyIds();
        }

        return [];
    }

    /**
     * Apply company filtering to a query based on user permissions
     */
    public function applyCompanyFiltering($query, $companyIdField = 'company_id', $user = null)
    {
        $user = $user ?: Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // Return no results
        }

        if ($this->isSuperAdmin($user)) {
            return $query; // No filtering for superadmin
        }

        if ($this->isHandler($user)) {
            $companyIds = $user->getCompanyIds();
            return $query->whereIn($companyIdField, $companyIds);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Get default company ID for current user
     */
    public function getDefaultCompanyId($user = null): ?int
    {
        $user = $user ?: Auth::user();

        if (!$user) return null;

        if ($this->isSuperAdmin($user)) {
            return Company::where('is_active', true)->first()?->id;
        }

        if ($this->isHandler($user)) {
            return $user->getPrimaryCompany()?->id;
        }

        return null;
    }

    /**
     * Check if user should have company selection disabled (handlers with single company)
     */
    public function shouldDisableCompanySelection($user = null): bool
    {
        $user = $user ?: Auth::user();

        if (!$user) return true;

        if ($this->isSuperAdmin($user)) {
            return false; // Superadmin can always select
        }

        if ($this->isHandler($user)) {
            return $user->companies()->count() <= 1; // Disable if single company
        }

        return true; // Disable for other roles
    }
}