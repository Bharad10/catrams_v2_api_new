<?php

namespace App\Models\User;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'users';
    protected $primaryKey       = 'us_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['us_id','us_firstname','us_lastname','us_email','us_phone','us_password','us_role_id','us_date_of_joining','us_created_on','us_created_by','us_updated_on','us_updated_by','us_delete_flag','last_login','login_status','fcm_token_web','fcm_token_mobile'];
}
