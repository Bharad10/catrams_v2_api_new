<?php
namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}
/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->post("user_login","Login::user_login");
$routes->resource('TestController',['filter' => 'authFilter']);








$routes->get("fetch_feature", "System\FeatureController::fetch_feature", ['filter' => 'authFilter']);

$routes->resource("ToolRequest/ToolRequestMasterController", ['filter' => 'authFilter']);
$routes->resource("System/UserRoleController", ['filter' => 'authFilter']);
$routes->resource("System/ExpenseMasterController", ['filter' => 'authFilter']);
$routes->resource("System/UsersNotificationController", ['filter' => 'authFilter']);
$routes->post("fetch_user_role_features", "System\UserRoleController::fetch_user_role_features", ['filter' => 'authFilter']);


$routes->post("delete_user_role", "System/ToolRequestMasterController::delete_user_role", ['filter' => 'authFilter']);
$routes->get("get_pend_tr", "ToolRequest\ToolRequestMasterController::get_pend_tr", ['filter' => 'authFilter']);
$routes->post("tool_req_create", "ToolRequest/ToolRequestMasterController::tool_req_create", ['filter' => 'authFilter']);
$routes->post("tool_req_accept", "ToolRequest\ToolRequestMasterController::tool_req_accept", ['filter' => 'authFilter']);
$routes->post("due_Date_adjust", "ToolRequest\ToolRequestMasterController::due_Date_adjust", ['filter' => 'authFilter']);
$routes->post("refund_calc", "ToolRequest\ToolRequestMasterController::refund_calc", ['filter' => 'authFilter']);

$routes->post('tool_req_list', 'ToolRequest\ToolRequestMasterController::tool_req_list', ['filter' => 'authFilter']);
$routes->post('hold_tlrq', 'ToolRequest\ToolRequestMasterController::hold_tlrq', ['filter' => 'authFilter']);
$routes->post("completed_request", "ToolRequest/ToolRequestMasterController::completed_request", ['filter' => 'authFilter']);
$routes->post("tool_req_history", "ToolRequest/ToolRequestMasterController::tool_req_history", ['filter' => 'authFilter']);
$routes->post("tool_req_details", "ToolRequest/ToolRequestMasterController::tool_req_details", ['filter' => 'authFilter']);
$routes->post("fetch_request_status", "ToolRequest/ToolRequestMasterController::fetch_request_status", ['filter' => 'authFilter']);
$routes->get("fetch_toolreq_hist", "ToolRequest/ToolRequestMasterController::fetch_toolreq_hist", ['filter' => 'authFilter']);
$routes->get("fetch_inspection_list", "ToolRequest\ToolRequestMasterController::fetch_inspection_list", ['filter' => 'authFilter']);
$routes->post("payment_complete", "ToolRequest\ToolRequestMasterController::payment_complete", ['filter' => 'authFilter']);
$routes->get("clear_notification", "System\UsersNotificationController::clear_notification", ['filter' => 'authFilter']);
$routes->post("clear_us_notif", "System\UsersNotificationController::clear_us_notif", ['filter' => 'authFilter']);
$routes->post("damagedtool_insp", "ToolRequest\ToolRequestMasterController::damagedtool_insp", ['filter' => 'authFilter']);
$routes->post("damagedtoolreq_updt", "ToolRequest\ToolRequestMasterController::damagedtoolreq_updt", ['filter' => 'authFilter']);



$routes->post("status_master_controller", "ToolRequest\ToolRequestMasterController::status_master_controller", ['filter' => 'authFilter']);
$routes->post("status_master", "Status\StatusMasterController::status_master", ['filter' => 'authFilter']);

