<?php

namespace App\Models\System;

use CodeIgniter\Model;

class ExpenseTrackerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'expense_tracker';
    protected $primaryKey       = 'expt_id';
    protected $allowedFields    = [
        'expt_id',
        'expt_type',
        'expt_rq_id',
        'expt_name',
        'expt_cost',
        'expt_created_by',
        'expt_created_on',
        'expt_updated_by',
        'expt_updated_on',
        'expt_delete_flag',
    ];
}
