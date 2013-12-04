<?php

class Requests extends Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('TwimlResponse');
	}
	
	// save record url into database
	public function save_recording_url() 
	{
		$this->load->database();
		$this->config->load('openvbx');
		$this->load->model('vbx_recorded_call');

		$this->vbx_recorded_call->save_records($_REQUEST);
	} // end public function save_recording_url
	
	public function join_agent($confName=false)
	{
		// todo: validate rest request
		$response = new TwimlResponse;
		if($confName != false)
		{
			$confOptions = array(
					'muted' => 'false',
					'startConferenceOnEnter' => 'true',
					'endConferenceOnExit' => 'false'
			);

			$options['timeLimit'] = 14400;
			$dial = $response->dial(NULL, $options);
			$dial->conference($confName, $confOptions);
			$response->respond();
		}
	} // end public function join_agent
} // end of controller class Requests
// end of file OpenVBX/controllers/requests.php