$routes->post("tool_create", "System\ToolMasterController::tool_create", ['filter' => 'authFilter']);
$routes->post("fetch_tool_details", "System\ToolMasterController::fetch_tool_details", ['filter' => 'authFilter']);
$routes->post("update_tool_details", "System\ToolMasterController::update_tool_details", ['filter' => 'authFilter']);
$routes->post("delete_tool_pack", "System\ToolMasterController::delete_tool_pack", ['filter' => 'authFilter']);
$routes->post("tool_track_list", "System\ToolMasterController::tool_track_list", ['filter' => 'authFilter']);
$routes->post("update_tool_status", "System\ToolMasterController::update_tool_status", ['filter' => 'authFilter']);
$routes->post("stock_update", "System\ToolMasterController::stock_update", ['filter' => 'authFilter']);

$routes->post("ban_img_upload", "System\BannerController::banner_img_upload");

$routes->post("customerlogin", "Auth\PreAuthentication::customer_login");
$routes->post("customersignup", "Auth\PreAuthentication::customer_signup");
$routes->post("getcustomerdetails", "Auth\PreAuthentication::get_customerdetails");

$routes->post("delcust", "Customer/CustomerMasterController::delete_cust", ['filter' => 'authFilter']);

$routes->post("job_complete", "Vendor\VendorMasterController::job_complete", ['filter' => 'authFilter']);
$routes->post("item_confirm_expert", "Vendor\VendorMasterController::item_confirm_expert", ['filter' => 'authFilter']);
$routes->post("get_item_det", "Vendor\VendorMasterController::get_item_det", ['filter' => 'authFilter']);
$routes->post("fetch_vendor_details", "Vendor\VendorMasterController::fetch_vendor_details", ['filter' => 'authFilter']);
$routes->post("vendorreject_request", "Vendor\VendorMasterController::vendorreject_request", ['filter' => 'authFilter']);
$routes->get("vendorcompleted_requestbyid", "Vendor\VendorMasterController::vendorcompleted_requestbyid", ['filter' => 'authFilter']);

$routes->post("vendor_Assign", "Customer\CustomerMasterController::vendor_Assign", ['filter' => 'authFilter']);
$routes->post("vendor_Assign_update", "Customer\CustomerMasterController::vendor_update", ['filter' => 'authFilter']);
$routes->post("vendor_update", "Customer\CustomerMasterController::vendor_update", ['filter' => 'authFilter']);
$routes->post("premiumreq_bycust", "Customer\CustomerMasterController::premiumreq_bycust", ['filter' => 'authFilter']);
$routes->get("get_customer_types", "Customer\CustomerMasterController::get_customer_types");

$routes->post("cust_create", "Customer/CustomerMasterController::create_customer", ['filter' => 'authFilter']);
$routes->post("update_customer", "Customer/CustomerMasterController::update_customer", ['filter' => 'authFilter']);
$routes->post("update_customer_by_mobile", "Customer/CustomerMasterController::update_customer_by_mobile", ['filter' => 'authFilter']);

$routes->get("getuserroles", "Customer/CustomerMasterController::get_customer_roles", ['filter' => 'authFilter']);

$routes->resource("Customer/CustomerMasterController", ['filter' => 'authFilter']);
$routes->resource("Vendor/VendorMasterController", ['filter' => 'authFilter']);
$routes->get("vend_serm_list", "Vendor\VendorMasterController::vend_serm_list", ['filter' => 'authFilter']);
$routes->post("vquote_details", "Vendor\VendorMasterController::vquote_details", ['filter' => 'authFilter']);
$routes->post("v_quote", "Vendor\VendorMasterController::v_quote", ['filter' => 'authFilter']);
$routes->post("v_quote_approval", "Vendor\VendorMasterController::v_quote_approval", ['filter' => 'authFilter']);
$routes->post("vendor_payment", "Vendor\VendorMasterController::vendor_payment", ['filter' => 'authFilter']);
$routes->post("create_service_request", "ServiceRequest\ServiceRequestMasterController::create_service_request", ['filter' => 'authFilter']);
$routes->get("getreq_by_role", "ServiceRequest\ServiceRequestMasterController::getreq_by_role", ['filter' => 'authFilter']);


