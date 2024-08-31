<?php

namespace App\Models\System;

use CodeIgniter\Model;

class OrderItemsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'order_items';
    protected $primaryKey       = 'oitem_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'oitem_id',
        'oitem_order_id',
        'oitem_tool_id',
        'oitem_cost',
        'oitem_quantity',
        'oitem_created_on',
        'oitem_created_by',
        'oitem_updated_on',
        'oitem_updated_by',
        'oitem_delete_flag'
    ];

}
