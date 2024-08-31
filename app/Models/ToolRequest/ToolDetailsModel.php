<?php

namespace App\Models\ToolRequest;

use CodeIgniter\Model;

class ToolDetailsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'tool_details';
    protected $primaryKey       = 'tool_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = 
[   'tool_id',
    'tool_name',
    'tool_description',
    'tool_images',
    'tool_active_flag',
    'tool_total_quantity',
    'tool_cost',
    'tool_sale_quantity',
    'tool_rent_id',
    'tool_rent_cost',
    'tool_rent_quantity',
    'tool_delay_percentage',
    'tool_deposit_id',
    'tool_deposit_price',
    'tool_adv_payment',
    'tool_adv_price',
    'tool_updated_on',
    'tool_created_on',
    'tool_created_by',
    'tool_delete_flag'
];
}

//tool_active_flag : 0-active 1-inactive
// tool_rent_id: 0-no rent 1- rent
//  tool_deposit_id:0-no deposit 1-deposit
// tool_adv_payment: 0 no pay 1 pay
