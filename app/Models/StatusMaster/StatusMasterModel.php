<?php

namespace App\Models\StatusMaster;

use CodeIgniter\Model;

class StatusMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'status_master';
    protected $primaryKey       = 'sm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sm_id','sm_name','sm_code','sm_pk_id','sm_delete_flag'];
}
