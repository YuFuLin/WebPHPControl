<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Account extends CI_Controller {

	public function __construct() {
           parent::__construct();
           $this->load->helper(array('url','html','form')); 
        }
	
	public function index(){
		
		$this->load->library('session');
		$this->load->model(array('Website_model','Payment_model'));
		$data['user'] = $this->session->userdata('user');
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}

		$my_website = $this->Website_model->get_user_websites($data['user']['user_id']);
		
		$data['website']  = $my_website;
				
		if(isset($data['website']['0']['website_id'])){
			$data['wid_default'] = $data['website']['0']['website_id'];
		}else{
			$data['wid_default']='';
		}
		
		
		
		//The check unpaid
		$unpaid = $this->Payment_model->get_unpaid($data['user']['user_id']);
		
		$data['unpaid_count'] = count($unpaid);
		/*
		echo '<pre>';
		print_r($unpaid);
		*/
		
		foreach($unpaid as $uk => $uv){
			
			switch($uv['type']){
			  case 'Credit':
				 $payment_type='信用卡';
			  break;
			  case 'ATM':
				 $payment_type='ATM';
			  break;
			  default: $payment_type='7-11 ibon';	
			}
			
			
			$data['unpaid'][]=array(
				'payment_id'  => $uv['payment_id'],
			    'amount'      => $uv['amount'],
				'virtual_code'=> $uv['virtual_code'],
				'bank_code'   => $uv['bank_code'],
				'type'        => $payment_type,
				'type_code'   => $uv['type'],
				'expire_ts'   => ($uv['expire_ts']=='0000-00-00 00:00:00')?'-':$uv['expire_ts'],
				'order_id'    => $uv['order_id']
				);
		
		}
		
		$this->load->view('account/account',$data);	
	}
	
	//=======sidemenu========
	private  function sidemenu($website_id){
		
		$this->load->model('Website_model');
		$this->load->library('session');
		$data['user'] = $this->session->userdata('user');	
		if(empty($data['user'])){
			redirect('/');
		}
		$data['website_id'] = $website_id;
		//Website list
		$data['my_website'] = $this->Website_model->get_user_websites($data['user']['user_id']);
		//Side menu
		$data['sidemenu'] = array(
			'website_id' => $website_id,
			'code'=> $data['my_website']['0']['code'],
			'ga'  => $data['my_website']['0']['set_ga'],
			'backstage'  => $data['my_website']['0']['set_backstage']
			);
		//Chosen Wewbsite
		$data['website'] = $this->Website_model->get_website($website_id);
		
		
		return $data;
	}
	
	public function myweb($website_id = '')
	{
		$this->load->library( array('session') );
		$this->load->model(array('Website_model','Templates_model'));

		$data['user'] = $this->session->userdata('user');	
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}

		if ( empty($website_id) ) 
		{
			$websites = $this->Website_model->get_user_websites($data['user']['user_id']);
			if ( !empty($websites) ) {
				$website_id = $websites[0]['website_id'];
			}
		}
		
		$data = $this->sidemenu($website_id);//sidemenu

		//website choosed
    $website = $this->Website_model->get_website_page_count($data['user']['user_id'],$website_id);
    $data['website'] = $website['record'];
		
		//Get template
		$product = $this->Templates_model->getTemplateDetail($data['website']['product_id']);
		$data['product']    = $product['record']['0'];
			
		$this->load->view('account/myweb',$data);	
	}	
	
	
	

	public function upload($website_id){

		$this->load->model(array('Upload_model')); 
		$data = $this->sidemenu($website_id);//sidemenu
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}
		$this->load->view('account/upload',$data);	

	}
	
	//file list
	public function fileslist($website_id){
		
		$this->load->model(array('Upload_model')); 
        $data = $this->sidemenu($website_id);//sidemenu
		
		$data['fileslist']   = $this->Upload_model->get_fileslist($website_id);;

		$this->load->view('account/files_list',$data);	
	
	}
	
	
	//Upload
	public function upload_file($website_id) {
		  $this->load->model(array('Upload_model'));
		  
	  if (!empty($_FILES)) {
		  $tempFile           = $_FILES['file']['tmp_name'];
		  $data['name']       = $_FILES['file']['name'];
		  $data['size']       = number_format($_FILES['file']['size']/1024, 1);
		  $data['website_id'] = $website_id;
		  $data['create_ts']  = date("Y-m-d H:i:s");
		  
		  $targetPath = getcwd() . '/uploads/';
		  $targetFile = $targetPath . $data['name'] ;
		  move_uploaded_file($tempFile, $targetFile);
		 
		  $this->Upload_model->add_file($data);
         }
    }
	
		
	
	public function viewpage($website_id){
		
		$this->load->model(array('Website_model'));
		$data = $this->sidemenu($website_id);//sidemenu公用區
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}
		
		$user_id = $data['user']['user_id'];
		$record = $this->Website_model->get_website_page_count($user_id, $website_id);
		if ( empty($record['record']) )
			show_404();

		$record['record']['used_page_count'] = 0;		
		foreach($record['rt']['website_page_rt'] as $rk => $rv)
		{
			if ( !empty($rv['website_id']) ) {
				$record['record']['used_page_count'] += (int)$rv['weight'];
			}
		}

		$data['total_page_count'] = $record['record']['total_page_count'];
		$data['used_page_count']  = $record['record']['used_page_count'];
        $data['remaining']        = $data['total_page_count']-$data['used_page_count'];
        $data['pages']            = $record['rt']['website_page_rt'];
		
		$this->load->view('account/viewpage',$data);
	}
	

	public function update_website_page(){	
		
		$this->load->model(array('Website_model'));
		$this->load->library('session');
		$data['user'] = $this->session->userdata('user');


		//更新網站所選擇的頁面
		//$new_page_id = $select_page;//頁面ID組成的array
		$website_id  = $this->input->post('website_id');
		$new_page_id = $this->input->post('new_page_id');
		

		if ( !is_array($new_page_id) ) {
			echo '參數錯誤'; die();//參數錯誤
		}
		
		$arr_page_id = array();
		foreach($new_page_id as $page_id) {
			$arr_page_id[] = (int)$page_id;
		}

		if ( !in_array(1, $arr_page_id) ) {
			echo '首頁為必選'; die();//檢查是否有選首頁
		}
		
		echo 'success';
		
		$this->Website_model->update_website_page($data['user']['user_id'], $website_id, $arr_page_id);
	
	}
	
	public function domain($website_id){
		
		$data = $this->sidemenu($website_id);//sidemenu公用區
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}
	
	
		$this->load->view('account/domain',$data);	
	}
	
	public function favorites($page = 0){
		
		$this->load->library( array('pagination','session'));
		$data['user'] = $this->session->userdata('user');	
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}
		$this->load->model(array('Favorite_model','Website_model'));

		
		$config['base_url'] = $this->config->item('base_url')."account/favorites/";
		$config['total_rows'] = $this->Favorite_model->getFavoritesTotal($data['user']['user_id']);
		$config['per_page'] = $this->config->item('favorite_per_page'); 
		$config['uri_segment'] = 4;
		$config['anchor_class'] = '';
		$config['cur_tag_open'] = '&nbsp;<b>';
		$config['cur_tag_close'] = '</b>';
		$config['last_link'] = '>>';
		$config['first_link'] = '<<';
		$this->pagination->initialize($config); 

		$data['page'] = $page;
		$data['record_count'] = $config['total_rows'];
		$data['page_count'] = ceil($config['total_rows'] / $config['per_page']);
		$data['pagination'] = $this->pagination->create_links();
		$data['favorites']  = $this->Favorite_model->get_favorites($data['user']['user_id'],$page);
		
		$my_website = $this->Website_model->get_user_websites($data['user']['user_id']);
		$data['website']  = $my_website;
		if(isset($data['website']['0']['website_id'])){
			$data['wid_default'] = $data['website']['0']['website_id'];
		}else{
			$data['wid_default']='';
		}
		
		
		$this->load->view('account/favorites',$data);	
		}
	
	public function example()
	{
		$this->load->model( array('Solution_model', 'Website_model') );
		
		$solution_id = 1;
		
		//取得方案，裡面有可選擇頁面數量
		$data['solution'] = $this->Solution_model->get_solution_by_id($solution_id);
		
		//選擇此方案可以選擇的頁面
		$data['solution_page_rt'] = $this->Solution_model->get_page_by_solution($solution_id);

		
		//取得使用者在此網站已選擇的頁面
		$user_id = 2;
		$website_id = 1;
		$data['website_page_rt'] = $this->Solution_model->get_page_by_website($user_id, $website_id);
		
		//已選擇頁面數量
		$data['total_selected_page'] = 0;
		foreach($data['website_page_rt'] as $page) {
			$data['total_selected_page'] += (int)$page['weight'];
		}

		//更新網站所選擇的頁面
		$new_page_id = array(1,3,5);//頁面ID組成的array
		$this->Website_model->update_website_page($website_id, $new_page_id);
		
		
		//檢查網站的所有權
		$this->Website_model->check_website_user($user_id, $website_id);		
		//更新網站的網域名稱
		$domain_name = 'xxx.com.tw';
		$this->Website_model->update_domain($website_id, $domain_name);
		
		
		//我的網站列表
		$data['my_website'] = $this->Website_model->get_user_websites($user_id);

		echo '<pre>';
		print_r($data);
	}
	
	//插入網址
	public function domain_insert(){
		
		$this->load->model( array('Website_model') );
		
		$website_id  = $this->input->post('website_id');
		$domain      = $this->input->post('domain');	
		
		$return = $this->Website_model->update_domain($website_id, $domain);
		
		echo $return;
			
	}
	
	public function domain_available()
	{
		$this->load->library( array('session'));
		$data['user'] = $this->session->userdata('user');	
		if(empty($data['user'])){
			$this->session->set_flashdata('referer', '/account');
			redirect('/no_login');
		}		

		$website_id  = $this->input->post('website_id');
		$domain      = $this->input->post('domain');
		
		
		$this->load->model( array('Website_model') );		
		$website = $this->Website_model->get_website_orders($data['user']['user_id'], $website_id);

		
		if ( empty($website['record']) || empty($website['rt']['orders']) ) {
			echo false; die();
		}
		
		$username = $this->config->item('whois_account');
		$password = $this->config->item('whois_password');
		$contents = file_get_contents("http://www.whoisxmlapi.com/whoisserver/WhoisService?domainName={$domain}&cmd=GET_DN_AVAILABILITY&username={$username}&password={$password}&outputFormat=JSON");
		
		$res=json_decode($contents);

		if( $res )
		{
		  if( empty($res->ErrorMessage) )
		  {
		  	if ( !empty($res->DomainInfo) )
		  	{
			  	$domainInfo = $res->DomainInfo;
			  	if ( !empty($domainInfo->domainAvailability) && $domainInfo->domainAvailability === 'AVAILABLE' ) {
				  	echo true; die();
			  	}
		  	}
		  	echo false;die();
		  } 
		  else 
		  {
		    echo $res->ErrorMessage->msg; die();
		  }
		}
		
		echo 'error';
		
	}
}