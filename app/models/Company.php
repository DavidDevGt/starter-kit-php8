<?php

namespace App\Models;

use App\Config\Database;

class Company extends Model
{
    protected $table = 'company';

    public $id;
    public $company_name;
    public $company_nit;
    public $company_address;
    public $company_postal_code;
    public $company_phone_principal;
    public $company_phone_secondary;
    public $company_email_principal;
    public $company_email_secondary;
    public $company_website;
    public $company_logo_url;
    public $company_country_id;
    public $active;
    public $created_at;
    public $updated_at;
}
