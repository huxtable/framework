<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Format
{
	/**
	 * @param	string	$timestamp
	 * @return	string	Date string formatted like `ls` dates
	 */
	public static function date($timestamp=null)
	{
		if(is_null($timestamp))
		{
			$timestamp = time();
		}

		$now  = getdate();
		$date = getdate($timestamp);

		$detail = ($now[0] - $date[0] <= 15778500) ? sprintf('%02s:%02s', $date['hours'], $date['minutes']) : $date['year'];
		
		return sprintf('%.3s %2s %5s', $date['month'], $date['mday'], $detail);
	}
}

?>
