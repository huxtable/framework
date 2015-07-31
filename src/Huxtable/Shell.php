<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class Shell
{
	/**
	 * Execute an external command, generate friendly output and return the result
	 * 
	 * @param	string	$command
	 * @param	boolean	$formatted	Return formatted output in addition to raw
	 * @param	string	$prefix
	 * @return	array
	 */
	static public function exec( $command, $formatted=false, $prefix='> ' )
	{
		$command = $command . ' 2>&1';	// stifle output; we'll include it in the returned array
		$result = exec( $command, $commandOutput, $exitCode );

		$output['raw'] = '';

		if( $formatted )
		{
			$output['formatted'] = '';
			$color = $exitCode == 0 ? 'green' : 'red';
		}

		foreach( $commandOutput as $line )
		{
			$output['raw'] .= $line . PHP_EOL;

			if( $formatted )
			{
				$output['formatted'] .= Output::colorize( $prefix . $line, $color ) . PHP_EOL;
			}
		}

		$output['raw'] = trim( $output['raw'] );

		return [
			'output' => $output,
			'exitCode' => $exitCode
		];
	}
}

?>
