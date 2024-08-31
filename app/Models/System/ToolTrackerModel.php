<?php

namespace App\Models\System;

use CodeIgniter\Model;

class ToolTrackerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tool_tracker';
    protected $primaryKey       = 'trk_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        
        'trk_id',
        'trk_tool_id',
        'trk_type',
        'trk_status',
        'trk_rq_id',
        'trk_quant',
        'trk_created_by',
        'trk_created_on',
        'trk_updated_by',
        'trk_updated_on',
        'trk_delete_flag',
    ];

}