$routes->get("service_request_list", "ServiceRequest/ServiceRequestMasterController::service_request_list", ['filter' => 'authFilter']);
$routes->post("update_assigne", "ServiceRequest\ServiceRequestMasterController::update_assigne", ['filter' => 'authFilter']);


$routes->post("userlogin", "Auth\PreAuthentication::user_login");

$routes->post("fetch_service_details", "ServiceRequest/ServiceRequestMasterController::fetch_service_details", ['filter' => 'authFilter']);

$routes->post("fetch_service_pack", "ServiceRequest\ServiceRequestMasterController::fetch_service_pack", ['filter' => 'authFilter']);

$routes->post("update_serv_details", "ServiceRequest/ServiceRequestMasterController::update_serv_details", ['filter' => 'authFilter']);

$routes->post("serv_pack_create", "ServiceRequest\ServiceRequestMasterController::serv_pack_create", ['filter' => 'authFilter']);

$routes->get("vehicle_make_list", "ServiceRequest\ServiceRequestMasterController::vehicle_make_list", ['filter' => 'authFilter']);

$routes->post("vehicle_model_list", "ServiceRequest\ServiceRequestMasterController::vehicle_model_list", ['filter' => 'authFilter']);

$routes->post("vehicle_varient_list", "ServiceRequest\ServiceRequestMasterController::vehicle_varient_list", ['filter' => 'authFilter']);

$routes->post("check_password", "System\UserMasterController::check_password", ['filter' => 'authFilter']);
$routes->post("edit_profile", "System\UserMasterController::edit_profile", ['filter' => 'authFilter']);
$routes->get("fetch_user", "System\UserMasterController::fetch_user", ['filter' => 'authFilter']);
$routes->post("create_user", "System\UserMasterController::create_user", ['filter' => 'authFilter']);
$routes->get("us_logout", "System\UserMasterController::us_logout", ['filter' => 'authFilter']);
$routes->post("update_admin", "System\UserMasterController::update_admin", ['filter' => 'authFilter']);
$routes->get("get_userdet", "System\UserMasterController::get_userdet", ['filter' => 'authFilter']);
$routes->resource("System/UserMasterController", ['filter' => 'authFilter']);
$routes->resource("Order/OrderMasterController", ['filter' => 'authFilter']);
$routes->get("get_order_list", "Order\OrderMasterController::get_order_list", ['filter' => 'authFilter']);
$routes->post("order_r_img", "Order\OrderMasterController::order_r_img", ['filter' => 'authFilter']);
$routes->post("order_payment", "Order\OrderMasterController::order_payment", ['filter' => 'authFilter']);
$routes->post("order_recievedcust", "Order\OrderMasterController::order_recievedcust", ['filter' => 'authFilter']);
$routes->get("order_history", "Order\OrderMasterController::order_history", ['filter' => 'authFilter']);
$routes->post("update_Date", "Order\OrderMasterController::update_Date", ['filter' => 'authFilter']);
$routes->post("completed_and_rejected_orders", "Order\OrderMasterController::completed_and_rejected_orders", ['filter' => 'authFilter']);
$routes->resource("ServiceRequest/ServiceRequestMasterController", ['filter' => 'authFilter']);
$routes->post("recieve_payment_order", "Order\OrderMasterController::recieve_payment_order", ['filter' => 'authFilter']);
$routes->post("cash_on_delivery", "Order\OrderMasterController::cash_on_delivery", ['filter' => 'authFilter']);

$routes->resource("System/ServiceMasterController", ['filter' => 'authFilter']);
$routes->resource("Payment/PaymentMasterController", ['filter' => 'authFilter']);
$routes->post("initiate_alert", "Payment\PaymentMasterController::initiate_alert", ['filter' => 'authFilter']);
$routes->resource("System/BannerController", ['filter' => 'authFilter']);
$routes->get("fetch_pay_hist", "Payment\PaymentMasterController::fetch_pay_hist", ['filter' => 'authFilter']);
$routes->post("fetch_specific_pay_hist", "Payment\PaymentMasterController::fetch_specific_pay_hist", ['filter' => 'authFilter']);
$routes->post("initiatePayment", "Payment\PaymentMasterController::initiatePayment", ['filter' => 'authFilter']);
$routes->post("recieve_transaction", "Payment\PaymentMasterController::recieve_transaction");


