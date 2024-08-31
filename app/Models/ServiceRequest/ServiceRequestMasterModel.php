<?php

namespace App\Models\ServiceRequest;

use CodeIgniter\Model;

class ServiceRequestMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'servicerequest_master';
    protected $primaryKey       = 'serm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['serm_id','serm_reopen_desc','serm_discount_amount','serm_custpay_amount','serm_pay_alert','serm_pay_amount','serm_reopen_by','serm_wkc_date','serm_reopen_flag','serm_ad_type','serm_ad_charge','serm_ad_charge_cost','serm_assigne','serm_vendor_flag','serm_hold_reason','serm_custid','serm_vehid','serm_number','serm_complaint','serm_hold_flag','serm_cost','serm_status','serm_reject_user','serm_reject_cust','serm_createdby','serm_createdon','serm_updatedby','serm_updatedon','serm_deleteflag','serm_active_flag'];


    
}
