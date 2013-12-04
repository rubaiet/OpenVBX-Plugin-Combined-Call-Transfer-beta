<?php
	$ci =& get_instance();
	$ci->load->library('pagination');

	// check if table 'conference_calls' and 'recorded_calls' not exists
	if ((!$ci->db->table_exists('conference_calls')) OR (!$ci->db->table_exists('recorded_calls')))
	{
		// log_message('error', "directory: ". dirname(__FILE__) . '/db.sql' ."\n\n");
		$queries = explode(';', file_get_contents(dirname(__FILE__) . '/db.sql'));
		foreach($queries as $query)
			if(trim($query))
				$ci->db->query($query);		
	}

	$ci->load->model('vbx_recorded_call');
	
	// set pagination configuration
	$items_per_page = '20';
	$max = $ci->input->get_post('max');
	$offset = $ci->input->get_post('offset');
	if (empty($max)) 
	{
		$max = $items_per_page;
	}
	
	$items = VBX_Recorded_call::search(array('status' => 'completed'), $max, $offset);

	if(empty($items))
	{
		set_banner('items', 'No record available!');
	}

	$total_items = VBX_Recorded_call::count(array('status' => 'completed'));
	
	// print($total_items); exit;

	$page_config = array(
		'base_url' => site_url('p/log/'),
		'total_rows' => $total_items,
		'per_page' => $max
	);
	
	$ci->pagination->initialize($page_config);
	$pagination = CI_Template::literal($ci->pagination->create_links());

	OpenVBX::addCSS('player/skin/jplayer-black-and-blue.css');

	
	$plugin = OpenVBX::$currentPlugin;
	$info = $plugin->getInfo();
	$path_jquery = base_url() . implode('/', array('plugins', $info['dir_name'], 'js/jquery-1.7.1.min.js'));
	$path_js = base_url() . implode('/', array('plugins', $info['dir_name'], 'player/jquery.jplayer.min.js'));
	
	echo '<script type="text/javascript" src="'.version_url($path_jquery).'"></script>';
	echo '<script type="text/javascript" src="'.version_url($path_js).'"></script>';
	// echo '<script type="text/javascript" src="'.version_url($colorbox_js).'"></script>';
	
	function generateFlashAudioPlayer($url, $size='sm')
	{
		$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
		$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
		$ipad = strpos($_SERVER['HTTP_USER_AGENT'],"iPad");
		$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
		$berry = strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
		$palmpre = strpos($_SERVER['HTTP_USER_AGENT'],"webOS");
		$palm = strpos($_SERVER['HTTP_USER_AGENT'],"PalmOS");
		if ($iphone || $ipod || $android || $berry || $ipad || $palmpre || $palm == true)
		{
			switch($size)
			{
				case "sm":
					$width=165;
					break;
				case "lg":
					$width=400;
					break;
			}
			?><audio src="<?php echo $url; ?>" controls preload="none" style="width:<?php echo $width; ?>px;"></audio><?php
		}
		else
		{
			$id = uniqid("",true);
			$id = str_replace(".","",$id);
			?>
		<div id="jquery_jplayer_<?php echo $id; ?>" class="jp-jplayer"></div>

		<div class="jp-container_<?php echo $id; ?>"<?php if($size=="lg") echo " style='display:inline-block; width:360px;'"; ?>>
			<div class="jp-audio"<?php if($size=="sm") echo " style='width:160px;'"; ?>>
				<div class="jp-type-single">
					<div id="jp_interface_1" class="jp-interface">
						<ul class="jp-controls">
							<li style="background:none;"><a href="#" class="jp-play" tabindex="1">play</a></li>
							<li style="background:none;"><a href="#" class="jp-pause" tabindex="1">pause</a></li>
							<li style="background:none;"><a href="#" class="jp-mute"<?php if($size=="sm") echo " style='left:133px;'"; ?> tabindex="1">mute</a></li>
							<li style="background:none;"><a href="#" class="jp-unmute"<?php if($size=="sm") echo " style='left:133px;'"; ?> tabindex="1">unmute</a></li>
						</ul>
						<div class="jp-progress-container"<?php if($size=="sm") echo " style='width:65px;'"; ?>>
							<div class="jp-progress"<?php if($size=="sm") echo " style='width:60px;'"; ?>>
								<div class="jp-seek-bar">
									<div class="jp-play-bar"></div>
								</div>
							</div>
						</div>
						<div class="jp-volume-bar-container"<?php if($size=="sm") echo " style='display:none;'"; ?>>
							<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
				$("#jquery_jplayer_<?php echo $id; ?>").jPlayer({
					ready: function () {
						$(this).jPlayer("setMedia", {
							mp3: "<?php echo $url.".mp3"; ?>",
							wav: "<?php echo $url.".wav"; ?>"
						});
					},
					play: function() { // To avoid both jPlayers playing together.
						$(this).jPlayer("pauseOthers");
					},
					repeat: function(event) { // Override the default jPlayer repeat event handler
						if(event.jPlayer.options.loop) {
							$(this).unbind(".jPlayerRepeat").unbind(".jPlayerNext");
							$(this).bind($.jPlayer.event.ended + ".jPlayer.jPlayerRepeat", function() {
								$(this).jPlayer("play");
							});
						} else {
							$(this).unbind(".jPlayerRepeat").unbind(".jPlayerNext");
							$(this).bind($.jPlayer.event.ended + ".jPlayer.jPlayerNext", function() {
								$("#jquery_jplayer_2").jPlayer("play", 0);
							});
						}
					},
					swfPath: "player",
					supplied: "mp3, wav",
					volume: 1,
					preload: "none",
					wmode: "window",
					cssSelectorAncestor: ".jp-container_<?php echo $id; ?>"
				});

			</script>
			<?php
		}
	} // end function generateFlashAudioPlayer
