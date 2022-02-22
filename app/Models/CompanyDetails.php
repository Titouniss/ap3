<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyDetails extends Model
{
    protected $fillable = [
        'detailable_id',
        'detailable_type',
        'siret',
        'code',
        'type',
        'contact_firstname',
        'contact_lastname',
        'contact_function',
        'contact_tel1',
        'contact_tel2',
        'contact_email',
        'street_number',
        'street_name',
        'postal_code',
        'city',
        'country',
        'authorize_supply'
    ];

    public $timestamps = false;

    public function detailable()
    {
        return $this->morphTo();
    }
}
