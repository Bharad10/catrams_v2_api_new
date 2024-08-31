<?php

namespace App\Models\System;

use CodeIgniter\Model;

class NotificationmasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'notification_master';
    protected $primaryKey       = 'nt_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nt_id',
        'nt_type',
        'nt_request_type',
        'nt_type_id',
        'nt_req_number',
        'nt_sourceid',
        'nt_destid',
        'nt_sourcetype',
        'nt_header',
        'nt_content',
        'nt_targeturl',
        'nt_stcode',
        'nt_read',
        'nt_status',
        'nt_created_on',
        'nt_deleteflag'
                                  ];


    //nt_request_type---->0 -service request , 1- tool request , 2- order request
    //nt_type_id ---> Request Id or Chat Id
    // nt_sourcetype  --------> 0-for user to customer 1- for customer to user 2
    //nt_read----------------->	0- for unread 1- for read
    //nt_type----------------->	0- for notification 1- for Chat Notification
    
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
