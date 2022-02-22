<?php

namespace App\Traits;

use App\Models\Company;

trait HasCompany
{
    /**
     * The associated company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id')->withTrashed();
    }
}
