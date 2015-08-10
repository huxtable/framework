<?php

/*
 * This file is part of Huxtable
 */
namespace Huxtable;

class FileInfo extends \SplFileInfo
{
	/**
	 * @param	string	$name
	 * @return	Huxtable\FileInfo
	 */
	public function child( $name )
	{
		return new self( $this->getPathname() . '/' . $name );
	}

	/**
	 * If $this is a directory, return an array of Huxtable\FileInfo objects
	 *   representing each child file
	 *
	 * @param	array	$skip	Filenames to skip
	 * @return	array|false
	 */
	public function children( $skip=[] )
	{
		if( !$this->isDir() )
		{
			return false;
		}

		$children = [];
		$filenames = scandir( $this->getPathname() );

		foreach( $filenames as $filename )
		{
			if( $filename == '.' || $filename == '..' || in_array( $filename, $skip ) )
			{
				continue;
			}

			$children[] = new self( $this->getPathname() . '/' . $filename );
		}

		return $children;
	}

	/**
	 * @return	void
	 */
	public function copy( FileInfo $dest )
	{
		if( $dest->isDir() )
		{
			$destFile = $dest->child( $this->getFilename() );
		}
		else
		{
			$destFile = $dest;
		}

		if( $this->isFile() )
		{
			copy( $this->getPathname(), $destFile->getPathname() );
		}
	}

	/**
	 * @return	string
	 */
	public function getContents()
	{
		if( !$this->isFile() )
		{
			throw new \Exception( "Could not read contents of '{$this->getPathname()}'" );
		}

		return file_get_contents( $this->getPathname() );
	}

	/**
	 * @param	int			$mode
	 * @param	boolean		$recursive
	 * @return	boolean
	 */
	public function mkdir( $mode=0777, $recursive=false )
	{
		// Already exists
		if( $this->isDir() || $this->isFile() )
		{
			return false;
		}

		return mkdir( $this->getPathname(), $mode, $recursive );
	}

	/**
	 * @return	Huxtable\FileInfo
	 */
	public function parent()
	{
		return new self( dirname( $this->getPathname() ) );
	}

	/**
	 * @param	string	$data
	 * @return	int
	 */
	public function putContents( $data )
	{
		return file_put_contents( $this->getPathname(), $data );
	}

	/**
	 * @return	boolean
	 */
	public function rmdir( $recursive=false )
	{
		if( $recursive )
		{
			exec( 'rm -r "' . $this->getPathname() . '"', $output, $code );
			return $code == 0;
		}

		return rmdir( $this->getPathname() );
	}

	/**
	 * @return	boolean
	 */
	public function unlink()
	{
		if( !$this->isFile() )
		{
			return false;
		}

		return unlink( $this->getPathname() );
	}
}

?>
