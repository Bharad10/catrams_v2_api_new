<?php

namespace App\Models\Quotation;

use CodeIgniter\Model;

class QuoteMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quote_master';
    protected $primaryKey       = 'qtm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['qtm_id','qtm_ad_charge','qtm_ad_charge_cost','qtm_ad_type','qtm_custid','qtm_vehid','qtm_type','qtm_rejected_reason','qtm_serm_id','qtm_number','qtm_complaint_name','qtm_cost','qtm_created_by','qtm_created_on','qtm_updated_by','qtm_updated_on','qtm_delete_flag','qtm_status_id'];


    //qtm_type -0 service, 1 tool
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
