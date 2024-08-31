<?php

namespace App\Models\Media;

use CodeIgniter\Model;

class RequestMediaModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'request_media';
    protected $primaryKey       = 'rmedia_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'rmedia_id',
        'rmedia_type', //	0-image, 1-audio 2-video, 4-document	
        'rmedia_request_id',
        'rmedia_url_type', //0-service, 1-rent 2-order
        'rmedia_url',
        'rmedia_by_type', //0-user 1-customer
        'rmedia_created_on',
        'rmedia_created_by',
        'rmedia_updated_on',
        'rmedia_updated_by',
        'rmedia_delete_flag'
    ];
}
