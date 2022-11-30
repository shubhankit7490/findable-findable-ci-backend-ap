<?php
defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Model_dictionary extends Base_Model {
	public $model = false;
	public $limit = 50;

	/**
	 * get_dictionary
	 *
	 * Get a dictionary (with an optional filtering)
	 *
	 * @access    public
	 *
	 * @param $filter   string
	 *
	 * @param $value    string
	 *
	 * @return    array
	 */
	public function get_dictionary( $filter = "", $value = "" ) {
		$fields = $this->model->get_dictionary_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->model->get_table() );

		if ( in_array( $filter, $this->allowed_dictionary_filters ) ) {
			// Query modifier filters
			$this->db->where( $filter, $value );
		} else if ( in_array( $filter, $this->searchable_dictionary_filters ) ) {
			// Query search filters
			if ( $value !== '*' ) {
				// $this->db->where( "MATCH(" . $this->model->get_table() . "." . $this->model->get_column_prefix() . $filter . ") AGAINST ('" . $this->db->escape_str( $value ) . "*' IN BOOLEAN MODE) > 0", null, false );
				$this->db->where( $this->model->get_table() . "." . $this->model->get_column_prefix() . $filter . " LIKE '" . $this->db->escape_str( $value ) . "%'" );
			}
		}

		// Only admin approved items
		if ( $this->model->get_table() !== 'traits' && $this->model->get_table() !== 'languages' && $this->model->get_table() !== 'business' ) {
			$this->db->where( $this->model->get_table() . '.' . $this->model->get_column_prefix() . 'admin_approved', 1 );
		}
		/*if($this->model->get_table() == 'languages'){
			$this->db->group_by('name'); 
		}*/
		$this->db->limit( $this->limit );

		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * get_dictionary
	 *
	 * Get a dictionary (with an optional filtering)
	 *
	 * @access    public
	 *
	 * @return    array
	 */
	public function get_dictionary_items() {
		$fields = $this->model->get_dictionary_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->model->get_table() );

		$this->db->limit( 100 );

		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * get_segmented_items
	 *
	 * Get dictionary items (approved / unapproved)
	 *
	 * @access    public
	 *
	 * @param $approved integer
	 *
	 * @return    array
	 */
	public function get_segmented_items( $approved = 0,$offset=NULL ) {
		$fields = $this->model->get_dictionary_model();

		$select = implode( ',', $fields );

		$this->db->select( $select );
		$this->db->from( $this->model->get_table() );
		$this->db->where( $this->model->get_column_prefix() . 'admin_approved', $approved );
		$this->db->where( $this->model->get_table() . '.deleted', 0 );
		if($offset!=NULL){
			$this->db->limit(50, $offset);
		}
		$query = $this->db->get();

		return $query->result();
	}

	/**
	 * parse_dictionary_name
	 *
	 * Get the internal dictionary name from the public name
	 *
	 * @access    public
	 *
	 * @param $dictionary   string
	 *
	 * @return    string
	 */
	public function parse_dictionary_name( $dictionary ) {
		switch ( $dictionary ) {
			case 'tech':
				return 'technical_abilities';
				break;
			case 'schools':
				return 'schools';
				break;
			case 'studyfields':
				return 'fields_of_study';
				break;
			case 'focusareas':
				return 'areas_of_focus';
				break;
			case 'company':
				return 'companies';
				break;
			case 'industry':
				return 'industries';
				break;
			case 'jobtitle':
				return 'job_title';
				break;
			case 'seniority':
				return 'seniorities';
				break;
			default:
				return false;
		}
	}
}