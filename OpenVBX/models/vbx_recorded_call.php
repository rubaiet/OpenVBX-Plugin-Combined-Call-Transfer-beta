<?php

class VBX_Recorded_callException extends Exception {}

/*
 * Message Class
 */
class VBX_Recorded_call extends Model {	

	protected static $__CLASS__ = __CLASS__;
	public $auto_populate_has_one = TRUE;
	public $table = 'recorded_calls';

	public function __construct($object = null)
	{
		parent::__construct($object);
	}

	function get_recorded_call($CallSid)
	{
		$ci =&get_instance();

		$ci->db->from($this->table);
		$ci->db->where('call_sid', $CallSid);
		$call_exists = $ci->db->count_all_results();

		if($call_exists)
		{
			return true;
		}
		return false;
	} // end function get_recorded_call

	function save_records($input)
	{
		$ci =& get_instance();
		// check if response exists
		if((!empty($input['CallSid'])) AND (!empty($input['CallStatus'])) AND (!empty($input['RecordingUrl'])))
		{
			// update recording url if CallSid match
			if($this->get_recorded_call($input['CallSid']) !== false)
			{
				if(isset($input['TranscriptionText']))
					$ci->db->set('transcript', $input['TranscriptionText']);

				$ci->db->set('status', $input['CallStatus']);
				$ci->db->set('updated', date('Y-m-d H:i:s'));
				$ci->db->where('call_sid', $input['CallSid']);
				$ci->db->update($this->table);
			}
			else 
			{
				// insert data to 'recorded_calls'
				$ci->db->set('call_sid', $input['CallSid']);
				$ci->db->set('from', $input['From']);
				$ci->db->set('to', $input['To']);
				$ci->db->set('recording_url', $input['RecordingUrl']);

				if(isset($input['RecordingDuration']))
					$ci->db->set('duration', $input['RecordingDuration']);
				if(isset($input['TranscriptionText']))
					$ci->db->set('transcript', $input['TranscriptionText']);

				$ci->db->set('status', $input['CallStatus']);
				$ci->db->set('status', $input['CallStatus']);
				$ci->db->set('created', date('Y-m-d H:i:s'));
				$ci->db->set('updated', date('Y-m-d H:i:s'));
				$ci->db->insert($this->table);
			} // end else CallSid do not match
		} // if parameters not found
	} // end function save_records
	
	static function get($search_options = array())
	{
		$obj = new self();
		$ci = &get_instance();
		
		$ci->db->select();
		$ci->db->from($obj->table);

		foreach($search_options as $option => $value)
		{
			if (preg_match('/([^_]+)__like_?(before|after|both)$/', $option, $side_match))
			{
				$side = empty($side_match[2])? 'both' : $side_match[2];
				$option = $side_match[1];
				$ci->db->like($option, $value, $side);
			}
			elseif (preg_match('/([^_]+)__(not_in|in)$/', $option, $matches))
			{
				list($comp, $key, $type) = $matches;
				$method = ($type == 'in' ? 'where_in' : 'where_not_in');
				$ci->db->$method($key, $value);
			}
			else
			{
				$ci->db->where($option, $value);
			}
		}

		$query = $ci->db->get();
		
		if (!empty($query))
		{
			return $query->row_array();
		}

		return false;
	}
	
	static function search($search_options = array(), $limit = -1, $offset = 0)
	{
		$obj = new self();
		$ci = &get_instance();
		
		$ci->db->select();
		$ci->db->from($obj->table);

		foreach($search_options as $option => $value)
		{
			if (preg_match('/([^_]+)__like_?(before|after|both)$/', $option, $side_match))
			{
				$side = empty($side_match[2])? 'both' : $side_match[2];
				$option = $side_match[1];
				$ci->db->like($option, $value, $side);
			}
			elseif (preg_match('/([^_]+)__(not_in|in)$/', $option, $matches))
			{
				list($comp, $key, $type) = $matches;
				$method = ($type == 'in' ? 'where_in' : 'where_not_in');
				$ci->db->$method($key, $value);
			}
			else
			{
				$ci->db->where($option, $value);
			}
		}

		$ci->db->limit($limit, $offset);
		$ci->db->order_by('id', 'DESC');
		$query = $ci->db->get();
		
		if (!empty($query))
		{
			return $query->result_array();
		}

		return false;
	}

	static function count($count_options = array()) 
	{
		$obj = new self();
		$ci = &get_instance();
		
		foreach($count_options as $option => $value)
		{
			if (preg_match('/([^_]+)__like_?(before|after|both)$/', $option, $side_match))
			{
				$side = empty($side_match[2])? 'both' : $side_match[2];
				$option = $side_match[1];
				$ci->db->like($option, $value, $side);
			}
			elseif (preg_match('/([^_]+)__(not_in|in)$/', $option, $matches))
			{
				list($comp, $key, $type) = $matches;
				$method = ($type == 'in' ? 'where_in' : 'where_not_in');
				$ci->db->$method($key, $value);
			}
			else
			{
				$ci->db->where($option, $value);
			}
		}

		$count = $ci->db->count_all_results($obj->table);
		if ($count > 0) {
			return $count;
		}
		return false;
	}
} // end of class VBX_Recorded_call
// end 