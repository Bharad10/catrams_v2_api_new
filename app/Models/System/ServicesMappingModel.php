<?php

namespace App\Models\System;

use CodeIgniter\Model;

class ServicesMappingModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'services_mapping';
    protected $primaryKey       = 'srm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'srm_id',
        'srm_servpack_id',
        'srm_tool_id',
        'srm_total_cost',
        'srm_created_by',
        'srm_created_on',
        'srm_updated_by',
        'srm_updated_on',
        'srm_delete_flag',


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
