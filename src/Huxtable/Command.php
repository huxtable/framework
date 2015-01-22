<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Command
{
	/**
	 * @var array
	 */
	protected $aliases=[];

	/**
	 * @var string
	 */
	protected $closure;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $options=[];

	/**
	 * Array of Command objects
	 *
	 * @var array
	 */
	protected $subcommands=[];
	
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
	 * @param	string	$alias
	 */
	public function addAlias($alias)
	{
		$this->aliases[] = $alias;
	}

	/**
	 * @param	Command	$command
	 */
	public function addSubcommand(Command $command)
	{
		$this->subcommands[$command->getName()] = $command;
	}

	/**
	 * Return array of aliases
	 *
	 * @return	array
	 */
	public function getAliases()
	{
		return $this->aliases;
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
	 * Return the command options whose values have been set
	 *
	 * @return	array
	 */
	public function getOptionsWithValues()
	{
		$options = [];

		foreach( $this->options as $option => $value )
		{
			if( !is_null($value) )
			{
				$options[$option] = $value;
			}
		}

		return $options;
	}

	/**
	 * @param	string	$key
	 * @return	mixed
	 */
	public function getOptionValue($key)
	{
		if(isset($this->options[$key]) && !is_null($this->options[$key]))
		{
			return $this->options[$key];
		}
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
	 * @param	string	$option
	 */
	public function registerOption($option)
	{
		if(is_string($option))
		{
			$this->options[$option] = null;
		}
	}

	/**
	 * @param	mixed	$closure
	 */
	protected function setClosure($closure)
	{
		if($closure instanceof \Closure)
		{
			$this->closure = $closure->bindTo($this);
			return;
		}

		if(is_string($closure))
		{
			// Verify that static method exists
			$pieces = explode('::', $closure);
	
			if(count($pieces) == 2 && method_exists($pieces[0], $pieces[1]))
			{
				$this->closure = $closure->bindTo($this);
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
	 * @param	string	$key
	 * @param	string	$value
	 */
	public function setOptionValue($key, $value)
	{
		if(array_key_exists($key, $this->options))
		{
			$this->options[$key] = $value;
		}
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
