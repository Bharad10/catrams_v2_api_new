<?php

namespace App\Models\Invoice;

use CodeIgniter\Model;

class InvoiceMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'invoice_master';
    protected $primaryKey       = 'invm_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['invm_id ','invm_invdet_id  ','invm_cstm_id  ','invm_date ','invm_created_by ','invm_created_on ','invm_updated_by ','invm_updated_on ','invm_delete_flag '];
}
