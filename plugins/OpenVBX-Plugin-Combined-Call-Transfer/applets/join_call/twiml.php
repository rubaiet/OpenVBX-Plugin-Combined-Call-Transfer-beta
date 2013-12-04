<?php
	include_once('TwimlJoinCall.php');

	$ci = &get_instance();

	// check if table 'conference_calls' and 'recorded_calls' not exists
	if ((!$ci->db->table_exists('conference_calls')) OR (!$ci->db->table_exists('recorded_calls')))
	{
		// create table 'conference_calls' and 'recorded_calls' if not exists
		$queries = explode(';', file_get_contents(dirname(dirname(dirname(__FILE__))).'/db.sql'));
		foreach($queries as $query)
			if(trim($query))
				$ci->db->query($query);		
	}

	$join_call = new TwimlJoinCall();
	$join_call->process_join();