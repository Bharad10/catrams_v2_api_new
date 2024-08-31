<?php

namespace App\Models\Invoice;

use CodeIgniter\Model;

class InvoiceDetailModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'invoice_detail';
    protected $primaryKey       = 'invdet_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['invdet_id ','invdet_amount ','invdet_discount ','invdet_subtotal ','invdet_expensetotal ','invdet_grandtotal ','invdet_VAT '];
}
