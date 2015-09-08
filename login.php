<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index()
	{
		$this->load->view('login');
	}
	
	
	public function facebook()
	{
		$this->load->helper( array('url') );
		$this->load->library( array('session', 'facebook') );

		if ( $this->facebook->session )
		{
			$user = $this->facebook->get_user();

			
			if ( $user )
			{
				$referer = $this->session->flashdata('referer');

				if ( $this->fbLogin($user) && empty($referer) )
					$referer = $this->input->server('HTTP_REFERER');

				if ( !empty($referer) )
					redirect($referer);
				else
					redirect('/');
			}
		}

		
		$login_url = $this->facebook->get_login_url();
		redirect($login_url);
	
	}

	private function fbLogin($data)
	{
		if ( $this->session->userdata('user') )
			return false;
	
		$this->load->database();
		$this->db->where('email', $data['email']);
		$this->db->where('source', 'facebook');
		$user = $this->db->get('user')->row_array();

		if ( empty($user) )
		{
			$user['email']  = $data['email'];
			$user['name']   = $data['name'];
			$user['source'] = 'facebook';
			$user['id']     = $data['id'];
			$this->db->insert('user', $user);
			$user['user_id'] = $this->db->insert_id();
		}

		$this->session->set_userdata('user', $user);
		return true;
	}
}
