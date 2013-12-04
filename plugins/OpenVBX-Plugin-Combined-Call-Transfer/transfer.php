<?php
	$users = OpenVBX::getUsers(array('is_active' => 1));
	$ci =& get_instance();

	// check if table 'conference_calls' and 'recorded_calls' not exists
	if ((!$ci->db->table_exists('conference_calls')) OR (!$ci->db->table_exists('recorded_calls')))
	{
		$queries = explode(';', file_get_contents(dirname(__FILE__) . '/db.sql'));
		foreach($queries as $query)
			if(trim($query))
				$ci->db->query($query);		
	}
	
	$account = OpenVBX::getAccount();
	
	// get active conferences
	$active_conferences = $account->conferences->getIterator(0, 1000, array('Status' => 'in-progress'));
	$active_rooms = array();

	// get a list of running conferences
	foreach($active_conferences as $row)
	{
		$active_rooms[] = $row->friendly_name;
	}

	if (count($active_rooms) < 1)
	{
		$active_rooms[] = '';
	}
	
	// close non active conferences on database
	// update data of 'conference_calls'
	$ci->db->where_not_in('conf_name', $active_rooms);
	$ci->db->update('conference_calls', array(
		'type' 		=> 0
	));
	
	// join conferences
	if(!empty($_POST['recipient']) AND !empty($_POST['confName']) AND !empty($_POST['number']))
	{
		// get user name from database by id
		$user = $ci->db->query(sprintf('SELECT * FROM users WHERE id = '.$_POST['recipient']))->row();
		if(count($user))
		{
			$agent = $user->first_name . ' ' . $user->last_name;
		}
		else
		{
			$agent = $_POST['recipient'];
		}
		
		// check if the conference is live
		$ci->db->where('conf_name', $_POST['confName']);
		$ci->db->where('type', 1);
		$ci->db->from('conference_calls');
		$conference_exists =  $ci->db->count_all_results();

		if($conference_exists)
		{
			// update data of 'conference_calls'
			$ci->db->update('conference_calls', array(
														'agent' => $agent
													), array('conf_name' => $_POST['confName']));
			// add new agent to database
			$path = 'requests/join_agent/'.$_POST['confName'];
			$join_agent_url = stripslashes(site_url($path));
			$account->calls->create($_POST['number'], 'Client:'.$_POST['recipient'], $join_agent_url);
		} // end if conference exists
	}
	
	// get live conferences from database
	$conferences = $ci->db->query(sprintf('SELECT * FROM conference_calls WHERE type = 1 ORDER BY id DESC'))->result();
?>
<style>
	.vbx-outbound form {
		padding: 20px 5%;
	}
</style>
<div class="vbx-content-main">
	<div class="vbx-content-menu vbx-content-menu-top">
		<h2 class="vbx-content-heading">Start Transfer</h2>
	</div>
    <div class="vbx-table-section vbx-outbound">
		<form method="post" action="">
			<fieldset class="vbx-input-container">
				<?php if(count($callerid_numbers)): ?>
					<?php if(count($conferences)): ?>
						<p>
							<label class="field-label">Agents<br/>
								<select name="recipient" class="medium">
									<?php 
										foreach($users as $user):
											if(count($user->devices) > 0):
												$agent_name = $user->values['first_name'] . ' ' . $user->values['last_name'];
									?>
													<option value="<?php echo $user->values['id']; ?>"><?php echo $agent_name; ?></option>
									<?php 
											endif;
										endforeach;
									?>
								</select>
							</label>
						</p>
						<p>
							<label class="field-label">Conference Rooms<br/>
								<?php if(count($conferences)): ?>
									<select name="confName" class="medium">
										<?php foreach($conferences as $conference): ?>
											<option value="<?php echo $conference->conf_name; ?>"><?php echo $conference->agent .' :: ' .$conference->conf_name; ?></option>
										<?php endforeach; ?>
									</select>
								<?php endif; ?>
							</label>
						</p>
						<p>
							<label class="field-label">Caller ID<br/>
								<select name="number" class="medium">
									<?php foreach($callerid_numbers as $number): ?>
										<option value="<?php echo $number->phone; ?>"><?php echo $number->name; ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</p>
						<p><button type="submit" class="submit-button"><span>Call</span></button></p>
					<?php else: ?>
						<p>No active conference found!</p>
					<?php endif; ?>
				<?php else: ?>
					<p>You do not have any phone numbers!</p>
			<?php endif; ?>
			</fieldset>
		</form>
    </div>
</div>