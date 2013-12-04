<?php

class TwimlJoinCallException extends Exception {};

class TwimlJoinCall {

	public $join_call;			
	public $service_data = array();
	public $service_call_sid;
	public $service_call_data = array();
	public $numbers	= array();
	public $users = array();
	public $call_received;
	public $loop_total;
	public $dialed_index;
	public $ring_tone_played;
	public $error;

	public function __construct($settings = array())
	{
		$this->ci = &get_instance();
		$this->response = new TwimlResponse;
		
		// gather join form info detail
		$this->dial_whom_selector 		= AppletInstance::getValue('dial-whom-selector');
		$this->dial_whom_user_or_group 	= AppletInstance::getUserGroupPickerValue('dial-whom-user-or-group');
		$this->dial_whom_number 		= AppletInstance::getValue('dial-whom-number');
		$this->no_answer_redirect 		= AppletInstance::getDropZoneUrl('no-answer-redirect');
		$this->recording_enable 		= AppletInstance::getValue('recording-enable', 'yes');
		$this->prompt 					= AppletInstance::getAudioSpeechPickerValue('prompt');
	
		// preset conference wait music
		$this->default_wait_url = 'http://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient';
		$this->wait_url = AppletInstance::getValue('wait-url', $this->default_wait_url);

		// prepare conf_name
		$this->conf_name = $this->ci->session->userdata('CONFNAME');
		if($this->conf_name == FALSE)
		{
			if(isset($_REQUEST['From']))
				$this->conf_name = $_REQUEST['From'].'-'.rand(pow(10, 6-1), pow(10, 6)-1);
			else
				$this->conf_name = rand(pow(10, 6-1), pow(10, 6)-1);
				
			$this->ci->session->set_userdata('CONFNAME', $this->conf_name);
		}

		$this->conf_options = array(
								'muted' => 'false',
								'startConferenceOnEnter' => 'false',
								'endConferenceOnExit' => 'true',
								'waitUrl' => $this->wait_url
							);
		// create path for invite agent response
		$this->path = 'requests/join_agent/'.$this->conf_name;
		$this->join_agent_url = stripslashes(site_url($this->path));
		
		$this->plugin = OpenVBX::$currentPlugin;
		$this->info = $this->plugin->getInfo();
		$this->dial_tone_path = base_url() . implode('/', array('plugins', $this->info['dir_name'], 'sounds/US_ringback_tone.mp3'));
		log_message('error', "join_agent_url: ". $this->join_agent_url ."\n\n");

		require_once(APPPATH . 'libraries/Services/Twilio.php');
		$this->service = new Services_Twilio($this->ci->twilio_sid, $this->ci->twilio_token);
	}

	public function process_join() 
	{
		// log_message('error', "Loop222:\n\n");

		// prepare agent number list
		$this->get_online_users();

		// if found agent numers
		if(count($this->numbers) > 0)
		{
			// get dialed index from session
			$this->dialed_index = $this->get_dialed_index();
			
			// get ring_tone state from session
			$this->ring_tone_played = $this->get_ring_tone_played();
			
			$this->service_call_sid = $this->get_service_call_sid();

			$this->call_received = FALSE;
			$this->loop_total = count($this->numbers);

			log_message('error', "Loop111:\n". $this->dialed_index . "-" . $this->ring_tone_played . "-" . $this->service_call_sid . "-" . $this->loop_total ."\n\n");

			while($this->dialed_index < $this->loop_total)
			{
				// increase the ringtone state and set in session
				$this->update_ring_tone_played();

				// if no ringtone played, start dial agents with ring tone
				if($this->ring_tone_played == 1)
				{
					log_message('error', "Loop:\n". $this->dialed_index . "-" . $this->loop_total ."\n\n");
					// start calling agent
					$this->service_data = $this->service->account->calls->create($_REQUEST['To'], $this->numbers[$this->dialed_index], $this->join_agent_url);
					
					$this->service_call_sid = $this->service_data->sid;
					log_message('error', "Passing service data:\n". $this->service_call_sid ."\n\n");

					// play greetings for wait 
					$this->say_greetings();
				}
				elseif($this->ring_tone_played == 2)
				{
					// play ring tone
					$this->play_dial_tone(2);
				}
				elseif($this->ring_tone_played == 3)
				{
					// check call status
					$this->service_call_data = $this->service->account->calls->get($this->service_call_sid);
					// log_message('error', "Passing call service data:\n". $this->service_call_data->status . "-" . $this->service_call_data->sid ."\n\n");

					// if the receiver still not pickup the call, terminate it
					if($this->service_call_data->status != 'in-progress')
					{
						// play ring tone
						$this->play_dial_tone(3);
					}
					else
					{
						$this->join_received_call();
						exit;
					}
				}
				elseif($this->ring_tone_played == 4)
				{
					// increase the ringtone state and set in session
					$this->update_ring_tone_played();

					// check call status
					$this->service_call_data = $this->service->account->calls->get($this->service_call_sid);
					// log_message('error', "Passing call service data:\n". $service_call_data->status . "-" . $service_call_data->sid ."\n\n");

					// if the receiver still not pickup the call, terminate it
					if($this->service_call_data->status != 'in-progress')
					{
						$this->dialed_index++;

						// set dialed index in session
						$this->ci->session->set_userdata('JOIN_CALL_DIALED', $this->dialed_index);

						$this->service_call_data = $this->service->account->calls->get($this->service_call_sid);
						$this->service_call_data->update(array("Status" => "completed"));
					}
					else
					{
						$this->join_received_call();
						exit;
					} // end else call in-progress
				} // end elseif it is the last stare for ring tone
			} // end while the process call agent numbers
		} // end if agent numbers found
		
		$this->process_no_agent();
	}
	
