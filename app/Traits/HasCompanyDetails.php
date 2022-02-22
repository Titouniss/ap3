<?php

namespace App\Traits;

use App\Models\CompanyDetails;

trait HasCompanyDetails
{
    public static $detailsFields = [
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
    ];

    public function getSiretAttribute()
    {
        return $this->details->siret;
    }

    public function setSiretAttribute($value)
    {
        $this->details->siret = $value;
    }

    public function getCodeAttribute()
    {
        return $this->details->code;
    }

    public function setCodeAttribute($value)
    {
        $this->details->code = $value;
    }

    public function getTypeAttribute()
    {
        return $this->details->type;
    }

    public function setTypeAttribute($value)
    {
        $this->details->type = $value;
    }

    public function getContactFirstnameAttribute()
    {
        return $this->details->contact_firstname;
    }

    public function setContactFirstnameAttribute($value)
    {
        $this->details->contact_firstname = $value;
    }

    public function getContactLastnameAttribute()
    {
        return $this->details->contact_lastname;
    }

    public function setContactLastnameAttribute($value)
    {
        $this->details->contact_lastname = $value;
    }

    public function getContactFunctionAttribute()
    {
        return $this->details->contact_function;
    }

    public function setContactFunctionAttribute($value)
    {
        $this->details->contact_function = $value;
    }

    public function getContactTel1Attribute()
    {
        return $this->details->contact_tel1;
    }

    public function setContactTel1Attribute($value)
    {
        $this->details->contact_tel1 = $value;
    }

    public function getContactTel2Attribute()
    {
        return $this->details->contact_tel2;
    }

    public function setContactTel2Attribute($value)
    {
        $this->details->contact_tel2 = $value;
    }

    public function getContactEmailAttribute()
    {
        return $this->details->contact_email;
    }

    public function setContactEmailAttribute($value)
    {
        $this->details->contact_email = $value;
    }

    public function getStreetNumberAttribute()
    {
        return $this->details->street_number;
    }

    public function setStreetNumberAttribute($value)
    {
        $this->details->street_number = $value;
    }

    public function getStreetNameAttribute()
    {
        return $this->details->street_name;
    }

    public function setStreetNameAttribute($value)
    {
        $this->details->street_name = $value;
    }

    public function getPostalCodeAttribute()
    {
        return $this->details->postal_code;
    }

    public function setPostalCodeAttribute($value)
    {
        $this->details->postal_code = $value;
    }

    public function getCityAttribute()
    {
        return $this->details->city;
    }

    public function setCityAttribute($value)
    {
        $this->details->city = $value;
    }

    public function getCountryAttribute()
    {
        return $this->details->country;
    }

    public function setCountryAttribute($value)
    {
        $this->details->country = $value;
    }

    public function getAuthorizeSupplyAttribute()
    {
        return $this->details->authorize_supply;
    }

    public function setAuthorizeSupplyAttribute($value)
    {
        $this->details->authorize_supply = $value;
    }

    /**
     * The associated details
     */
    public function details()
    {
        if ($this->morphOne(CompanyDetails::class, 'detailable')->doesntExist()) {
            CompanyDetails::create([
                'detailable_id' => $this->id,
                'detailable_type' => static::class,
            ]);
        }
        return $this->morphOne(CompanyDetails::class, 'detailable');
    }
}
