<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Input
{
	/**
	 * @var array
	 */
	protected $arguments;

	/**
	 * @var string
	 */
	protected $command;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @param	array	$arguments
	 */
	public function __construct(array $arguments=[])
	{
		global $argv;

		$this->arguments['command']	  = [];

		$this->options['application'] = [];
		$this->options['command']	  = [];

		if(empty($arguments))
		{
			$arguments = $argv;

			// Grab piped input if present
			stream_set_blocking(STDIN, 0);
			$stdin = stream_get_contents(STDIN);

			if(strlen($stdin) > 0)
			{
				$arguments[] = trim($stdin);
			}
		}

		$this->parseArguments($arguments);
	}

	/**
	 * @return	array
	 */
	public function getApplicationOptions()
	{
		return $this->options['application'];
	}

	/**
	 * @return	string
	 */
	public function getCommand()
	{
		if(!is_null($this->command))
		{
			return $this->command;
		}

		if(isset($this->options['application'][0]))
		{
			return $this->options['application'][0];
		}
	}

	/**
	 * @return	array
	 */
	public function getCommandArguments()
	{
		return $this->arguments['command'];
	}

	/**
	 * @return	array
	 */
	public function getCommandOptions()
	{
		return $this->options['command'];
	}

	/**
	 * @return	none
	 */
	protected function parseArguments(array $arguments)
	{
		array_shift($arguments);

		// Application options are any options before the command
		$i=0;
		while(isset($arguments[$i]) && substr($arguments[$i], 0, 1) == '-')
		{
			// Short option
			if(substr($arguments[$i], 1, 1) != '-')
			{
				$option = substr($arguments[$i], 1);
			}
			// Long option
			else
			{
				$option = substr($arguments[$i], 2);
			}

			$this->options['application'][] = $option;
			array_shift($arguments);

			$i++;
		}

		if(count($arguments) == 0)
		{
			return;
		}

		// Command is the first of remaining arguments
		$this->command = array_shift($arguments);

		$i=0;
		while(isset($arguments[$i]))
		{
			// Command options are any options following the command
			if(substr($arguments[$i], 0, 1) == '-')
			{
				// Short option
				if(substr($arguments[$i], 1, 1) != '-')
				{
					$option = substr($arguments[$i], 1);
				}
				// Long option
				else
				{
					$option = substr($arguments[$i], 2);
				}
	
				$this->options['command'][] = $option;		
			}
			// Command arguments
			else
			{
				$this->arguments['command'][] = $arguments[$i];
			}

			$i++;
		}	
	}
}

?>
