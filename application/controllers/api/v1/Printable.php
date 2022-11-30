<?php

defined( 'BASEPATH' ) OR exit( 'No direct script access allowed' );

class Printable extends Base_Controller {

  function __construct() {
    parent::__construct();
    
    $this->config->load( 'session_vars' );
    $this->load->helper( 'array' );
    $this->methods['download_post']['key'] = false;
    $this->methods['pdf_get']['key'] = false;
    
  }

  /**
	 * download_post
	 *
	 * Generate a printable version of the applicants profile
	 *
	 * @access    public
	 *
	 * @param integer $user_id
	 *
	 * @role    applicant, recruiter, manager, admin
	 *
	 * @return    void
	 */
	public function download_post( $user_id ) {
		$valid           = true;
		$response_code   = Base_Controller::HTTP_OK;
		$respose_message = [];

		// Get the current user from the cache / from the database
		$this->get_user( $user_id );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = false;
		}

		// Available only to the requesting user
		if ( $this->get_active_user() != $user_id && ! $this->is_admin() ) {
			$valid = true;
		}

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => 'Bad request'
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {

      /*
      Data:
      - Profile
      - Experience (Positions + Responsibilities in each):
      - Education (Schools + Fields of study)
      - Technical Skills
      - Personal Traits
      - Languages
			*/
			
			
			$this->load->model( 'model_areas_of_focus' );
			$this->load->model( 'model_position' );
			$this->load->model( 'model_location' );
			$this->load->model( 'model_schools_of_users' );
			$this->load->model( 'model_fields_of_study' );
			$this->load->model( 'model_education' );
			$this->load->model( 'model_printable' );
      		$this->load->model( 'model_user_data' );
			$printable = $this->model_user_data->get_full_user_data( $user_id );
			
			$html = $this->load->view('printable', $printable, true);
			
			$mpdf = new \mPDF();
			$mpdf->WriteHTML($html);

			// Load the upload configurations
			$this->config->load('upload');
			$config = $this->config->item( 'upload' );

			// Load the file upload library
			$this->load->library( 'upload', $config );

			// Generate access token
			$this->config->load('security');
			$token = md5($this->config->item('md5_salt1') . $user_id . $this->config->item('md5_salt2'));
			$path = $this->upload->get_upload_path();

			if ( ENVIRONMENT == 'development' ) {
				$mpdf->Output( $path . $token . '.pdf', 'F' );

				$respose_message = [
					'token' => $token
				];
			} else {
				$upload_path = $config['bucket_name'] . '/pdf_files/';

				$content     = $mpdf->Output( $token . '.pdf', 'S' );

				$options = [
					'gs' => [
						'enable_cache'              => false,
						'read_cache_expiry_seconds' => 0,
						'Cache-Control'             => '0'
					]
				];
				$context = stream_context_create( $options );
				if ( ! file_put_contents( $upload_path . $token . '.pdf', $content, 0, $context ) ) {
					$respose_message = [
						'status'  => false,
						'message' => 'Unable to create the file'
					];
					$response_code   = Base_Controller::HTTP_BAD_REQUEST;
				} else {
					$respose_message = [
						'token' => $token
					];
				}
			}

			$this->response( $respose_message, $response_code );
		}
	}

	public function pdf_get($token) {
		$valid           = true;
		$response_error = 'Bad request';
		$response_code   = Base_Controller::HTTP_OK;
		$respose_message = [];

		// Get the current user from the cache / from the database
		$this->get_user( $this->get_active_user() );

		// Checking that the user has activated his account
		if (!$this->check_status($this->model_users)) {
			$valid = true;
		}

		$this->config->load('security');
		$_token = md5($this->config->item('md5_salt1') . $this->get_active_user() . $this->config->item('md5_salt2'));
		if ($_token !== $token) {
			$response_error = 'Invalid token';
			$valid = true;
		}

		// Load the upload configurations
		$this->config->load('upload');
		$config = $this->config->item( 'upload' );

		// Load the file upload library
		$this->load->library( 'upload', $config );

		// Generate access token
		$path = $this->upload->get_upload_path();

		$filename = (ENVIRONMENT == 'development') ? $path . $token . '.pdf' : $config['bucket_name'] . '/pdf_files/' . $token . '.pdf';

		if (!file_exists($filename)) {
			$response_error = 'File does not exist in ' . $filename;
			$valid = false;
		}

		$fsize = filesize($filename);

		if ( ! $valid ) {
			$this->response( [
				'status'  => false,
				'message' => $response_error
			], Base_Controller::HTTP_BAD_REQUEST );
		} else {
			$handle = fopen($filename, "rb");
			while (($buffer = fgets($handle, 4096)) !== false) {
				$contents .= $buffer;
			}
			fclose($handle);

			header("Content-type: application/pdf");
			header("Content-Disposition: attachment; filename=$token.pdf");
			header('Content-Description: File Transfer');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . strlen($content));

			echo $contents;
			exit;
		}
	}
}