$routes->resource("System/ToolMasterController", ['filter' => 'authFilter']);
$routes->resource("Quote/QuoteMasterController", ['filter' => 'authFilter']);
$routes->post("send_quote", "ServiceRequest/ServiceRequestMasterController::send_quote", ['filter' => 'authFilter']);
$routes->post("service_requestbycustomer", "ServiceRequest/ServiceRequestMasterController::service_requestbycustomer", ['filter' => 'authFilter']);
$routes->post("quotedetailsby_requestid", "Quote/QuoteMasterController::quotedetailsby_requestid", ['filter' => 'authFilter']);
$routes->post("serv_history", "ServiceRequest\ServiceRequestMasterController::serv_history_byid", ['filter' => 'authFilter']);
$routes->post("reject_quote", "Quote\QuoteMasterController::reject_quote", ['filter' => 'authFilter']);
$routes->resource("WorkCard/WorkCardMasterController", ['filter' => 'authFilter']);
$routes->post("servicestatus_update", "WorkCard\WorkCardMasterController::servicestatus_update", ['filter' => 'authFilter']);
$routes->post("delete_service", "WorkCard/WorkCardMasterController::delete_service", ['filter' => 'authFilter']);
$routes->post("hold_workcard", "WorkCard/WorkCardMasterController::hold_workcard", ['filter' => 'authFilter']);
$routes->post("workcard_unhold", "WorkCard/WorkCardMasterController::workcard_unhold", ['filter' => 'authFilter']);
$routes->post("getworkcard_Details", "WorkCard/WorkCardMasterController::getworkcard_Details", ['filter' => 'authFilter']);
$routes->post("add_service", "WorkCard/WorkCardMasterController::add_service", ['filter' => 'authFilter']);

$routes->post("holdjob_by_cust", "WorkCard\WorkCardMasterController::holdjob_by_cust", ['filter' => 'authFilter']);
$routes->post("unholdjob_by_cust", "WorkCard\WorkCardMasterController::unholdjob_by_cust", ['filter' => 'authFilter']);
$routes->get("getwork_by_role", "WorkCard\WorkCardMasterController::getwork_by_role", ['filter' => 'authFilter']);
$routes->post("service_request_history", "ServiceRequest/ServiceRequestMasterController::service_request_history", ['filter' => 'authFilter']);

$routes->post("testcheck", "Quote/QuoteMasterController::testcheck", ['filter' => 'authFilter']);

$routes->post("reject_request", "ServiceRequest\ServiceRequestMasterController::reject_request", ['filter' => 'authFilter']);

$routes->get("fetch_history", "ServiceRequest\ServiceRequestMasterController::fetch_history", ['filter' => 'authFilter']);

$routes->post("imageupload", "Customer/CustomerController::imageupload", ['filter' => 'authFilter']);
$routes->post("imagecUpload", "Customer/CustomerController::imagecUpload", ['filter' => 'authFilter']);
$routes->post("profilpic_upload", "Customer/CustomerController::profilpic_upload", ['filter' => 'authFilter']);
$routes->post("tlrq_img_upload", "ToolRequest\ToolRequestrackerControler::tlrq_img_upload");

