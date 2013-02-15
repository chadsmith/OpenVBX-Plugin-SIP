<?php
include_once('TwimlDialSIP.php');
define('DIAL_COOKIE', 'state-'.AppletInstance::getInstanceId());

$CI =& get_instance();

$transcribe = (bool) $CI->vbx_settings->get('transcriptions', $CI->tenant->id);
$voice = $CI->vbx_settings->get('voice', $CI->tenant->id);
$language = $CI->vbx_settings->get('voice_language', $CI->tenant->id);
$timeout = $CI->vbx_settings->get('dial_timeout', $CI->tenant->id);

$dialer = new TwimlDialSIP(array(
	'transcribe' => $transcribe,
	'voice' => $voice,
	'language' => $language,
	'timeout' => $timeout
));
$dialer->set_state();

try {
	switch ($dialer->state) {
		case 'voicemail':
			$dialer->noanswer();
			break;
		case 'hangup':
			$dialer->hangup();
			break;
		default:
		  $dialer->dial($dialer->endpoint);
			break;
	}
}
catch (Exception $e) {
	error_log('Dial Applet exception: '.$e->getMessage());
	$dialer->response->say("We're sorry, an error occurred while dialing. Goodbye.");
	$dialer->hangup();
}

$dialer->save_state();
$dialer->respond();