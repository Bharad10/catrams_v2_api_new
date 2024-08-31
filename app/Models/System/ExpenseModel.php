<?php

namespace App\Models\System;

use CodeIgniter\Model;

class ExpenseModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'expenses';
    protected $primaryKey       = 'exp_id';
    protected $allowedFields    = [
        'exp_id',
        'exp_name',
        'exp_cost',
        'exp_desc',
        'exp_created_by',
        'exp_created_on',
        'exp_updated_by',
        'exp_updated_on',
        'exp_delete_flag',
    ];
}
