<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Output
{
	/**
	 * Returns string in formatted, padded block
	 *
	 * @param	string	$string			String to colorize
	 * @param	string	$foreground		Name of foreground color
	 * @param	string	$background		Name of background color
	 * @param	string	$padding		Block padding
	 * @param	string	$indent			Block indent
	 * @return	string
	 */
	public static function block($string, $foreground=null, $background=null, $padding=2, $indent=0)
	{
		$length  = strlen($string) + (2 * $padding);
		$empty   = '';
		$content = '';

		for($i=1; $i<=$length; $i++)
		{
			$empty .= ' ';
		}

		for($j=1; $j<= (2 * $padding) + 1; $j++)
		{
			if($j == $padding + 1)
			{
				$content .= $string;
			}
			else
			{
				$content .= ' ';
			}
		}

		$result[] = self::colorize($empty, $foreground, $background);
		$result[] = self::colorize($content, $foreground, $background);
		$result[] = self::colorize($empty, $foreground, $background);

		for($k=0; $k<count($result); $k++)
		{
			$left = '';
			for($l=0; $l<$indent; $l++)
			{
				$left .= ' ';
			}

			$result[$k] = $left . $result[$k];
		}

		return implode(PHP_EOL, $result).PHP_EOL;
	}
	/**
	 * @param	string	$string			String to colorize
	 * @param	string	$foreground		Name of foreground color
	 * @param	string	$background		Name of background color
	 * @return	string
	 */
	public static function colorize($string, $foreground=null, $background=null)
	{
		$foregroundColors =
		[
			'black'		=> '0;30',
			'red'		=> '0;31',
			'green'		=> '0;32',
			'yellow'	=> '0;33',
			'blue'		=> '0;34',
			'purple'	=> '0;35',
			'cyan'		=> '0;36',
			'gray'		=> '0;37',
		];

		$backgroundColors =
		[
			'black'		=> '40',
			'red'		=> '41',
			'green'		=> '42',
			'yellow'	=> '43',
			'blue'		=> '44',
			'purple'	=> '45',
			'cyan'		=> '46',
			'gray'		=> '47',
		];

		$colorized = '';
		$colorized .= isset($foregroundColors[$foreground]) ? "\033[".$foregroundColors[$foreground]."m" : '';
		$colorized .= isset($backgroundColors[$background]) ? "\033[".$backgroundColors[$background]."m" : '';
		$colorized .= $string;
		$colorized .= "\033[0m";

		return $colorized;
	}
}

?>
