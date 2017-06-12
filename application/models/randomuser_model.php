<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'libraries/Zyght_Model.php');

class randomuser_model extends Zyght_Model {
	public function __construct(){
		parent::__construct();

		$this->table = 'RandomUser';
		$this->id = 'id';
	}

	public function generate($company_id, $amount) {
		$date =	date("Y-m-d");

		if (!isset($company_id) || empty($company_id)) {
			return FALSE;
		}

		if (!isset($amount) || empty($amount)) {
			return FALSE;
		}

		if ($amount <= 0) {
			return FALSE;
		}

		for ($pos = 0; $pos < $amount; $pos++) {
			$pass = $this->_random_password();
			while($this->_is_user_exists($company_id, $pass)){
				$pass = $this->_random_password();
			}
			$this->db->insert($this->table, array(
				'company_id' => $company_id,
				'password' => $pass,
				'date' => $date
			));
		}

		return TRUE;
	}
	
	private function _is_user_exists($company_id, $password){
		$this->db->select('1');
		$this->db->from($this->table);
		$this->db->where('company_id', $company_id);
		$this->db->where('password', $password);
		$query = $this->db->get();
		
		return ($query->num_rows() > 0) ? TRUE : FALSE;
	}

	private function _random_password($chars = 5) {
		$letters = 'abcefghijklmnopqrstuvwxyz1234567890';
		return substr(str_shuffle($letters), 0, $chars);
	}

	public function get_by_company_id($company_id) {
		$this->db->select('*');
		$this->db->from($this->table);
		$this->db->where('company_id', $company_id);
		$query = $this->db->get();

		return ($query->num_rows() > 0) ? $query->result() : array();
	}

	// USER -------------------------------------------------------------------------------

	public function login($password, $company_code) {
		$this->db->select($this->table . '.*');
		$this->db->from($this->table);
		$this->db->join("Company as c", "c.id = " . $this->table . ".company_id");
		$this->db->where($this->table . '.password', $password);
		$this->db->where('c.code', $company_code);

		$query = $this->db->get();

		if ($query->num_rows() == 0) {
			return FALSE;
		}

		$user = $query->row();
		$user->access_token = $this->_generate_token($user->id);

		return $user;
	}

	private function _generate_token($id) {
		$timestamp = date("mdY_His");
		$token = md5($timestamp);

		$this->db->where($this->id, $id);
		$this->db->update($this->table, array(
			'access_token' => $token
		));

		return $token;
	}

	public function get_loggedin_user($access_token) {
		$this->db->select($this->table .'.*');
		$this->db->from($this->table);
		$this->db->where($this->table .'.access_token', $access_token);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$list = $query->result();
			return $list[0];
		}

		return FALSE;
	}
}

