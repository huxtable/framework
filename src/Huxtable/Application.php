<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

use Huxtable\Command\CommandInvokedException;
use Huxtable\Command\IncorrectUsageException;
use Huxtable\FileInfo;
use Huxtable\Git;
use Huxtable\HTTP;
use Huxtable\InvalidCommandException;

class Application
{
	const UPGRADE_ALREADY_NEWEST  = 10;
	const UPGRADE_ERR_PHP_VERSION = 20;
	const UPGRADE_ERR_PULL        = 30;
	const UPGRADE_ERR_SUBMODULE   = 40;
	const UPGRADE_SUCCESSFUL      = 50;

	/**
	 * @var array
	 */
	protected $aliases=[];

	/**
	 * @var array
	 */
	protected $commands=[];

	/**
	 * @var Command
	 */
	protected $defaultCommand;

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
	protected $output='';

	/**
	 * @var FileInfo
	 */
	protected $userDir;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @param	string	$name				Application name
	 * @param	string	$version
	 * @param	string	$phpMinimumVersion
	 * @param	Input	$input
	 */
	public function __construct( $name, $version, $phpMinimumVersion=null, Input $input=null )
	{
		if( !is_null( $phpMinimumVersion ) )
		{
			if( version_compare( PHP_VERSION, $phpMinimumVersion, '>=' ) == false )
			{
				$this->exit = 1;
				$this->output = "{$name}: Requires PHP {$phpMinimumVersion}+, found " . PHP_VERSION . PHP_EOL;
				$this->stop();
			}
		}

		$this->name    = $name;
		$this->version = $version;
		$this->input   = is_null($input) ? new Input() : $input;

		if( $this->userDir instanceof FileInfo )
		{
			if( !$this->userDir->isDir() )
			{
				$this->userDir->mkdir();
			}
		}

		// Register default commands
		$help = new Command('help', "Display help information about {$this->name}", [$this, 'commandHelp']);
		$help->setUsage('help <command>');
		$this->registerCommand($help);

		if( isset( $this->projectPath ) && isset( $this->remoteConfigURL ) )
		{
			$upgrade = new \Huxtable\Command( 'upgrade', "Fetch the newest version of {$this->name}", [$this, 'commandUpgrade'] );
			$upgrade->registerOption( 'verbose' );
			$upgradeUsage = <<<USAGE
upgrade [options]

OPTIONS
     --verbose
         be verbose


USAGE;
			$upgrade->setUsage( $upgradeUsage );
			$this->registerCommand($upgrade);
		}
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

		$command = $this->getCommand($name);

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

		foreach($this->input->getCommandOptions() as $key => $value)
		{
			$command->setOptionValue($key, $value);
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

		$command     = $this->getCommand($commandName);
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
	 * Expose self::upgrade as a command-line command
	 */
	protected function commandUpgrade()
	{
		$repo = new Git\Repository( $this->projectPath );

		try
		{
			$commandOptions = $this->input->getCommandOptions();
			$verbose = isset( $commandOptions['verbose'] );
			$upgrade = $this->upgrade( new HTTP, $repo, $verbose );
		}
		catch( \Exception $e )
		{
			$this->logError( $e->getMessage() );
			throw new CommandInvokedException( 'An error occurred while retrieving upgrade information. Please try again later.', 1 );
		}

		switch( $upgrade['status'] )
		{
			case self::UPGRADE_ALREADY_NEWEST:
				return "{$this->name} {$this->version} is currently the newest version available.";
				break;

			case self::UPGRADE_SUCCESSFUL:
				return "Successfully upgraded to {$this->name} {$upgrade['remoteVersion']}";
				break;

			case self::UPGRADE_ERR_PHP_VERSION:
				$phpLocalVersion = phpversion();
				return "{$this->name} {$upgrade['remoteVersion']} requires PHP v{$upgrade['phpMinimumVersion']}, found v{$phpLocalVersion}";
				break;

			case self::UPGRADE_ERR_PULL:
				$this->logError( "Git Error: An error occurred during 'git pull'. Run '{$this->name} upgrade --verbose'" );
				throw new CommandInvokedException( 'An error occurred while upgrading. Please try again later.', 1 );
				break;

			case self::UPGRADE_ERR_SUBMODULE:
				$this->logError( "Git Error: An error occurred during 'git submodule update'. Run '{$this->name} upgrade --verbose'" );
				throw new CommandInvokedException( 'An error occurred while upgrading. Please try again later.', 1 );
				break;
		}
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
	 * @param	string	$name
	 * @return	string
	 */
	protected function getCommand($name)
	{
		if(!isset($this->commands[$name]))
		{
			// check known aliases
			if(isset($this->aliases[$name]))
			{
				if(isset($this->commands[$this->aliases[$name]]))
				{
					return $this->commands[$this->aliases[$name]];
				}
			}

			throw new InvalidCommandException("Unknown command '{$name}'", InvalidCommandException::UNDEFINED);
		}

		return $this->commands[$name];
	}
	
	/**
	 * @return	string
	 */
	public function getUsage()
	{
		$usage  = "usage: {$this->name} [--version] <command> [<args>]".PHP_EOL.PHP_EOL;
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
	 * @param	string	$message
	 */
	public function logError( $message )
	{
		if( $this->userDir instanceof FileInfo )
		{
			date_default_timezone_set( 'UTC' );
			$logEntry = sprintf( "%s   %s\n", date( 'm/d/Y G:i:s' ), $message );
			$this->userDir->child( 'error.log' )->putContents( $logEntry, true );
		}
	}

	/**
	 * Optionally specify a command to run if the application is run without arguments
	 *
	 * @return	void
	 */
	public function registerDefaultCommand (Command $command)
	{
		$this->defaultCommand = $command;
	}

	/**
	 * @param	Command	$command
	 */
	public function registerCommand(Command $command)
	{
		$this->commands[$command->getName()] = $command;

		$aliases = $command->getAliases();
		foreach($aliases as $alias)
		{
			$this->aliases[$alias] = $command->getName();
		}
	}

	/**
	 * 
	 */
	public function run()
	{
		ksort($this->commands);

		$options = $this->input->getApplicationOptions();

		// --version
		if( isset($options['version'] ) )
		{
			$this->output = $this->commandVersion();
			return;
		}

		try
		{
			$this->output = $this->callCommand($this->input->getCommand(), $this->input->getCommandArguments());
		}

		// Command not registered
		catch(InvalidCommandException $e)
		{
			switch($e->getCode())
			{
				case InvalidCommandException::UNDEFINED:

					$this->output = sprintf
					(
						"%s: '%s' is not a %s command. See '%s help'",
						$this->name,
						$this->input->getCommand(),
						$this->name,
						$this->name
					);
					break;

				case InvalidCommandException::UNSPECIFIED:

					// Client has defined a default command
					if (!is_null ($this->defaultCommand))
					{
						$this->output = call_user_func ($this->defaultCommand->getClosure());
						$this->stop();
					}
					else
					{
						$this->output = $this->getUsage();
					}					
					break;
			}

			$this->exit = 1;
		}
		// Incorrect parameters given
		catch(\BadFunctionCallException $e)
		{
			$command   = $this->getCommand($this->input->getCommand());
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

			$this->output = sprintf('usage: %s %s', $this->name, $usage) . PHP_EOL;
			$this->exit = 1;
		}
		// Exception thrown by command
		catch(CommandInvokedException $e)
		{
			$this->output = sprintf ('%s: %s', $this->name, $e->getMessage()) . PHP_EOL;
			$this->exit = $e->getCode();
		}
		// Exception thrown by command: show usage
		catch(IncorrectUsageException $e)
		{
			$this->output = sprintf( 'usage: %s %s', $this->name, $e->getMessage() ) . PHP_EOL;
			$this->exit = 1;
		}
	}

	/**
	 * Terminate the application
	 */
	public function stop()
	{
		if (substr ($this->output, strlen($this->output) - 1) != PHP_EOL && !is_null ($this->output))
		{
			$this->output .= PHP_EOL;
		}

		echo $this->output;
		exit( $this->exit );
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

	/**
	 * Compare local and remote versions, fetch update if necessary
	 *
	 * @param	Huxtable\HTTP			$http
	 * @param	Huxtable\Git\Repository	$repo
	 * @param	boolean					$verbose
	 * @return	array
	 */
	public function upgrade( HTTP $http, Git\Repository $repo, $verbose=false )
	{
		if( $verbose )
		{
			echo 'Checking remote version... ';
		}
		$response = $http->get( $this->remoteConfigURL );

		$error = $response->getError();
		if( $error['code'] != 0 )
		{
			if( $verbose )
			{
				echo 'failed.' . PHP_EOL;
			}

			throw new \Exception( "curl Error: {$error['message']}", $error['code'] );
		}

		$status = $response->getStatus();
		if( $status['code'] != 200 )
		{
			if( $verbose )
			{
				echo 'failed.' . PHP_EOL;
			}
			throw new \Exception( "HTTP Error: {$status['message']} ({$this->remoteConfigURL})", $status['code'] );
		}

		$body = $response->getBody();
		$remoteConfig = json_decode( $body, true );
		if( json_last_error() != JSON_ERROR_NONE )
		{
			if( $verbose )
			{
				echo 'failed.' . PHP_EOL;
			}
			throw new \Exception( 'JSON Error: ' . json_last_error_msg(), json_last_error() );
		}

		$result = [
			'remoteVersion' => $remoteConfig['version'],
			'phpMinimumVersion' => $remoteConfig['php-min']
		];

		// Should we update?
		$remoteVersionIsNewer = version_compare( $this->version, $remoteConfig['version'], '<' );

		if( $remoteVersionIsNewer )
		{
			if( $verbose )
			{
				echo 'update found.' . PHP_EOL;
			}
		}
		else
		{
			if( $verbose )
			{
				echo 'done.' . PHP_EOL;
			}
			
			$result['status'] = self::UPGRADE_ALREADY_NEWEST;
			return $result;
		}

		$phpVersionMeetsRequirement = version_compare( phpversion(), $remoteConfig['php-min'], '>=' );

		if( !$phpVersionMeetsRequirement )
		{
			$result['status'] = self::UPGRADE_ERR_PHP_VERSION;
			return $result;
		}

		// Pull
		if( $verbose )
		{
			echo 'Fetching update... ';
		}
		$resultPull = $repo->pull();

		if( $resultPull['exitCode'] != 0 )
		{
			$result['status'] = self::UPGRADE_ERR_PULL;

			if( $verbose )
			{
				echo 'failed.' . PHP_EOL;
				echo $resultPull['output']['raw'] . PHP_EOL . PHP_EOL;
			}
			return $result;
		}

		$resultSubmodules = $repo->submoduleUpdate([
			'init' => true,
			'recursive' => true
		]);

		if( $resultSubmodules['exitCode'] != 0 )
		{
			$result['status'] = self::UPGRADE_ERR_SUBMODULE;

			if( $verbose )
			{
				echo 'failed.' . PHP_EOL;
				echo $resultSubmodules['output']['raw'] . PHP_EOL . PHP_EOL;
			}
			return $result;
		}

		if( $verbose )
		{
			echo 'done.' . PHP_EOL;
		}

		$result['status'] = self::UPGRADE_SUCCESSFUL;
		return $result;
	}
}

?>
