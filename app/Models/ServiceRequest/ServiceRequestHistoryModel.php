<?php

namespace App\Models\ServiceRequest;

use CodeIgniter\Model;

class ServiceRequestHistoryModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'serv_req_history';
    protected $primaryKey       = 'srh_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['srh_id','srh_serm_id','srh_status_id','srh_created_on','srh_created_by','srh_updated_on','srh_updated_by','srh_delete_flag'];
}
