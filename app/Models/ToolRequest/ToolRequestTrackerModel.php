<?php

namespace App\Models\ToolRequest;

use CodeIgniter\Model;

class ToolRequestTrackerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tool_request_tracker';
    protected $primaryKey       = 'trt_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['trt_id','trt_rq_id','trt_type','trt_url_type','trt_url','trt_notes','trt_created_on','trt_created_by','trt_updated_on','trt_updated_by','trt_delete_flag'];
}
//	trt_type= 0-from customer 1-from User  2-amount 3-tool recieved by customer