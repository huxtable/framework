<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Command
{
	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $closure;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * Array of Command objects
	 *
	 * @var array
	 */
	protected $subcommands;
	
	/**
	 * @var string
	 */
	protected $usage='';

	/**
	 * @param	string	$name
	 * @param	string	$description
	 * @param	mixed	$closure 		Closure, name of static function or [object, function] array
	 */
	public function __construct($name, $description, $closure)
	{
		$this->name = $name;
		$this->description = $description;
		$this->setClosure($closure);
	}

	/**
	 * @param	Command	$command
	 */
	public function addSubcommand(Command $command)
	{
		$this->subcommands[$command->getName()] = $command;
	}

	/**
	 * @return	Closure
	 */
	public function getClosure()
	{
		if($this->closure instanceof \Closure)
		{
			return $this->closure;
		}

		$reflect = new \ReflectionMethod($this->closure);
		return $reflect->getClosure();
	}

	/**
	 * @return	string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @return	string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return	Command
	 */
	public function getSubcommand($name)
	{
		if(isset($this->subcommands[$name]))
		{
			return $this->subcommands[$name];
		}
	}

	/**
	 * Return array of Command objects registered as subcommands
	 *
	 * @return	array
	 */
	public function getSubcommands()
	{
		return $this->subcommands;
	}

	/**
	 * @return	string
	 */
	public function getUsage()
	{
		if(strlen($this->usage) > 0)
		{
			return $this->usage;
		}

		$parameters = [];

		// Inspect closure parameters to build usage string
		$rf = new \ReflectionFunction($this->getClosure());

		foreach($rf->getParameters() as $parameter)
		{
			$pattern = $parameter->isOptional() ? '[<%s>]' : '<%s>';
			$parameters[] = sprintf($pattern, $parameter->name);
		}

		return sprintf('%s %s', $this->name, implode(' ', $parameters));
	}

	/**
	 * @param	mixed	$closure
	 */
	protected function setClosure($closure)
	{
		if($closure instanceof \Closure)
		{
			$this->closure = $closure;
			return;
		}

		if(is_string($closure))
		{
			// Verify that static method exists
			$pieces = explode('::', $closure);
	
			if(count($pieces) == 2 && method_exists($pieces[0], $pieces[1]))
			{
				$this->closure = $closure;
				return;
			}
		}

		if(is_array($closure) && count($closure) == 2)
		{
			$object     = $closure[0];
			$methodName = $closure[1];

			if(is_object($object) && is_string($methodName) && method_exists($object, $methodName))
			{
				$reflect = new \ReflectionClass($object);
				$method  = $reflect->getMethod($methodName);

				$this->closure = $method->getClosure($object);
				return;
			}
		}

		throw new Command\InvalidClosureException("Invalid closure passed for '{$this->name}'");
	}

	/**
	 * @param	string	$usage
	 */
	public function setUsage($usage)
	{
		$this->usage = $usage;
	}
}

?>