$routes->post("holdreq_by_cust", "WorkCard\WorkCardMasterController::holdreq_by_cust", ['filter' => 'authFilter']);
$routes->post("holdreq_by_user", "WorkCard\WorkCardMasterController::holdreq_by_user", ['filter' => 'authFilter']);
$routes->post("unholdreq_by_cust", "WorkCard\WorkCardMasterController::unholdreq_by_cust", ['filter' => 'authFilter']);
$routes->post("unholdreq_by_user", "WorkCard\WorkCardMasterController::unholdreq_by_user", ['filter' => 'authFilter']);
$routes->post("holdjob_by_user", "WorkCard\WorkCardMasterController::holdjob_by_user", ['filter' => 'authFilter']);
$routes->post("unholdjob_by_user", "WorkCard\WorkCardMasterController::unholdjob_by_user", ['filter' => 'authFilter']);
$routes->post("newservice_bycust", "WorkCard\WorkCardMasterController::newservice_bycust", ['filter' => 'authFilter']);
$routes->post("toolrecomendation", "WorkCard\WorkCardMasterController::toolrecomendation", ['filter' => 'authFilter']);
$routes->post("reopen_workcard", "WorkCard\WorkCardMasterController::reopen_workcard", ['filter' => 'authFilter']);
$routes->post("job_assign_expert", "WorkCard\WorkCardMasterController::job_assign_expert", ['filter' => 'authFilter']);
$routes->post("recommended_tool_Det", "WorkCard\WorkCardMasterController::recommended_tool_Det", ['filter' => 'authFilter']);
$routes->post("recommended_tool_confirm", "WorkCard\WorkCardMasterController::recommended_tool_confirm", ['filter' => 'authFilter']);
$routes->post("get_couponsforcustomer", "Coupon\CouponMasterController::get_couponsforcustomer", ['filter' => 'authFilter']);
$routes->post("delete_coupon", "Coupon\CouponMasterController::delete_coupon", ['filter' => 'authFilter']);
$routes->post("verify_signin_otp", "Auth\PreAuthentication::verify_signin_otp");

$routes->resource("Coupon/CouponMasterController", ['filter' => 'authFilter']);
$routes->resource("DashBoard/DashBoardController", ['filter' => 'authFilter']);
$routes->resource("Approval/ApprovalmasterControler", ['filter' => 'authFilter']);
$routes->post("update_hold_req", "Approval\ApprovalmasterControler::update_hold_req");

$routes->post("serv_payment", "ServiceRequest\ServiceRequestMasterController::serv_payment", ['filter' => 'authFilter']);
$routes->post("actvt_serm_rq", "ServiceRequest\ServiceRequestMasterController::actvt_serm_rq", ['filter' => 'authFilter']);
$routes->post("get_draftlist_cust", "ServiceRequest\ServiceRequestMasterController::get_draftlist_cust", ['filter' => 'authFilter']);
$routes->post("completed_requestbyid", "ServiceRequest\ServiceRequestMasterController::completed_requestbyid", ['filter' => 'authFilter']);
$routes->post("purchasequote_accept", "Quote\QuoteMasterController::purchasequote_accept", ['filter' => 'authFilter']);
$routes->post("getquotebytreqid", "Quote\QuoteMasterController::getquotebytreqid", ['filter' => 'authFilter']);
$routes->post("getquotebytreqid_formobile", "Quote\QuoteMasterController::getquotebytreqid_formobile", ['filter' => 'authFilter']);
$routes->post("recent_quotes", "Quote\QuoteMasterController::recent_quotes", ['filter' => 'authFilter']);
$routes->resource("ToolRequest/ToolRequestrackerControler", ['filter' => 'authFilter']);
$routes->post("fetch_sr_timeline", "ServiceRequest\ServiceRequestMasterController::fetch_sr_timeline", ['filter' => 'authFilter']);
$routes->get("serv_hist", "ServiceRequest\ServiceRequestMasterController::serv_hist", ['filter' => 'authFilter']);
$routes->get("gethist_by_role", "ServiceRequest\ServiceRequestMasterController::gethist_by_role", ['filter' => 'authFilter']);
$routes->post("check_appliedcoupon", "Coupon\CouponMasterController::check_appliedcoupon", ['filter' => 'authFilter']);
$routes->get("check_reopen_workcard", "ServiceRequest\ServiceRequestMasterController::check_reopen_workcard", ['filter' => 'authFilter']);

