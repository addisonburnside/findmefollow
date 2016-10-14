<?php
$list = isset($list)?$list:[];
$prering_time = isset($prering_time)?$prering_time:[];
$listring_time = isset($listring_time)?$listring_time:[];
?>
<script>
extension = <?php echo $extension; ?>;
</script>
<div class="message alert" style="display:none;"></div>
<form role="form">
	<div class="form-group">
		<label for="grplist" class="help"><?php echo _('Follow Me List')?> <i class="fa fa-question-circle"></i></label>
		<textarea id="grplist" name="grplist" class="form-control" rows="<?php echo count($list) < 3 ? 3 : count($list)?>"><?php echo implode("\n",$list)?></textarea>
		<span class="help-block help-hidden" data-for="grplist"><?php echo _('List extensions to ring, one per line. You can include an extension on a remote system, or an external number by suffixing a number with a pound (#).  ex:  2448089# would dial 2448089.')?><br><br><?php echo _("Note: Any local extension added will skip that local extension's FindMe/FollowMe, if you wish the system to use another extension's FindMe/FollowMe append a # onto that extension, eg 105#")?></span>
	</div>
	<div class="form-group">
		<label for="pre_ring" class="help"><?php echo sprintf(_('Ring %s First For'),$extension) ?> <i class="fa fa-question-circle"></i></label><br/>
		<select name="pre_ring" id="pre_ring" class="form-control">
			<?php foreach($prering_time as $key => $value) { ?>
				<option value="<?php echo $key?>" <?php echo ($prering == $key) ? 'selected' : ''?>><?php echo $value?> <?php echo _('Seconds')?></option>
			<?php } ?>
		</select>
		<span class="help-block help-hidden" data-for="pre_ring"><?php echo _('This is the number of seconds to ring the primary extension prior to proceeding to the follow-me list. The extension can also be included in the follow-me list. A 0 setting will bypass this.')?></span>
	</div>
	<div class="form-group">
		<label for="grptime" class="help"><?php echo _('Ring Followme List For') ?> <i class="fa fa-question-circle"></i></label><br/>
		<select name="grptime" id="grptime" class="form-control">
			<?php foreach($listring_time as $key => $value) { ?>
				<option value="<?php echo $key?>" <?php echo ($ringtime == $key) ? 'selected' : ''?>><?php echo $value?> <?php echo _('Seconds')?></option>
			<?php } ?>
		</select>
		<span class="help-block help-hidden" data-for="grptime"><?php echo _('Time in seconds that the phones will ring')?></span>
	</div>
	<div class="form-group">
		<label class="help" for="needsconf-h"><?php echo _('Use Confirmation')?> <i class="fa fa-question-circle"></i></label>
		<div class="onoffswitch">
			<input type="checkbox" name="needsconf" class="onoffswitch-checkbox" id="needsconf" <?php echo ($confirm) ? 'checked' : ''?>>
			<label class="onoffswitch-label" for="needsconf">
				<div class="onoffswitch-inner"></div>
				<div class="onoffswitch-switch"></div>
			</label>
		</div>
		<span class="help-block help-hidden" data-for="needsconf-h"><?php echo _("Enable this if you're calling external numbers that need confirmation - eg, a mobile phone may go to voicemail which will pick up the call. Enabling this requires the remote side push 1 on their phone before the call is put through.")?></span>
	</div>
	<div class="form-group">
		<label for="calgrp" class="help"><?php echo _('Calendar Group')?> <i class="fa fa-question-circle"></i></label>
		<select id="calgrp" name ="calgrp" class="form-control">
			<?php echo $calgrpopts?>
		</select>
		<span class="help-block help-hidden" data-for="calgrp"><?php echo _("If set the followme will only be active when the calendar group returns true.")?></span>
	</div>
	<div class="form-group">
		<label class="help" for="calmatch-h"><?php echo _('Calendar Match on Event')?> <i class="fa fa-question-circle"></i></label>
		<div class="onoffswitch">
			<input type="checkbox" name="calmatch" class="onoffswitch-checkbox" id="calmatch" <?php echo ($calendar_match == 'yes') ? 'checked' : ''?>>
			<label class="onoffswitch-label" for="calmatch">
				<div class="onoffswitch-inner"></div>
				<div class="onoffswitch-switch"></div>
			</label>
		</div>
		<span class="help-block help-hidden" data-for="calmatch-h"><?php echo _("When set to on follow me will be enabled whenever there is an event. When set to off followme will enable if the no event is present")?></span>
	</div>
</form>
