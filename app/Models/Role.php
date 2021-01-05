<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    protected $fillable = ['name', 'guard_name', 'description', 'company_id', 'is_public'];

    public function permissions(): BelongsToMany
    {
        $permissions = parent::permissions();

        if ($user = Auth::user()) {
            if ($companyId = $user->company_id ?? $this->company_id) {
                if ($company = Company::find($companyId)) {
                    if ($company->activeSubscription) {
                        if ($company->activeSubscription->permissions) {
                            $permissionIds = $company->activeSubscription->permissions->pluck('id');
                            $permissions = $permissions->whereIn('id', $permissionIds);
                        }
                    }
                }
            }
        }

        return $permissions;
    }
}
