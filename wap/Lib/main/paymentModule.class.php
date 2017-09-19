<?php
// +----------------------------------------------------------------------
// | Fanwe 方维o2o商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(97139915@qq.com)
// +----------------------------------------------------------------------


class paymentModule extends MainBaseModule
{

	
	public function done()
	{
		global_run();
		init_app_page();
		$id = intval($_REQUEST['id']);
		
		$data = call_api_core("payment","done",array("id"=>$id));
		if(!$data['status'])
		{
			showErr($data['info']);
		}
		if($data['pay_status']==1)
		{
			if($data['pin_status']>0){
				//修改拼团表
				$order_id = $data['order_id'];
				$GLOBALS['db']->query("update ".DB_PREFIX."open_group set order_id = '".$order_id."' where id = ".$data['pin_status']);
				
			}
			$data['page_title'] = "支付结果";
			$GLOBALS['tmpl']->assign("data",$data);
			$GLOBALS['tmpl']->display("payment_done.html");
		}
		else
		{
			$data['payment_code']['page_title'] = "订单付款";
			$GLOBALS['tmpl']->assign("data",$data['payment_code']);
			$GLOBALS['tmpl']->display("payment_pay.html");
		}
		
	}
	
	public function order_share(){
	    global_run();
	    init_app_page();
	    $id = intval($_REQUEST['id']);
	    $is_share = intval($_REQUEST['is_share']);

	    if($is_share){
	        $data = call_api_core("payment","order_share",array("id"=>$id));
	        if($data['user_login_status']!=LOGIN_STATUS_LOGINED){
	            app_redirect(wap_url("index","user#login"));
	            exit;
	        }
	    }
	    app_redirect(wap_url("index","uc_order#index",array('pay_status'=>1)));
	}
		
}
?>