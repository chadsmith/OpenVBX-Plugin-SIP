<?php
	$ci =& get_instance();
	$ci->load->model('vbx_incoming_numbers');
	
	try {
		$numbers = $ci->vbx_incoming_numbers->get_numbers();
	}
	catch (VBX_IncomingNumberException $e) {
		log_message('Incoming numbers exception: '.$e->getMessage.' :: '.$e->getCode());
		$numbers = array();
	}
	
	$callerId = AppletInstance::getValue('callerId', null);
	$version = AppletInstance::getValue('version', null);
?>
<div class="vbx-applet dial-applet">

	<h2>SIP Endpoint</h2>
	<div class="vbx-full-pane">
		<fieldset class="vbx-input-container">
  		<div class="vbx-input-container input">
  			<input type="text" class="medium" name="endpoint" value="<?php echo AppletInstance::getValue('endpoint') ?>"/>
  		</div>
		</fieldset>
	</div>

	<br />
	<h2>Caller ID</h2>
	<div class="vbx-full-pane">
		<fieldset class="vbx-input-container">
			<select class="medium" name="callerId">
				<option value="">Caller's Number</option>
<?php if(count($numbers)) foreach($numbers as $number): $number->phone = normalize_phone_to_E164($number->phone); ?>
				<option value="<?php echo $number->phone; ?>"<?php echo $number->phone == $callerId ? ' selected="selected" ' : ''; ?>><?php echo $number->name; ?></option>
<?php endforeach; ?>
			</select>
		</fieldset>
	</div>

	<br />
	<h2>If nobody answers...</h2>
	<div class="vbx-full-pane nobody-answers-number">
		<?php echo AppletUI::DropZone('no-answer-redirect') ?>
	</div>

	<input type="hidden" name="version" value="1" />
</div>