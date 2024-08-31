<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerRolesModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_roles';
    protected $primaryKey       = 'cstr_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cstr_id','cstr_name','cstr_description','cstr_created_on','cstr_updated_by','cstr_updated_on','cstr_delete_flag'];
}
