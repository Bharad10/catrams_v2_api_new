<?php

namespace App\Models\Products;

use CodeIgniter\Model;

class ProductMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'product_master';
    protected $primaryKey       = 'pm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'pm_id',
        'pm_name',
        'pm_code',
        'pm_sl_nm',
        'pm_created_by',
        'pm_created_on',
        'pm_updated_by',
        'pm_updated_on',
        'pm_delete_flag',
    ];

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