$routes->post("approval_for_hold", "Approval\ApprovalmasterControler::approval_for_hold");
$routes->post("approval_for_unhold", "Approval\ApprovalmasterControler::approval_for_unhold");
$routes->get("app_service", "Approval\ApprovalmasterControler::app_service");
$routes->post("request_rejected_hold", "Approval\ApprovalmasterControler::request_rejected_hold");
$routes->post("saledamaged_update", "Approval\ApprovalmasterControler::saledamaged_update");
$routes->get("getquote_byroleid", "Quote\QuoteMasterController::getquote_byroleid", ['filter' => 'authFilter']);
$routes->post("premium_approval", "Approval\ApprovalmasterControler::premium_approval",['filter' => 'authFilter']);
$routes->resource("Customer/CustomerSettingsController", ['filter' => 'authFilter']);
$routes->post("set_active_status", "Customer\CustomerSettingsController::set_active_status",['filter' => 'authFilter']);

$routes->resource("WorkCard/WorkCardSettingsController", ['filter' => 'authFilter']);
$routes->post("admin_login", "Auth\PreAuthentication::admin_login");
$routes->post("accept_request", "ServiceRequest\ServiceRequestMasterController::accept_request", ['filter' => 'authFilter']);
$routes->post("data_card_upload", "ServiceRequest\ServiceRequestMasterController::data_card_upload", ['filter' => 'authFilter']);
$routes->post("datacard_image_upload", "ServiceRequest\ServiceRequestMasterController::datacard_image_upload");
$routes->post("datacard_image_cUpload", "ServiceRequest\ServiceRequestMasterController::datacard_image_cUpload",['filter' => 'authFilter']);

$routes->resource("Chat/ChatMasterController", ['filter' => 'authFilter']);
$routes->post("get_chat_history", "Chat\ChatMasterController::get_chat_history", ['filter' => 'authFilter']);

$routes->post("fetch_sr_chathist", "ServiceRequest\ServiceRequestMasterController::fetch_sr_chathist");
$routes->post("create_service_chat", "ServiceRequest\ServiceRequestMasterController::create_service_chat");

$routes->post("c_img_upload", "Chat\ChatMasterController::c_img_upload");
$routes->post("c_img_cUpload", "Chat\ChatMasterController::c_img_cUpload");
$routes->post("c_aud_upload", "Chat\ChatMasterController::c_aud_upload");
$routes->post("c_aud_cUpload", "Chat\ChatMasterController::c_aud_cUpload");
$routes->post("c_doc_upload", "Chat\ChatMasterController::c_doc_upload");
$routes->post("c_doc_cUpload", "Chat\ChatMasterController::c_doc_cUpload");
$routes->post("work_card_activity_details", "WorkCard\WorkCardMasterController::work_card_activity_details", ['filter' => 'authFilter']);
$routes->post("tool_image_upload", "System\ToolMasterController::tool_image_upload");
$routes->post("tool_image_cUpload", "System\ToolMasterController::tool_image_cUpload");
$routes->resource("Customer/CustomersubUsersController", ['filter' => 'authFilter']);
$routes->post("get_customersubUsers", "Customer\CustomersubUsersController::get_customersubUsers");
$routes->post("subuser_login", "Customer\CustomersubUsersController::subuser_login");





//Customer
$routes->post("create_sub_users", "Customer\CustomerMasterController::create_sub_users", ['filter' => 'authFilter']);
$routes->get("getCustomer_roles", "Customer\CustomerSettingsController::getCustomer_roles",['filter' => 'authFilter']);
$routes->get("getCustomer_actions", "Customer\CustomerSettingsController::getCustomer_actions",['filter' => 'authFilter']);
$routes->post("assign_vendor", "Customer\CustomerSettingsController::assign_vendor", ['filter' => 'authFilter']);


//expert
$routes->post("vendor_status_update", "Vendor\VendorMasterController::vendor_status_update", ['filter' => 'authFilter']);