<?php

namespace App\Models\ServiceRequest;

use CodeIgniter\Model;

class ServiceRequestItemsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'servicerequest_items';
    protected $primaryKey       = 'sitem_id';
    protected $allowedFields    = ['sitem_id',
    'sitem_reference',
    'sitem_assignee_type',
    'sitem_paid_status', //    0-Payment Not  Completed  1-Custom Amount Paid  2-Payment Complete
    'sitem_assignee',
    'sitem_vendor',
    'sitem_hold_reason',
    'sitem_active_flag',
    'sitem_serid',
    'sitem_itemid',
    'sitem_hold_flag',
    'sitem_status_flag',
    'sitem_type',
    'sitem_cost',
    'sitem_createdby',
    'sitem_createdon',
    'sitem_updatedby',
    'sitem_updatedon',
    'sitem_deleteflag'
];


}
