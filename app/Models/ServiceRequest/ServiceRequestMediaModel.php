<?php

namespace App\Models\ServiceRequest;

use CodeIgniter\Model;

class ServiceRequestMediaModel extends Model
{
    protected $table            = 'servicerequest_media';
    protected $primaryKey       = 'smedia_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['smedia_id','smedia_url','smedia_type','smedia_sereqid','smedia_createdby','smedia_createdon','smedia_updatedby','smedia_updatedon','smedia_deleteflag'];
    
}
