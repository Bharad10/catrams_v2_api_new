<?php

namespace App\Models\Request;

use CodeIgniter\Model;

class RequestStatusMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'request_status_master';
    protected $primaryKey       = 'reqsts_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['reqsts_id ','reqsts_rm_id ','reqsts_tldt_id','reqsts_name ','reqsts_desc ','reqsts_servrq_id','reqsts_created_by ','reqsts_created_on ','reqsts_updated_by ','reqsts_updated_on ','reqsts_delete_flag '];
}