	public function process_no_agent()
	{
		// clear session
		$this->clear_session();

		// else if redirect to another applet
		if(empty($this->no_answer_redirect))
		{
			$this->ci->session->unset_userdata('JOIN_CALL_DIALED');
			$this->response->say('Sorry, all of our agents are busy now. Please try again later! Thank you.', array(
					'voice' => $this->ci->vbx_settings->get('voice', $this->ci->tenant->id),
					'language' => $this->ci->vbx_settings->get('voice_language', $this->ci->tenant->id)
				));
			$this->response->addHangup();
			$this->response->respond();
		}
		else
		{
			$this->ci->session->unset_userdata('JOIN_CALL_DIALED');
			$this->response->redirect($this->no_answer_redirect);
			$this->response->respond();
		}
	}
	
	public function clear_session()
	{
		$this->ci->session->unset_userdata('JOIN_CALL_DIALED');
		$this->ci->session->unset_userdata('SERVICE_CALL_SID');
		$this->ci->session->unset_userdata('RING_TONE_PLAYED');
		$this->ci->session->unset_userdata('CONFNAME');
	}
	
	public function say_greetings()
	{
		// save 'dialed index' and 'service call sid' into session
		$this->ci->session->set_userdata('JOIN_CALL_DIALED', $this->dialed_index);
		$this->ci->session->set_userdata('SERVICE_CALL_SID', $this->service_call_sid);

		// start greetings
		AudioSpeechPickerWidget::setVerbForValue($this->prompt, $this->response);
		
		$this->response->redirect();
		$this->response->respond();
		exit;
	}

	public function play_dial_tone($loop=3)
	{
		$this->ci->session->set_userdata('JOIN_CALL_DIALED', $this->dialed_index);
		$this->ci->session->set_userdata('SERVICE_CALL_SID', $this->service_call_sid);

		$this->response->play($this->dial_tone_path, array('loop'=>$loop));
		$this->response->redirect();
		$this->response->respond();
		exit;
	}

	public function update_ring_tone_played()
	{
		// get the ringtone state
		$this->ring_tone_played = $this->get_ring_tone_played();

		// change ringtone state
		if($this->ring_tone_played >= 4 )
		{
			$this->ring_tone_played = 0;
		}
		else
		{
			$this->ring_tone_played = $this->ring_tone_played + 1;
		}
		
		// set new state at session
		$this->ci->session->set_userdata('RING_TONE_PLAYED', $this->ring_tone_played);
	}

