<?php 
/**
 * 订单记录
 */
require APP_ROOT_PATH.'app/Lib/page.php';
require_once APP_ROOT_PATH."system/model/user.php";
class dcorderModule extends HizBaseModule
{
    
	function __construct()
	{
        parent::__construct();
        global_run();

    }
	
    
		public function index()
	{	
		init_app_page();
		$s_account_info = $GLOBALS['hiz_account_info'];

		$account_id = $s_account_info['id'];
		/* 业务逻辑部分 */
		$conditions .= " where agency_id = ".$account_id; // 查询条件
		
		$supplier_list=$GLOBALS['db']->getAll(" select * from " . DB_PREFIX . "supplier".$conditions);
		$s_id=array();
		foreach($supplier_list as $k=>$v){
		    $s_id[]=$v['id'];
		}
		
		$GLOBALS['tmpl']->assign("supplier_list", $supplier_list);
		$id_count=count($supplier_list);
		require_once APP_ROOT_PATH."system/model/dc.php";

		$sn = strim($_REQUEST['sn']);
		$supplier_id = strim($_REQUEST['supplier_id']);
		$begin_time = strim($_REQUEST['begin_time']);
		$end_time = strim($_REQUEST['end_time']);
		$order_status = $_REQUEST['order_status'];
		$pay_type = $_REQUEST['pay_type'];

		if(!$begin_time){
			$begin_time_s=to_timespan(to_date(NOW_TIME,"Y-m-d"));
			$begin_time=to_date($begin_time_s,'Y-m-d H:i');
		}else{	
			$begin_time_s = to_timespan($begin_time,"Y-m-d H:i");
		}
		
		if(!$end_time){
			$end_time_s=to_timespan(to_date(NOW_TIME,"Y-m-d"))+3600*24-1;
			$end_time=to_date($end_time_s,'Y-m-d H:i');
		}else{	
			$end_time_s = to_timespan($end_time,"Y-m-d H:i");	
		}
		
		
		$condition = "";
		if($sn!="")
			$condition .=" and (order_sn like '%".$sn."%' or mobile like '%".$sn."%') ";
		if($begin_time_s)
			$condition .=" and create_time > ".$begin_time_s." ";
		if($end_time_s)
			$condition .=" and create_time < ".$end_time_s." ";
		if($order_status>0)
		{
			if($order_status==1)
			$condition .=" and confirm_status = 0 and is_cancel=0 ";
			if($order_status==2)
			$condition .=" and confirm_status = 1 and is_cancel=0 ";
			if($order_status==3)
			$condition .=" and confirm_status=2 and is_cancel=0 ";	
			if($order_status==4)
			$condition .=" and is_cancel > 0";	
		}
			
		if($pay_type > 0)
		{
			if($pay_type==1)
			$condition .=" and payment_id = 0 and pay_status = 1";
			if($pay_type==2)
			$condition .=" and payment_id = 1 ";
		}else{
			
			$condition .=" and (pay_status = 1 or payment_id=1)";
		}
		if($supplier_id)
			$condition .=" and supplier_id = ".$supplier_id." ";

		
		//	print_r($pay_type);die;
		$GLOBALS['tmpl']->assign("sn",$sn);
		$GLOBALS['tmpl']->assign("begin_time",$begin_time);
		$GLOBALS['tmpl']->assign("end_time",$end_time);
		$GLOBALS['tmpl']->assign("order_status",$order_status);
		$GLOBALS['tmpl']->assign("pay_type",$pay_type);
		$GLOBALS['tmpl']->assign("supplier_id",$supplier_id);
	    //分页
	    $page_size = 8;
	    $page = intval($_REQUEST['p']);
	    if($page==0) $page = 1;
	    $limit = (($page-1)*$page_size).",".$page_size;
	    
	    $sql = "select * from ".DB_PREFIX."dc_order where supplier_id in (".implode(",",$s_id).") and type_del = 0 and is_rs = 0 $condition order by id desc limit ".$limit;
	    $sql_count = "select count(id) from ".DB_PREFIX."dc_order where supplier_id in (".implode(",",$s_id).") and type_del = 0 and is_rs = 0 $condition ";
	    $list = $GLOBALS['db']->getAll($sql);
	    $total = $GLOBALS['db']->getOne($sql_count);
		$count=count($list);
	    foreach($list as $k=>$v){
	    
	    	$list[$k]['sort']=$count-$k;
	    	$list[$k]['supplier_name']= $GLOBALS['db']->getOne("select name from ".DB_PREFIX."supplier where id=".$v['supplier_id']);
	    	$order_menu=unserialize($v['order_menu']);
	    	$order_promote=unserialize($v['promote_str']);
	    	$list[$k]['order_promote']=$order_promote;
	    	
	    	if($v['order_delivery_time']==1){
	    		//立即送达，商户从下单时间后4小时，可以确认订单
	    		$list[$k]['over_time']=$v['create_time']+3600*4;
	    	}elseif($v['order_delivery_time']>1){
	    		//有具体配送时间，商户从送达时间后4小时，可以确认订单
	    		$list[$k]['over_time']=$v['order_delivery_time']+3600*4;
	    	}
	    	$list[$k]['now']=NOW_TIME;
	    	$list[$k]['create_time'] = to_date($v['create_time']);
	  
			$m_cart_list=$order_menu['menu_list'];
	    	$list[$k]['m_cart_list']=$m_cart_list;
	    	$list[$k]['pay_price']=$v['total_price']-$v['ecv_money']-$v['promote_amount'];
	    	$list[$k]['index']=$total-$k-$page_size*($page-1);

	    	
	    }
	    
	    
	    $page = new Page($total,$page_size);   //初始化分页对象
	    $p  =  $page->show();
	    $GLOBALS['tmpl']->assign('pages',$p);
	
	    $GLOBALS['tmpl']->assign("id_count",$id_count);
		
	    $GLOBALS['tmpl']->assign("list",$list); 		
		 $GLOBALS['tmpl']->assign("ajax_url", url("hiz","dcorder"));

		$GLOBALS['tmpl']->assign("head_title","外卖订单记录");
		$GLOBALS['tmpl']->display("pages/dcorder/index.html");	
		

	
	}
	
	
	
