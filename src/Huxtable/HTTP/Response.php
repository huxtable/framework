<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable\HTTP;

class Response
{
	/**
	 * @var string
	 */
	protected $body;

	/**
	 * @var array
	 */
	protected $error;

	/**
	 * @var array
	 */
	protected $info;

	/**
	 * @return	void
	 */
	public function __construct( $body, $info, $error )
	{
		$this->body = $body;
		$this->info = $info;
		$this->error = $error;
	}

	/**
	 * @return	string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return	array
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @return	array
	 */
	public function getInfo()
	{
		return $this->info;
	}

	/**
	 * @return	array
	 */
	public function getStatus()
	{
		$messages = [
			200	=> 'OK',
			201	=> 'Created',
			202	=> 'Accepted',
			203	=> 'Non-Authoritative Information',
			204	=> 'No Content',
			205	=> 'Reset Content',
			206	=> 'Partial Content',

			300	=> 'Multiple Choices',
			301	=> 'Moved Permanently',
			302	=> 'Found',
			303	=> 'See Other',
			304	=> 'Not Modified',
			305	=> 'Use Proxy',
			306	=> 'Switch Proxy',
			307	=> 'Temporary Redirect',
			308	=> 'Permanent Redirect',

			400	=> 'Bad Request',
			401	=> 'Unauthorized',
			402	=> 'Payment Required',
			403	=> 'Forbidden',
			404	=> 'Not Found',
			405	=> 'Method Not Allowed',
			406	=> 'Not Acceptable',
			407	=> 'Proxy Authentication Required',
			408	=> 'Request Timeout',
			409	=> 'Conflict',
			410	=> 'Gone',

			500	=> 'Internal Server Error',
			501	=> 'Not Implemented',
			502	=> 'Bad Gateway',
			503	=> 'Service Unavailable',
			504	=> 'Gateway Timeout',
			520	=> 'Unknown Error'
		];

		$status['code'] = $this->info['http_code'];
		$status['message'] = $messages[ $status['code'] ];

		return $status;
	}
}

?>
