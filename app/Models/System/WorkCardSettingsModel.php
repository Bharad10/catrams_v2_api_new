<?php

namespace App\Models\System;

use CodeIgniter\Model;

class WorkCardSettingsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'workcard_settings';
    protected $primaryKey       = 'ws_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'ws_id',
        'ws_rp_days',
        'ws_created_by',
        'ws_created_on',
        'ws_updated_on',
        'ws_updated_by',
        'ws_delete_flag',

    ];

}
