<?php

namespace App\Models\Chat;

use CodeIgniter\Model;

class ChatDetailsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'chat_details';
    protected $primaryKey       = 'c_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [

        'c_id',
        'c_us_type',
        'c_message_type',
        'c_message',
        'c_created_on',
        'c_customer_id',
        'c_staff_id',
        'c_updated_on',
        'c_status',
        'c_delete_flag'
    ];

    // <-----------------Details---------------->
    // 'c_us_type'=>'	0-FROM USERS 1-FROM CUSTOMER 2-FROM EXPERT',
    // 'c_message_type'=>'	0-TEXT MESSAGE 1-AUDIO MESSAGE 2-VIDEO MESSAGE',
    // 'c_status'=>'0-SENDING 1-DELIVERED 2-READ'
    // <---------------------------------------->
}
