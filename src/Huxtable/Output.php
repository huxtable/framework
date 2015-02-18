<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Output
{
	/**
	 * @var string
	 */
	protected $buffer='';

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

	/**
	 * @return	string
	 */
	public function flush()
	{
		return $this->buffer;
	}

	/**
	 * @param	string	$string
	 */
	public function line($string)
	{
		$this->buffer = $this->buffer . $string . PHP_EOL;
	}

	/**
	 * @param	string	$string
	 */
	public function string($string)
	{
		$this->buffer .= $string;
	}

	/**
	 * @return	void
	 */
	public function unshiftLine($string)
	{
		$this->buffer = $string . PHP_EOL . $this->buffer;
	}
}

?>
