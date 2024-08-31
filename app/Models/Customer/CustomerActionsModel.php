<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerActionsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_actions';
    protected $primaryKey       = 'cactions_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cactions_id',
        'cactions_name',
        'cactions_delete_flag'
    ];


}
