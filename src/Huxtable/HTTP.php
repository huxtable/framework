<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class HTTP
{
	/**
	 * @param	string	$hostname
	 * @return	Huxtable\HTTP\Response
	 */
	public function get( $hostname )
	{
		return $this->request( $hostname );
	}

	/**
	 * @param	string					$hostname
	 * @param	Huxtable\HTTP\Options	$options
	 * @return	Huxtable\HTTP\Response
	 */
	public function request( $hostname )
	{
		$curl = curl_init();

		curl_setopt( $curl, CURLOPT_URL, $hostname );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

		$body = curl_exec( $curl );
		$info = curl_getinfo( $curl );
		$error = [
			'code' => curl_errno( $curl ),
			'message' => curl_error( $curl )
		];

		curl_close( $curl );

		return new HTTP\Response( $body, $info, $error );
	}
}

?>
