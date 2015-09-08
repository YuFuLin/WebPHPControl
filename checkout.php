<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Checkout extends CI_Controller {
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index($product_id=1)
	{
		$this->load->helper('url');
		$this->load->model(array('Templates_model','Solution_model'));
		$this->load->library( array('session') );
		$data['user']        = $this->session->userdata('user');
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/templates');
			redirect('/no_login');
		}
		
			
		//All solution
		$Solution_all = $this->Solution_model->get_solutions(); 
		$data['solution_all']= $Solution_all;
		
		//Solution choosing
		$data['box_solution']= array (
			'solution_use' => $this->session->userdata('solution_use'),
			'solution_all' => $Solution_all
		);
		
		$data['user']        = $this->session->userdata('user');
		$data['solution_use']= $this->session->userdata('solution_use');
		$data['product_id']  = $this->session->userdata('product_id');

		
		$data['product'] = $this->Templates_model->getTemplateDetail($data['product_id']);
		
	
		$this->load->view('checkout', $data);
	}
	
	
	
	public function addorder(){
		$this->load->helper('url');
		$this->load->model(array('Orders_model'));
		$this->load->library( array('session') );
		
		$data         =$this->input->post();
		$solution_use =$this->session->userdata('solution_use');
		
		//templates and solution
		$data['solution_id'] = $solution_use['solution_id'];
	    $data['product_id']  = $this->session->userdata('product_id');
		$data['user']        = $this->session->userdata('user');
		
		//order record
	    $record  = $this->Orders_model->add_build_order($data['user']['user_id'], $data);
		
		//order_id
		if(is_int($record)){
			$warring =0;
			$name    =0;
			$alipay  = $this->alipay($record); 
			
		}else{
			$str     =explode('.',$record);
			$warring =1;
			$record  =$str['0'];  //Reminder
			$name    =$str['1'];  //Array name
			$alipay  ='';
		}
		
		$json = array(
				'warring'  => $warring,
				'record'   => $record,
				'name'     => $name,
				'alipay'   => $alipay
			);
        echo json_encode($json);	
		
	}
	
	public function unpaidpayment(){
		$order_id =$this->input->post('order_id');
			if(!empty($order_id)){
				$return = $this->alipay($order_id,'unpaid');
				echo $return ;
			}
		}
	
	
	private  function alipay($order_id){
		$this->load->library('session');
		$user = $this->session->userdata('user');
        $this->load->model(array('Orders_model'));
		$order = $this->Orders_model->get_an_order($order_id);;
		if ( (int)$order['user_id'] != (int)$user['user_id'] ) {
			die();
		}
	
		$sulotion          = $order['code'].'.'.$order['solution'];
        $checkoutTotal     = $order['amount'];
        $roturl            = $this->config->item('base_url').'checkout/callback'; 
		$ClientBackURL     = $this->config->item('base_url').'account';
		$OrderResultURL    = $this->config->item('base_url').'account';
		$PaymentInfoURL    = $this->config->item('base_url').'checkout/paymentinfo';
		
		if($order['type']=='CVS'){
			$ChooseSubPayment = 'FAMILY';
		}elseif($order['type']=='WebATM'){
		    $ChooseSubPayment = 'ESUN';
	    }else{
			$ChooseSubPayment='';
		}
		
		$Desc_1            = '感謝您購買LAZYWeb 服務。';
		$MerchantTradeDate = date('Y/m/d H:i:s');
		$MerchantID        ='1045404';
		$Hash_key          ='L7hrnmzUIZse26WF';
		$HashIV            ='vrwOK7ZQqwAjJKj8';
		
		$parameter='ChoosePayment='.$order['type'].'&'.
		'ChooseSubPayment='.$ChooseSubPayment.'&'.
		'ClientBackURL='.$ClientBackURL.'&'.
		'ItemName='.$sulotion.'&'.
		'MerchantID='.$MerchantID.'&'.
		'MerchantTradeDate='.$MerchantTradeDate.'&'.
		'MerchantTradeNo='.$order['order_id'].'&'.
		'OrderResultURL='.$OrderResultURL.'&'.
		'PaymentInfoURL='.$PaymentInfoURL.'&'.
		'PaymentType=aio&'.
		'ReturnURL='.$roturl.'&'.
		'TotalAmount='.$checkoutTotal.'&'.
		'TradeDesc=LAZYWeb-網頁設計專家';	

		$str_checkcode = 'HashKey='.$Hash_key.'&'.$parameter.'&HashIV='.$HashIV;
		$CheckMacValue = strtoupper(md5(strtolower(urlencode($str_checkcode))));

		$data['def_url']  = "<form name='form1' method='post' action='https://payment.allpay.com.tw/Cashier/AioCheckOut'>";
		$data['def_url'] .= "<input type='hidden' name='CheckMacValue' value='".$CheckMacValue."'>";
		$data['def_url'] .= "<input type='hidden' name='ChooseSubPayment' value='".$ChooseSubPayment."'>";
		$data['def_url'] .= "<input type='hidden' name='ChoosePayment' value='".$order['type']."'>";
		$data['def_url'] .= "<input type='hidden' name='ClientBackURL' value='".$ClientBackURL."'>";
		$data['def_url'] .= "<input type='hidden' name='ItemName' value='".$sulotion."'>";
		$data['def_url'] .= "<input type='hidden' name='MerchantID' value='".$MerchantID."'>";
		$data['def_url'] .= "<input type='hidden' name='MerchantTradeDate' value='".$MerchantTradeDate."'>";
		$data['def_url'] .= "<input type='hidden' name='MerchantTradeNo' value='".$order['order_id']."'>";
		$data['def_url'] .= "<input type='hidden' name='OrderResultURL' value='".$OrderResultURL."'>";
		$data['def_url'] .= "<input type='hidden' name='PaymentInfoURL' value='".$PaymentInfoURL."'>";
		$data['def_url'] .= "<input type='hidden' name='PaymentType' value='aio'>";
		$data['def_url'] .= "<input type='hidden' name='ReturnURL' value='".$roturl."'>";
		$data['def_url'] .= "<input type='hidden' name='TotalAmount' value='".$checkoutTotal."'>";
		$data['def_url'] .= "<input type='hidden' name='TradeDesc' value='LAZYWeb-網頁設計專家'>";
		$data['def_url'] .= "<button id='button-quote' style='opacity:0'>歐付寶付款</button>";
		$data['def_url'] .= "</form>";
		return $data['def_url'];	
	}
	

	public function paymentinfo(){
		$this->load->model(array('Payment_model','Mail_model'));
		
		
		$paymentinfo = $this->input->post(); 
			$data['order_id']   = $paymentinfo['MerchantTradeNo'];
			$data['trade_no']   = $paymentinfo['TradeNo'];
			$data['expire_ts']  = $paymentinfo['ExpireDate']; 
			$data['rtncode']    = $paymentinfo['RtnCode'];
				

		if($paymentinfo['RtnCode']=='2'){
			$data['bank_code']    = $paymentinfo['BankCode'];   
			$data['virtual_code'] = $paymentinfo['vAccount'];   	
		}
		if($paymentinfo['RtnCode']=='10100073'){
			$data['virtual_code'] = $paymentinfo['PaymentNo'];   
		}
		
        $this->Payment_model->update_payment($data);
		   
		
			$m_data['order_id'] = $paymentinfo['MerchantTradeNo'];
			$m_data['tpl']      = 'tpl_order';
			$m_data['pay_ts']   = $paymentinfo['ExpireDate'];
			$this->Mail_model->sendmail($m_data);	
			
		
	}
	
	
	public function callback()
	{
		$callback = $this->input->post();

		if( (int)$callback['RtnCode'] !== 1 ) {
			die();
		}
		
		$this->load->model(array('Website_model','Orders_model','Payment_model','Mail_model'));
		$order = $this->Orders_model->get_an_order( (int)$callback['MerchantTradeNo'] );
		$paystatus = $this->Payment_model->payment_status((int)$callback['MerchantTradeNo']);
		
		if ( empty($order) || ($paystatus['is_success']=='Y')) {
			die();
		}

		$data['order_id']   = $callback['MerchantTradeNo'];
        $data['is_success'] = 'Y';
		$data['trade_no']   = $callback['TradeNo'];
		$data['rtn_msg']    = $callback['RtnMsg'];
		$data['pay_ts']     = $callback['PaymentDate'];
        $this->Payment_model->update_payment($data);
	    $this->Website_model->update_status($order['website_id'], 'preparing');

		switch ($order['type'])
		{
			case 'Credit':
				$pay ='信用卡';
				break;
			case 'ATM':
				$pay ='ATM';
				break;
				default:
				$pay ='超商繳費';
		}
		
		
		$data['name']       = $order['name'];
		$data['email']      = $order['email'];
		$data['tpl']        = 'tpl_success';
		$data['payment']    = $pay;
		$data['amount']     = $order['amount'];
		$data['pay_time']   = $data['pay_ts'];
		$data['order_id']   = $order['order_id'];
		$data['solution']   = $order['code'].'.'.$order['solution'];
		$data['product_id'] = $order['product_id'];
		$data['thumb']      = "http://demo.lazyweb.com.tw/images/demo/large/".$order['thumbnail'];
		$data['demo']       = "http://demo.lazyweb.com.tw/demo/".$order['product_id'];
		$data['subject']    = '訂單#'.$order['order_id'].'付款 成功';
		
		
		$this->Mail_model->sendmail($data);
		
		$msn['phone'] =$order['phone'];
		$msn['message']='[LAZYWeb-網頁設計專家]-親愛的'.$order['name'].'，感謝您使用LAZYWev服務，您的訂單編號為'.$order['order_id'].'，詳細資訊可至會員中心查詢。';
		$this->Mail_model->sms($msn);	

	}
	
	public function use_solution(){
	  	$this->load->library( array('session') );
		$solution_use=$this->session->userdata('solution_use');
		
		$json = array(
				'price'  => $solution_use['build_price'],
				'id'     => $solution_use['solution_id'],
				'name'	 => $solution_use['name'],
				'code'   => $solution_use['code']
			);
     echo json_encode($json);	
	}
	
	
	public function use_coupon($coupon_serial)
	{	
		$this->load->model(array('Coupon_model'));
        $coupon = $this->Coupon_model->get_coupon_by_serial($coupon_serial);

		switch($coupon){
		  case 'serial':
			 $record="優惠碼不存在";
		  break;
		  case 'order_id':
			 $record="優惠碼已使用";
		  break; 
		  case 'active':
			 $record="優惠碼已停用";
		  break;
		  case 'valid_ts':
			 $record="優惠碼尚未生效";
		  break;
		  case 'expire_ts':
			 $record="優惠碼已過期";
		  break;		
		  default:
		     $record="使用成功";	 
		}
		
		if(isset($coupon['coupon_id'])){
		   $amount    =$coupon['amount'];
		   $coupon_id =$coupon['coupon_id'];
		 }else{
		   $amount    =0;
		   $coupon_id ='';
		 }
		 
		$json = array(
				'record'        => $record,
				'amount'        => $amount ,	
				'coupon_id'     => $coupon_id
			);
		
	    echo json_encode($json);
	}
	
	
}