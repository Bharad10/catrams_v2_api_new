<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerFeatureActionsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_feature_actions';
    protected $primaryKey       = 'cfa_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cfa_id',
        'cfa_name'
    ];

}