?>
<script>
	$(document).ready(function(){
		$(".inline").colorbox({inline:true, width:"50%"});
	});
</script>

<div class="vbx-content-main vbx-calls">

		<div class="vbx-content-menu vbx-content-menu-top">
			<h2 class="vbx-content-heading">Inbound Call Log</h2>
			<?php echo $pagination; ?>
		</div><!-- vbx-content-menu -->

		<?php if(!empty($items)): ?>

		<div class="vbx-table-section">
		<table id="calls-table" class="vbx-items-grid">
			<thead>
				<tr class="items-head">
					<th class="call_id">ID</th>
					<th>From</th>
					<th>To</th>
					<th class="duration">Duration</th>
					<th>Created</th>
					<!--<th>Transcript</th>-->
					<th>Play</th>
					<th>URL</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($items as $item): ?>
				<tr class="items-row">
					<td><?php echo $item['id']; ?></td>
					<td><?php echo $item['from']; ?></td>
					<td><?php echo $item['to']; ?></td>
					<td><?php echo $item['duration']; ?></td>
					<td><?php echo $item['created']; ?></td>
					<!--<td>
						<?php
							if($item['transcript'] != ''):
						?>
							<a class='inline' href="#inline_content_<?php echo $item['id']; ?>" title="Detail">Transcript</a>
							<div style='display:none'>
								<div id="inline_content_<?php echo $item['id']; ?>" style="padding:10px; background:#fff;">
									<p><?php echo nl2br($item['transcript']); ?></p>
								</div>
							</div>
						<?php
							else:
								echo '&nbsp;';
							endif;
						?>
					</td>
					-->
					<td>
						<?php 
							if($item['recording_url'] != '')
								echo generateFlashAudioPlayer($item['recording_url'],"sm");
							else
								echo '&nbsp;';
						?>
					</td>
					<td>
						<?php 
							if($item['recording_url'] != '')
								 echo anchor($item['recording_url'], 'Play', array('title' => 'Play on browser', 'target' => '_blank'));
							else
								echo '&nbsp;';
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table><!-- .vbx-items-grid -->
		</div><!-- .vbx-table-section -->

		<?php else: ?>

		<div class="vbx-content-container">
				<div class="flows-blank">
						<h2>No record found!</h2>
				</div>
			<div class="vbx-content-section">
			</div><!-- .vbx-content-section -->
		</div><!-- .vbx-content-container -->

		<?php endif; ?>

</div><!-- .vbx-content-main -->