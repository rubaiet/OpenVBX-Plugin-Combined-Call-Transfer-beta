<?php
	$ci =& get_instance();
	$version = AppletInstance::getValue('version', null);

	if (AppletInstance::getValue('dial-whom-selector', 'user-or-group') === 'user-or-group')
	{
		$showVoicemailAction = true;
	}
	else
	{
		$showVoicemailAction = false;
	}
	
	$userOrGroup = AppletInstance::getUserGroupPickerValue('dial-whom-user-or-group');
	if ($userOrGroup instanceof VBX_Group)
	{
		$showGroupVoicemailPrompt = true;
	}
	else
	{
		$showGroupVoicemailPrompt = false;
	}

	$dial_whom_selector = AppletInstance::getValue('dial-whom-selector', 'user-or-group');
	$recording_enable = AppletInstance::getValue('recording-enable', 'yes');

	$defaultWaitUrl = 'http://twimlets.com/holdmusic?Bucket=com.twilio.music.classical';
	$waitUrl = AppletInstance::getValue('wait-url', $defaultWaitUrl);
	$musicOptions = array(
			  array("url" => "http://twimlets.com/holdmusic?Bucket=com.twilio.music.classical", "name" => "Classical"),
			  array("url" => "http://twimlets.com/holdmusic?Bucket=com.twilio.music.ambient", "name" => "Ambient"),
			  array("url" => "http://twimlets.com/holdmusic?Bucket=com.twilio.music.electronica", "name" => "Electronica"),
			  array("url" => "http://twimlets.com/holdmusic?Bucket=com.twilio.music.guitars", "name" => "Guitars"),
			  array("url" => "http://twimlets.com/holdmusic?Bucket=com.twilio.music.rock", "name" => "Rock"),
			  array("url" => "http://twimlets.com/holdmusic?Bucket=com.twilio.music.soft-rock", "name" => "Soft Rock"),
			);
?>
<div class="vbx-applet test-call-applet">
	<h2>Join Whom</h2>
	<div class="radio-table">
		<table>
			<tr class="radio-table-row first <?php echo ($dial_whom_selector === 'user-or-group') ? 'on' : 'off' ?>">
				<td class="radio-cell">
					<input type="radio" class='dial-whom-selector-radio' name="dial-whom-selector" value="user-or-group" <?php echo ($dial_whom_selector === 'user-or-group') ? 'checked="checked"' : '' ?> />
				</td>
				<td class="content-cell">
					<h4>Dial a user or group</h4>
					<?php echo AppletUI::UserGroupPicker('dial-whom-user-or-group'); ?>
				</td>
			</tr>
			<tr class="radio-table-row last <?php echo ($dial_whom_selector === 'number') ? 'on' : 'off' ?>">
				<td class="radio-cell">
					<input type="radio" class='dial-whom-selector-radio' name="dial-whom-selector" value="number" <?php echo ($dial_whom_selector === 'number') ? 'checked="checked"' : '' ?> />
				</td>
				<td class="content-cell">
					<h4>Dial phone number</h4>
						<div class="vbx-input-container input">
							<input type="text" class="medium" name="dial-whom-number" value="<?php echo AppletInstance::getValue('dial-whom-number') ?>"/>
						</div>
				</td>
			</tr>
		</table>
	</div>
	<br />
	
	<h2>Waiting Speech</h2>
	<p>When the system tries to connect each agent, caller will hear:</p>
	<?php echo AppletUI::AudioSpeechPicker('prompt'); ?>

	<br />

	<h2>Call Recording</h2>
	<div class="radio-table">
		<table>
			<tr class="radio-table-row first <?php echo ($recording_enable === 'yes') ? 'on' : 'off' ?>">
				<td class="radio-cell">
					<input type="radio" class='dial-whom-selector-radio' name="recording-enable" value="yes" <?php echo ($recording_enable === 'yes') ? 'checked="checked"' : '' ?> />
				</td>
				<td class="content-cell">
					<h4>Enable</h4>
				</td>
			</tr>
			<tr class="radio-table-row last <?php echo ($recording_enable === 'no') ? 'on' : 'off' ?>">
				<td class="radio-cell">
					<input type="radio" class='dial-whom-selector-radio' name="recording-enable" value="no" <?php echo ($recording_enable === 'no') ? 'checked="checked"' : '' ?> />
				</td>
				<td class="content-cell">
					<h4>Disable</h4>
				</td>
			</tr>
		</table>
	</div>
	<br />

	<h2>Join Music</h2>
	<p>Music is played until two or more people have dialed in after call join.</p>
	<div class="vbx-full-pane">
		<fieldset class="vbx-input-container">
			<select name="wait-url" class="medium">
					<?php foreach($musicOptions as $option): ?>
					<option value="<?php echo $option['url']?>" <?php echo ($waitUrl == $option['url'])? 'selected="selected"' : '' ?>><?php echo $option['name']; ?></option>
					<?php endforeach; ?>
			</select>
		</fieldset>
	</div>

	<br />
	<h2>If nobody answers...</h2>
	<div class="vbx-full-pane nobody-answers-number">
		<?php echo AppletUI::DropZone('no-answer-redirect') ?>
	</div>
												
	<!-- Set the version of this applet -->
	<input type="hidden" name="version" value="3" />
</div><!-- .vbx-applet -->