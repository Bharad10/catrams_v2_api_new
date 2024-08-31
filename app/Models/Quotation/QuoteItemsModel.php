<?php

namespace App\Models\Quotation;

use CodeIgniter\Model;

class QuoteItemsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quote_items';
    protected $primaryKey       = 'qti_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['qti_id','qti_items_vendor','qti_qm_id','qti_item_id','qti_type','qti_cost','qti_created_by','qti_created_on','qti_updated_by','qti_updated_on','qti_deleted_flag'];

}
