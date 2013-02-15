<?php

class TwimlDialException extends Exception {};

class TwimlDialSIP {

	private $use_ci_session = true;
	
	static $hangup_stati = array('completed', 'answered');
	static $voicemail_stati = array('no-answer', 'failed');
	
	protected $cookie_name;

	public $state;
	public $response;
	
	public $dial;
	
	protected $timeout = false;
	protected $transcribe = true;
	protected $voice = 'man';
	protected $language = 'en';

	public $default_timeout = 20;
	
	public function __construct($settings = array())
	{
		$this->response = new TwimlResponse;
		
		$this->cookie_name = 'state-'.AppletInstance::getInstanceId();
		$this->version = AppletInstance::getValue('version', null);
		
		$this->callerId = AppletInstance::getValue('callerId', null);
		if (empty($this->callerId) && !empty($_REQUEST['From'])) 
		{
			$this->callerId = $_REQUEST['From'];
		}

		$this->endpoint = AppletInstance::getValue('endpoint');

		$this->no_answer_redirect = AppletInstance::getDropZoneUrl('no-answer-redirect');
		
		if (count($settings)) {
			foreach ($settings as $setting => $value) 
			{
				if (isset($this->$setting)) 
				{
					$this->$setting = $value;
				}
			}
		}
	}

	public function getDial() 
	{
		if (empty($this->dial)) 
		{
			$this->dial = $this->response->dial(NULL, array(
					'action' => current_url(),
					'callerId' => $this->callerId,
					'timeout' => (!empty($this->timeout)) ? $this->timeout : $this->default_timeout
				));
		}
		return $this->dial;
	}

	public function dial($device_or_user) 
	{
		$dialed = false;
		
		$dialed = $this->dialSip($device_or_user);
		
		return $dialed;
	}

	public function dialSip($endpoint) 
	{
		$dial = $this->getDial();
		$dial->sip($endpoint);
		$this->state = 'calling';
		return true;
	}

	public function noanswer() 
	{
		$_status = null;
		if(empty($this->no_answer_redirect)) 
		{
			$this->response->hangup();
		}
		$this->response->redirect($this->no_answer_redirect);
	}

	public function hangup() 
	{
		$this->response->hangup();
	}

	public function respond() 
	{
		$this->response->respond();
	}

	public function set_state() 
	{
		$call_status = isset($_REQUEST['CallStatus']) ? $_REQUEST['CallStatus'] : null;
		$dial_call_status = isset($_REQUEST['DialCallStatus']) ? $_REQUEST['DialCallStatus'] : null;
		
		$this->state = $this->_get_state();

		if (in_array($dial_call_status, self::$hangup_stati) 
			|| in_array($call_status, self::$hangup_stati))
		{
			$this->state = 'hangup';
		}
		elseif(in_array($dial_call_status, self::$voicemail_stati))
		{
			$this->state = 'voicemail';
		}
		elseif (!$this->state) 
		{
			$this->state = 'new';
		}
	}

	private function _get_state() 
	{
		$state = null;
		if ($this->use_ci_session) 
		{
			$CI =& get_instance();
			$state = $CI->session->userdata($this->cookie_name);
		}
		else 
		{
			if (!empty($_COOKIE[$this->cookie_name])) 
			{
				$state = $_COOKIE[$this->cookie_name];
			}
		}

		return $state;
	}

	public function save_state() 
	{
		$state = $this->state;
		if ($this->use_ci_session) 
		{
			$CI =& get_instance();
			$CI->session->set_userdata($this->cookie_name, $state);
		}
		else 
		{
			set_cookie($this->cookie_name, $state, time() + (5 * 60));
		}
	}
}