<?php

namespace App\Controllers\Shipment;

use App\Models\Media\RequestMediaModel;
use App\Models\Shipment\ShipmentMasterModel;
use App\Models\Shipment\ShipmentTrackingModel;
use CodeIgniter\RESTful\ResourceController;

class ShipmentMasterController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }

    public function create_shipment($shipmast,$shiptrack){
        $shipmentmasterModel= new ShipmentMasterModel();
        $shipmentitemsModel = new ShipmentTrackingModel();
        
        $m_data=[];
        $t_data=[];
        $m_data=$shipmast;
        $shm_id=$shipmentmasterModel->insert($m_data);
        $t_data=$shiptrack;
        $t_data['shtrack_shm_id']=$shm_id;
        $shtrack__id=$shipmentitemsModel->insert($t_data);

        
        return $shm_id;
    }
}