	public function accept_order()
	{	
		init_app_page();
		$s_account_info = $GLOBALS['hiz_account_info'];
		
		$id = intval($_REQUEST['id']);

		 $order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."dc_order where type_del = 0 and is_rs = 0 and id=".$id);
		
		
		if(!$order_info)
		{
			 
	        $root['status'] = 0;
	        $root['info'] ="数据不存在";
	        ajax_return($root);
	  
		}
		
		 if(!in_array($order_info['location_id'], $s_account_info['location_ids']))
		 {
	        $root['status'] = 0;
	        $root['info'] ="没有管理权限";
	        ajax_return($root);
	   	 }
		
	   	 if($order_info['order_delivery_time']==1){
	   	 	//立即送达超过两小时不接单，直接关闭订单
	   	 	if(NOW_TIME-$order_info['create_time'] > 3600 * 2){
	   	 		require_once  APP_ROOT_PATH."system/model/dc.php";
	   	 		
	   	 		$root['status'] = 0;
	   	 		$root['jump'] = url("hiz","dcorder#index");
	   	 		$root['info'] ="请在用户下单后2小时内接单，接单超时，订单关闭";
		   	 	$close_reason='商家接单超时，订单关闭';
				dc_order_close($id,2,$close_reason);
	   	 		ajax_return($root);	
	   	 	}
	   	 }elseif($order_info['order_delivery_time'] > 10000){
	   	 	//有具体送达时间，超过送达时间，直接关闭订单
	   	 	if(NOW_TIME > $order_info['order_delivery_time']){
		   	 	require_once  APP_ROOT_PATH."system/model/dc.php";
		   	 	
		   	 	$root['status'] = 0;
		   	 	$root['jump'] = url("hiz","dcorder#index");
		   	 	$root['info'] ="超过用户配送时间，接单超时，订单关闭";
		   	 	$close_reason='商家接单超时，订单关闭';
				dc_order_close($id,2,$close_reason);
		   	 	ajax_return($root);
	   	 	}
	   	 }
		
