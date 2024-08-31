<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomersubUsersModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_subusers';
    protected $primaryKey       = 'csub_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'csub_id',
        'csub_custmasterId',
        'csub_custroleId', //1-Technician @todoo for now	
        'csub_name',
        'csub_email',
        'csub_password',
        'csub_phone',
        'csub_reference', //Limited Requests
        'csub_statusflagId',
        'csub_countrycodeId',
        'csub_fcm_token',
        'csub_vendorflagId',
        'csub_delete_flag'
    ];
}