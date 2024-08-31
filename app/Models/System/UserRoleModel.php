<?php

namespace App\Models\System;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'user_roles';
    protected $primaryKey       = 'role_Id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['role_Id','role_name','role_description','role_groupid','role_created_on','role_created_by','role_updated_on','role_updated_by','role_delete_flag'];

}