			$GLOBALS['db']->query("update ".DB_PREFIX."dc_order set confirm_status = 1 where id = ".$id);
			$rs=$GLOBALS['db']->affected_rows();
			if($rs> 0){
				$root['status'] = 1;
				$root['jump'] = url("hiz","dcorder#index");
				$root['info'] ="接单成功";
				ajax_return($root);
				
			}else{
				$root['status'] = 0;
				$root['info'] ="已接单，不用重复操作";
				ajax_return($root);
				
			}

		
	}
	
	
	public function close_order()
	{
		init_app_page();
		$s_account_info = $GLOBALS['hiz_account_info'];
		$id = intval($_REQUEST['id']);
		$close_reason=strim($_REQUEST['close_reason'])==''?strim($_REQUEST['close_reason_text']):strim($_REQUEST['close_reason']);	
		$order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."dc_order where type_del = 0 and is_rs = 0 and id=".$id);
		
		
		if(!$order_info)
		{
			 
	        $root['status'] = 0;
	        $root['info'] ="数据不存在";
	        ajax_return($root);
	  
		}
		
		if(!in_array($order_info['location_id'], $s_account_info['location_ids']))
		 {
	        $root['status'] = 0;
	        $root['info'] ="没有管理权限";
	        ajax_return($root);
	   	 }
	   	 
	   	 require_once  APP_ROOT_PATH."system/model/dc.php";
	   	
	   	 $root['status'] = 1;
	  	 $root['jump'] = url("hiz","dcorder#index");
	   	 $root['info'] ="关闭交易成功";
	 	dc_order_close($id,2,$close_reason);
	     ajax_return($root);
		
	}
	
	public function over_order()
	{	
		init_app_page();
		$s_account_info = $GLOBALS['hiz_account_info'];
		
		$id = intval($_REQUEST['id']);

		 $order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."dc_order where type_del = 0 and is_rs = 0 and pay_status = 1 and id=".$id);
		
		
		if(!$order_info)
		{
			 
	        $root['status'] = 0;
	        $root['info'] ="数据不存在";
	        ajax_return($root);
	  
		}
		
		 if(!in_array($order_info['location_id'], $s_account_info['location_ids']))
		 {
	        $root['status'] = 0;
	        $root['info'] ="没有管理权限";
	        ajax_return($root);
	   	 }
	   	 require_once  APP_ROOT_PATH."system/model/dc.php";
	   	 $result=dc_confirm_delivery($id);
	   	 $result['jump'] = url("hiz","dcorder#index");
		 ajax_return( $result);
		
	}
	
	public function close_tip()
	{
		$id = intval($_REQUEST['id']);
		$form_url=url("hiz","dcorder#close_order",array('id'=>$id));
		$GLOBALS['tmpl']->assign("form_url",$form_url);
		$GLOBALS['tmpl']->assign("is_rs",0);
		$GLOBALS['tmpl']->display("pages/dc/close_tip.html");
	}
	
	
	
		/**
	 * 打印小票
	 */
	public function print_order()
	{
	
		$s_account_info = $GLOBALS['hiz_account_info'];
		
		$id = intval($_REQUEST['id']);

		 $order_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."dc_order where type_del = 0 and id=".$id);
		
		
		if(!$order_info)
		{			 
	        $root['status'] = 0;
	        $root['info'] ="数据不存在";
	        ajax_return($root);  
		}
		
		 if(!in_array($order_info['location_id'], $s_account_info['location_ids']))
		 {
	        $root['status'] = 0;
	        $root['info'] ="没有管理权限";
	        ajax_return($root);
	   	 }
	   	 require_once APP_ROOT_PATH."system/model/dc.php";
	   	 $order_info['pay_status_format']=get_order_state($order_info);
	   	 $order_info['order_menu']=unserialize($order_info['order_menu']);
	   	 $order_info['order_promote']=unserialize($order_info['promote_str']);
	   	 $order_info['pay_price']=$order_info['total_price']-$order_info['ecv_money']-$order_info['promote_amount'];
	   	
	   	 $GLOBALS['tmpl']->assign("order_info",$order_info);

	     $info=$GLOBALS['tmpl']->fetch("pages/dc/print_order.html");
	   	 
	     $root['status'] = 1;
	     $root['info'] =$info;
	   	 ajax_return($root);
	}


}
?>