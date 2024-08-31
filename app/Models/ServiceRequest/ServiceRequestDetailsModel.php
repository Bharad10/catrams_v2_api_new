<?php

namespace App\Models\ServiceRequest;

use CodeIgniter\Model;

class ServiceRequestDetailsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'service_request_details';
    protected $primaryKey       = 'servdet_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['servdet_id','servdet_complaint_name','servdet_complaint_desc','servdet_cost','servdet_status_id'];
}
