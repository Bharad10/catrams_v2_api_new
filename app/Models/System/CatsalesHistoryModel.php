<?php

namespace App\Models\System;

use CodeIgniter\Model;

class CatsalesHistoryModel extends Model
{
    protected $table            = 'cat_saleshistory';
    protected $primaryKey       = 'csh_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['csh_id','csh_customername','csh_phone','csh_email','csh_gstin','csh_invnumber','csh_productname','csh_productcode','csh_amount','csh_invdate'];
}
