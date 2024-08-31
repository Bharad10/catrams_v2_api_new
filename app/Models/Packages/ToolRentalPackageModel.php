<?php

namespace App\Models\Packages;

use CodeIgniter\Model;

class ToolRentalPackageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tool_rental_package';
    protected $primaryKey       = 'tlrp_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['tlrp_id','tlrp_name','tlrp_days','tlrp_cost','tlrp_tool_id','tlrp_updated_on','tlrp_added_on','tlrp_created_on','tlrp_delete_flag','tool_delay_price','tool_discount','tool_adv_price','tool_adv_payment'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
