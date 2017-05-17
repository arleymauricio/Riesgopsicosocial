<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'libraries/Zyght_Model.php');

class Questionary_model extends Zyght_Model {
	public function __construct(){
		parent::__construct();

		$this->table = 'Questionary';
		$this->id = 'id';
	}

	public function get_questionaries() {
		$response = [];
		$questions_aux = [];
		$categories_aux = [];
		$questionary_aux = [];

		$this->db->select($this->table .'.*');
		$this->db->from($this->table);
		$this->db->where($this->table .'.active', 1);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$questionaries = $query->result();

			foreach ($questionaries as $questionary) {

				$sql = "
					SELECT DISTINCT qc.*
					FROM Question as q
					INNER JOIN QuestionCategory as qc ON qc.id = q.question_category_id
					WHERE q.questionary_id = ". $questionary->id ." AND q.active = 1 AND qc.active = 1
				";

				$query2 = $this->db->query($sql);
				$categories = $query2->result();

				$categories_aux = [];
				foreach ($categories as $category) {
					$this->db->select('*');
					$this->db->from('Question');
					$this->db->where('questionary_id', $questionary->id);
					$this->db->where('question_category_id', $category->id);
					$this->db->where('active', 1);

					$query3 = $this->db->get();
					$questions = $query3->result();

					$questions_aux = [];
					foreach ($questions as $question) {
						if ($question->type == 1) {
							$this->db->select('*');
							$this->db->from('QuestionOptions');
							$this->db->where('question_id', $question->id);

							$query4 = $this->db->get();
							$options = $query4->result();

							$question->options = $options;
							$questions_aux[] = $question;
						}else{
							$questions_aux[] = $question;
						}
					}

					$category->questions = $questions_aux;	
					$categories_aux[] = $category;
				}

				$questionary->categories = $categories_aux;
				$questionary_aux[] = $questionary;
			}

			return $questionary_aux;
		}

		return FALSE;
	}
	
	public function create($user, $questionary_id, $job_position_id, $answers) {
		$this->db->trans_start();

		$this->db->insert("QuestionaryCompletion", array(
			'random_user_id' => $user->id,
			'questionary_id' => $questionary_id,
			'job_position_id' => $job_position_id,
			'created_at' => date('Y-m-d H:i:s')
		));

		$questionary_completion_id = $this->db->insert_id();

		foreach ($answers as $answer) {
			$this->answer_model->create($questionary_completion_id, $answer->questionOptionId, $answer->value);
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			// generate an error... or use the log_message() function to log your error
			return FALSE;
		}

		return $questionary_completion_id;
	}
	
	public function get_questionary_completions_by_company_id($company_id) {
		$this->db->select('qc.*, jp.company_id, jp.position, q.name');
		$this->db->select('CASE WHEN qr.questionary_completion_id IS NULL THEN 0 ELSE 1 END AS has_recommendation');
		$this->db->from('QuestionaryCompletion AS qc');
		$this->db->join('JobPosition AS jp', 'qc.job_position_id = jp.Id');
		$this->db->join('Questionary AS q', 'qc.questionary_id = q.id');
		$this->db->join('(SELECT DISTINCT(questionary_completion_id) FROM [dbo].[QuestionaryRecommendations]) AS qr', 
				'qr.questionary_completion_id = qc.id', 'left');
		$this->db->where('q.active', 1);
		$this->db->where('jp.company_id', (int) $company_id);
		$query = $this->db->get();
	
		return ($query->num_rows() > 0) ? $query->result() : FALSE;
	}

	public function set_recommendations($questionary_completion_id, $recommendation_ids){
		$this->db->trans_start();
		$this->delete_all_recommendations($questionary_completion_id);
		if(is_array($recommendation_ids)){
			foreach ($recommendation_ids as $r_id){
				$this->db->insert('QuestionaryRecommendations',
						array(
								'questionary_completion_id' => (int) $questionary_completion_id,
								'recommendation_id' => (int) $r_id
						)
				);
			}
		}
		$this->db->trans_complete();
		
		if ($this->db->trans_status() === FALSE) {
			// generate an error... or use the log_message() function to log your error
			return FALSE;
		}
	
		return TRUE;
	}
	
	public function delete_all_recommendations($questionary_completion_id){
		$this->db->where('questionary_completion_id', $questionary_completion_id);
		$this->db->delete('QuestionaryRecommendations');
		return TRUE;
	}
}
