<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerRoleMappingModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_role_mapping';
    protected $primaryKey       = 'cfrm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cfrm_id',
        'cfrm_role_id',
        'cfrm_feature_id',
        'cfrm_action_id'
    ];

}