	public function join_received_call()
	{
		// set value to quit loop, unset session
		$this->loop_total = 0;
		$this->call_received = TRUE;
		
		// clear session
		$this->clear_session();
		// $this->user_index = $this->dialed_index - 1;
		$this->agent = $this->users[$this->dialed_index];
		
		// insert convesation information
		if((!empty($_REQUEST['CallSid'])) AND (!empty($_REQUEST['From'])) AND (!empty($_REQUEST['To']))) 
		{
			// add conference detail on table 'conference_calls'
			$this->ci->db->insert('conference_calls', array(
				'call_sid' 	=> $_REQUEST['CallSid'],
				'conf_name' => $this->conf_name,
				'from' 		=> $_REQUEST['From'],
				'to' 		=> $_REQUEST['To'],
				'agent' 	=> $this->agent,
				'type' 		=> '1',
				'created' 	=> date('Y-m-d')
			));
		} // insert conference detail

		if($this->recording_enable != 'no')
		{
			// start caller dial
			$dial = $this->response->dial(null, array(
					'timeout' 				=> $this->ci->vbx_settings->get('dial_timeout', $this->ci->tenant->id),
					'timeLimit' 			=> 14500,
					'record' 				=> 'true',
					'action'				=> site_url('requests/save_recording_url')
			));
		}
		else
		{
			// start caller dial
			$dial = $this->response->dial(null, array(
					'timeout' 				=> $this->ci->vbx_settings->get('dial_timeout', $this->ci->tenant->id),
					'timeLimit' 			=> 14500
			));
		}
		
		$dial->conference($this->conf_name, $this->conf_options);

		// twiml to $response
		$this->response->respond();
	}
	
	/**
	 * get users list who are asigned to receive this call and now on line
	 *
	 * @return array
	 */
	public function get_online_users() 
	{
		// prepare agent number list
		if ($this->dial_whom_selector === 'user-or-group')
		{
			$this->dial_whom_instance = null;
			if(is_object($this->dial_whom_user_or_group))
			{
				$this->dial_whom_instance = get_class($this->dial_whom_user_or_group);
			}

			switch($this->dial_whom_instance)
			{
				case 'VBX_User':
					foreach($this->dial_whom_user_or_group->devices as $device)
					{
						if($device->is_active == 1)
						{
							$this->numbers[] 	= $device->value;
							$this->users[]		= $this->dial_whom_user_or_group->values['first_name'] . ' ' . $this->dial_whom_user_or_group->values['last_name'];
						}
					}
					$user_response = print_r($this->numbers, true);
					// log_message('error', "VBX_User: ". $user_response ."\n");
					break;
				case 'VBX_Group':
					foreach($this->dial_whom_user_or_group->users as $user)
					{
						$user = VBX_User::get($user->user_id);
						foreach($user->devices as $device)
						{
							if($device->is_active == 1)
							{
								$this->numbers[]	= $device->value;
								$this->users[]	= $user->values['first_name'] . ' ' . $user->values['last_name'];
							}
						}
					}
					$user_response = print_r($this->numbers, true);
					$user_response2 = print_r($this->users, true);
					log_message('error', "VBX_Group: ". $user_response ."\n");
					log_message('error', "VBX_Group: ". $user_response2 ."\n");
					break;
				default:
					$this->error = 'No agent found! Goodbye.';
					break;
			}
		}
		else if ($this->dial_whom_selector === 'number')
		{
			$this->numbers[]	= $dial_whom_number;
			$this->users[]	= $dial_whom_number;
		}
		else
		{
			$this->error = 'No receiver found! Goodbye.';
		}
	} // end public function get_online_users

	/**
	 * get dialed index from session
	 *
	 * @return int
	 */
	public function get_dialed_index() 
	{
		$this->dialed_index = $this->ci->session->userdata('JOIN_CALL_DIALED');
		if($this->dialed_index == FALSE)
		{
			$this->dialed_index = 0;
		}
		
		return $this->dialed_index;
	}

	/**
	 * get ring tone status from session
	 *
	 * @return int
	 */
	public function get_ring_tone_played() 
	{
		$this->ring_tone_played = $this->ci->session->userdata('RING_TONE_PLAYED');
		if($this->ring_tone_played == FALSE)
		{
			$this->ring_tone_played = 0;
		}
		
		return $this->ring_tone_played;
	}

	/**
	 * get service call sid from session
	 *
	 * @return int
	 */
	public function get_service_call_sid() 
	{
		$this->service_call_sid = $this->ci->session->userdata('SERVICE_CALL_SID');
		return $this->service_call_sid;
	}
}

