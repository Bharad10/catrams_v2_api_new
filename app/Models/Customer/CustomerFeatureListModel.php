<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerFeatureListModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_feature_list';
    protected $primaryKey       = 'cft_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cft_id',
        'cft_name',
        'cft_description',
        'cft_created_on',
        'cft_created_by',
        'cft_updated_on',
        'cft_updated_by',
        'cft_delete_flag'
    ];

}
