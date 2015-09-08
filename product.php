<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends CI_Controller {
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function index()
	{
		$this->load->helper(array('form', 'url'));

		$data['record'] = $this->db->get('product')->result_array();
		$this->load->view('product', $data);
	}
	
	public function import_solution()
	{
		$this->db->select('product_id, themeforest_id');
		$product_id = array();
		foreach($this->db->get('product')->result_array() as $product)
		{
			$product_id[$product['themeforest_id']] = $product['product_id'];
		}
		
		if ( !empty($_FILES['import']) )
		{
			$this->load->library('excel');
	
			$this->excel = PHPExcel_IOFactory::load($_FILES['import']['tmp_name']);
			$count = $this->excel->getSheetCount();
			$solution_product_rt = array();

			for( $i = 0 ; $i < $count ; $i++ ) 
			{
				$sheet = $this->excel->getSheet($i);
				$loop = $sheet->getHighestRow();
				for( $j = 1 ; $j <= $loop ; $j++ ) 
				{
					$themeforest_id = $sheet->getCell("D{$j}")->getValue();
					if ( is_numeric($themeforest_id) && !empty($product_id[$themeforest_id]) ) 
					{
						for( $k = 1 ; $k <= 4 ; $k++ )
						{
							if ( $k > 1 || (int)$sheet->getCell("A{$j}")->getValue() == 0 )
							{
								$this->db->set('solution_id', $k);
								$this->db->set('product_id', $product_id[$themeforest_id]);
								$this->db->set('create_ts', 'NOW()', FALSE);
								$this->db->insert('solution_product_rt');
							}
						}
					}
				}
			}
		}
	}
	
	public function import($act = '')
	{

/* 		echo '<pre>'; print_r($_FILES['import']);die(); */
		$this->load->library('excel');

		$this->excel = PHPExcel_IOFactory::load($_FILES['import']['tmp_name']);
		$count = $this->excel->getSheetCount();
		if ($act == 'truncate' || $act == 'clear')
		{
			$this->db->query("TRUNCATE TABLE product");
			$this->db->query("TRUNCATE TABLE product_keyword_rt");
			$this->db->query("TRUNCATE TABLE product_category_rt");
			$this->db->query("TRUNCATE TABLE solution_product_rt");
			$this->db->query("TRUNCATE TABLE keyword");
		}

		//Load Keyword from database
		$keyword_list = array();
		$firstNewKeyword = 0;
		$arrKeyword = $this->db->from('keyword')->order_by('keyword_id', 'asc')->get()->result_array();
		foreach($arrKeyword as $kk => $kv)
			$keyword_list[$kv['name']] = $kv['keyword_id'];

		//Load Keyword from database		
		$array_themeforest_id = array();
		$arrThemeforestId = $this->db->select('themeforest_id')->get('product')->result_array();
		foreach($arrThemeforestId as $tk => $tv)
			$array_themeforest_id[] = $tv['themeforest_id'];

		$product_keyword_rt = array();
		
		for( $i = 0 ; $i < $count ; $i++ ) 
		{
			$sheet = $this->excel->getSheet($i);
			$loop = $sheet->getHighestRow();
			for( $j = 1 ; $j <= $loop ; $j++ ) 
			{
				$themeforest_id = $sheet->getCell("D{$j}")->getValue();
				if ( is_numeric($themeforest_id) ) 
				{
/*
					if ( in_array($themeforest_id, $array_themeforest_id) ) {
						continue;
					}
*/
					unset($data);
					$data['solution']       = $sheet->getCell("A{$j}")->getValue();
					$data['purchase_url']   = $sheet->getCell("B{$j}")->getValue();
					$data['demo_url']       = $sheet->getCell("C{$j}")->getValue();
					$data['themeforest_id'] = $sheet->getCell("D{$j}")->getValue();
					$data['usd']            = $sheet->getCell("E{$j}")->getValue();
					$data['is_fullscreen']  = $sheet->getCell("F{$j}")->getValue();
					$data['is_responsive']  = $sheet->getCell("G{$j}")->getValue();
					$data['is_singlepage']  = $sheet->getCell("H{$j}")->getValue();
					$data['author']         = $sheet->getCell("I{$j}")->getValue();
					$data['browser']        = $sheet->getCell("J{$j}")->getValue();
					$data['thumbnail']      = $sheet->getCell("K{$j}")->getValue();
					$this->db->set('create_ts', 'NOW()', FALSE);
					$this->db->insert('product', $data);

					$product_id = $this->db->insert_id();
					$keyword = explode(',', $sheet->getCell("L{$j}")->getValue());
					$product_keyword = array();
					foreach($keyword as $kv) 
					{
						$kv = trim($kv);
						if ( isset($keyword_list[$kv]) ) 
							$keyword_id = $keyword_list[$kv];
						else 
						{
							$keyword_id = count($keyword_list) + 1;
							$keyword_list[$kv] = $keyword_id;

							if ( !$firstNewKeyword )
								$firstNewKeyword = $keyword_id;
						}

						$product_keyword[] = $keyword_id;
					}

					foreach(array_unique($product_keyword) as $keyword_id) 
					{
						if ( empty($keyword_id) )
							continue;
						unset($data);
						$product_keyword_rt[] = array(
							'product_id' => $product_id,
							'keyword_id' => $keyword_id,
							'create_ts' => date("Y-m-d H:i:s")
						);
					}

					$category = explode(',', $sheet->getCell("M{$j}")->getValue());
					$product_category_rt = array();

					foreach($category as $cv)
					{
						if ( empty($cv) )
							continue;
						$product_category_rt[] = (int)$cv;
					}

					foreach(array_unique($product_category_rt) as $category_id) 
					{					
						unset($data);
						$data['product_id'] = $product_id;
						$data['category_id'] = $category_id;
						$this->db->set('create_ts', 'NOW()', FALSE);
						$this->db->insert('product_category_rt', $data);
					}
										
					for( $k = 1 ; $k <= 4 ; $k++ )
					{
						if ( $k > 1 || (int)$sheet->getCell("A{$j}")->getValue() == 0 )
						{
							$this->db->set('solution_id', $k);
							$this->db->set('product_id', $product_id);
							$this->db->set('create_ts', 'NOW()', FALSE);
							$this->db->insert('solution_product_rt');
						}
					}
				}
				elseif ( empty($themeforest_id) )
				{
					break;
				}
			}
		}
		
		foreach($keyword_list as $name => $id)
		{
			if ( $firstNewKeyword && $id >= $firstNewKeyword )
			{
				unset($data);
				$data['keyword_id'] = $id;
				$data['name'] = $name;
				$this->db->set('create_ts', 'NOW()', FALSE);
				$this->db->insert('keyword', $data);
	
			}
		}

		if ( !empty($product_keyword_rt) )
		{
			$this->db->insert_batch('product_keyword_rt', $product_keyword_rt);	
		}
			

		redirect('/product', 'refresh');
	}
}