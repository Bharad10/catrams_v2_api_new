<?php

namespace App\Models\Chat;

use CodeIgniter\Model;

class ServicesChatModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'service_chat_details';
    protected $primaryKey       = 'sc_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'sc_id',
        'sc_req_id',
        'sc_req_type',
        'sc_us_type',
        'sc_message_type',
        'sc_message',
        'sc_created_on',
        'sc_customer_id',
        'sc_staff_id',
        'sc_updated_on',
        'sc_status',
        'sc_delete_flag'
    ];

       // <-----------------Details---------------->
    // 'sc_us_type'=>'	0-FROM USERS 1-FROM CUSTOMER',
    // 'sc_message_type'=>'	0-TEXT MESSAGE 1-AUDIO MESSAGE 2-PICTURE/PHOTO MESSAGE 3-VIDEO MESSAGE	'
    // 'sc_status'=>'0-SENDING 1-DELIVERED 2-READ'
    // 'sc_req_type'=>0-service request ,1-tool rent request, 2-order request
    // <---------------------------------------->

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
