<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_master';
    protected $primaryKey       = 'cstm_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'cstm_id', 'cstm_cstp_id', 'cstm_name', 'cstm_password', 'cstm_salutation', 'cstm_cstr_id','cstm_phone', 'cstm_alternate_num','cstm_country_code',
        'cstm_email', 'cstm_state', 'cstm_city','cstm_gstin','cstm_dealer_name' ,'cstm_address', 'cstm_location', 'cstm_profile_photo', 'cstm_created_by', 'cstm_created_on', 'cstm_updated_by', 'cstm_updated_on', 'cstm_delete_flag','cstm_status_flag', 'fcm_token_web', 'fcm_token_mobile','cstm_type',
        'cstm_vendor_flag','cstm_vendor_percent'
    ];
//cstm_vendor_flag :0-no vendor 1-vendor

}
