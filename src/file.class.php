<?php

/**
 * File-related utility class
 * 
 * @package Utilities
 * @author Nev Stokes <mail@nevstokes.com>
 */

namespace nevstokes\Utilities;

/**
 * Various methods to assist in the processing, formatting and identification
 * of files
 * 
 * @package Utilities
 */
class FileUtilty
{

	protected $_file;

	/**
	 * Constructor
	 * 
	 * The only thing needed initially is the name of the file. A test is 
	 * performed on instantiation to ensure that the file is readable.
	 * 
	 * @param string $file The file we're interested in
	 * @throws RuntimeException Exception thrown if the file is unreadable
	 */
	public function __construct($file)
	{
		// Make sure we can read the file
		if (
			   file_exists($file)
			&& is_file($file)
			&& is_readable($file)
		) {
			$this->_file = $file;
		} else {
			throw new RuntimeException('The file could not be read');
		}
	}

	/**
	 * Format an integer as a file size
	 * 
	 * Return a formatted number appended with the appropriate order
	 * of magnitude.
	 * 
	 * @param int $precision (optional) How many decimal places to
	 * include. Defaults to none.
	 * @return string
	 */
	public function formatSize($precision='0')
	{
		// Get the file size
		$size = filesize($this->_file);

		// http://en.wikipedia.org/wiki/SI_prefix
		$postfixes = array('b', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

		$power = floor(log($size, 1024));
		return ($power > 0) ? number_format(($size / pow(1024, $power)), $precision, '.', '') . $postfixes[$power] : 0;
	}

	/**
	 * Loads a file a chunk at a time
	 * 
	 * Use this function as an alternative to file_put_contents when dealing
	 * with large files to avoid memory issues.
	 * 
	 * @param int $chunk (optional, default 1KB) The size of data to read by
	 * @return string
	 */
	public function readChunked($chunk=1024)
	{
		$buffer = '';

		// File already validated for reading
		$handle = fopen($this->_file, 'r');

		// Read in the file chunk-by-chunk
		while (false !== feof($handle)) {
			$buffer .= fread($handle, $chunk);
		}

		fclose($handle);

		return $buffer;
	}

	/**
	 * Determine if a file is binary
	 * 
	 * Attempts to work out the type of a file based upon the percentage of
	 * control characters at the head of the file
	 * 
	 * @param string $file The filename to check
	 * @return bool Returns true if the file is binary and false otherwise
	 * @throws RuntimeException Exception thrown if the file could not be read
	 * 
	 * @link http://www.ultrashock.com/forum/viewthread/98391/
	 */
	public function isBinary($file)
	{
		// Read in the first 512 bytes of the file
		$fh = fopen($this->_file, 'r');
		$blk = fread($fh, 512);
		fclose($fh);
		clearstatcache();

		return (
			   false
			|| (substr_count($blk, "^ -~", "^\r\n") / 512 > 0.3)
			|| (substr_count($blk, "\x00") > 0)
		);
	}

}

?>