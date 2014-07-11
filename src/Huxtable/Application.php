<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

use Huxtable\Command\CommandInvokedException;
use Huxtable\InvalidCommandException;

class Application
{
	/**
	 * @var array
	 */
	protected $commands=[];

	/**
	 * @var int
	 */
	protected $exit=0;

	/**
	 * @var Input
	 */
	protected $input;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @param	string	$name		Application name
	 * @param	string	$version
	 * @param	Input	$input
	 */
	public function __construct($name, $version, Input $input=null)
	{
		$this->name    = $name;
		$this->version = $version;
		$this->input   = is_null($input) ? new Input() : $input;

		// Register default commands
		$help = new Command('help', "Display help information about {$this->name}", [$this, 'commandHelp']);
		$help->setUsage('help <command>');

		$this->registerCommand($help);
		$this->registerCommand(new Command('version', "Display version number", [$this, 'commandVersion']));
	}

	/**
	 * @param	string	$name
	 * @param	array	$arguments
	 * @return	mixed
	 */
	public function callCommand($name, $arguments=[])
	{
		if(is_null($name))
		{
			throw new InvalidCommandException("No command specified", InvalidCommandException::UNSPECIFIED);
		}

		if(!isset($this->commands[$name]))
		{
			throw new InvalidCommandException("Command '{$name}' not registered", InvalidCommandException::UNDEFINED);
		}

		$command = $this->commands[$name];

		// Attempt calling subcommand first
		if(count($arguments) > 0)
		{
			// Subcommand is registered
			if(is_null($command->getSubcommand($arguments[0])) == false)
			{
				$command = $command->getSubcommand($arguments[0]);
				array_shift($arguments);
			}
		}

		// Number of items in $arguments may not be fewer
		// than number of required closure parameters
		$rf = new \ReflectionFunction($command->getClosure());

		if($rf->getNumberOfRequiredParameters() > count($arguments))
		{
			throw new \BadFunctionCallException(sprintf
			(
				"Missing arguments for '%s': %s expected, %s given",
				$name,
				$rf->getNumberOfRequiredParameters(),
				count($arguments)
			));
		}

		return call_user_func_array($command->getClosure(), $arguments);
	}

	/**
	 * Display help information about a registered command
	 *
	 * @param	string	$commandName
	 * @return	string
	 */
	public function commandHelp($commandName=null)
	{
		if(is_null($commandName))
		{
			return $this->getUsage();
		}

		if(!isset($this->commands[$commandName]))
		{
			throw new CommandInvokedException(sprintf("'%s' is not a %s command", $commandName, $this->name), 1);
		}

		$command     = $this->commands[$commandName];
		$subcommands = $command->getSubcommands();

		if(count($subcommands) == 0)
		{
			return sprintf('usage: %s %s', $this->name, $command->getUsage());
		}

		$output = <<<OUTPUT
usage: %s

Subcommands for '{$commandName}' are:
%s
OUTPUT;

		$usage = '';
		$descriptions = '';

		foreach($subcommands as $command)
		{
			$usage .= sprintf("%s \033[4;29m%s\033[0m %s", $this->name, $commandName, str_replace($command->getName(), "\033[4;29m{$command->getName()}\033[0m", $command->getUsage())).PHP_EOL.'       ';

			$descriptions .= sprintf('   %-11s%s', $command->getName(), $command->getDescription()).PHP_EOL;
		}

		$output = sprintf($output, trim($usage), $descriptions);

		return $output.PHP_EOL;
	}

	/**
	 * Display version number
	 *
	 * @return	string
	 */
	protected function commandVersion()
	{
		return sprintf('%s version %s', $this->name, $this->version);
	}

	/**
	 * @return	string
	 */
	public function getUsage()
	{
		$usage  = "usage: {$this->name} <command> [<args>]".PHP_EOL.PHP_EOL;
		$usage .= "Commands are:".PHP_EOL;

		foreach($this->commands as $command)
		{
			$usage .= sprintf('   %-11s%s', $command->getName(), $command->getDescription()).PHP_EOL;
		}

		$usage .= PHP_EOL."See '{$this->name} help <command>' to read about a specific command";

		return $usage;
	}

	/**
	 * @return	string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param	Command	$command
	 */
	public function registerCommand(Command $command)
	{
		$this->commands[$command->getName()] = $command;
	}

	/**
	 * 
	 */
	public function run()
	{
		ksort($this->commands);

		try
		{
			echo $output = $this->callCommand($this->input->getCommand(), $this->input->getCommandArguments());

			if(strlen($output) != 0 && substr($output, strlen($output) - 1) != PHP_EOL)
			{
				echo PHP_EOL;
			}
		}

		// Command not registered
		catch(InvalidCommandException $e)
		{
			switch($e->getCode())
			{
				case InvalidCommandException::UNDEFINED:

					$output = sprintf
					(
						"%s: '%s' is not a %s command. See '%s help'",
						$this->name,
						$this->input->getCommand(),
						$this->name,
						$this->name
					);
					break;

				case InvalidCommandException::UNSPECIFIED:
					$output = $this->getUsage();
					break;
			}

			echo $output.PHP_EOL;
			$this->exit = 1;
		}
		// Incorrect parameters given
		catch(\BadFunctionCallException $e)
		{
			$command   = $this->commands[$this->input->getCommand()];
			$usage     = $command->getUsage();
			$arguments = $this->input->getCommandArguments();

			// Attempt calling subcommand first
			if(count($arguments) > 0)
			{
				// Subcommand is registered
				if(is_null($command->getSubcommand($arguments[0])) == false)
				{
					$usage    = $command->getName();
					$command  = $command->getSubcommand($arguments[0]);
					$usage   .= ' '.$command->getUsage();

					array_shift($arguments);
				}
			}

			echo sprintf('usage: %s %s', $this->name, $usage).PHP_EOL;
			$this->exit = 1;
		}
		// Exception thrown by command
		catch(CommandInvokedException $e)
		{
			echo sprintf('%s: %s', $this->name, $e->getMessage()).PHP_EOL;
			$this->exit = $e->getCode();
		}
	}

	/**
	 * Terminate the application
	 */
	public function stop()
	{
		exit($this->exit);
	}

	/**
	 * @param	string	$name		Name of command to unregister
	 */
	public function unregisterCommand($name)
	{
		if(isset($this->commands[$name]))
		{
			unset($this->commands[$name]);
		}
	}
}

?>