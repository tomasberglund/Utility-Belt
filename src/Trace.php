<?php

/**
 * @package Utilities
 * @author Nev Stokes, <mail@nevstokes.com>
 */

namespace nevstokes\Utilities;

/**
 * Display formatted errors and exceptions
 * 
 * @package Utilities
 * @subpackage Errors
 */
class Trace
{
	private static $_errors = array();

	/**
	 * Handles the display of Exceptions
	 * 
	 * This method will generate a full page formatted exception including a
	 * syntax highlighted portion of the offending file and a per-file call
	 * backtrace ordered from first to last. Any previously buffered content
	 * will be discarded.
	 * 
	 * Delegate to this class by using the following in your code:
	 * 
	 * <code>
	 * set_exception_handler(array('nevstokes\Utilities\Trace', 'exceptions'));
	 * </code>
	 * 
	 * @param Exception $e
	 * 
	 * @link http://www.php.net/manual/en/function.set-exception-handler.php
	 */
	public function exceptions($e)
	{
		// Where the exception occurred
		$filename = $e->getFile();
		$linenumber = $e->getLine();

		// Get a syntax highlighted version of the offending file
		$code = highlight_file($filename, true);

		// Regex pattern to match a hex colour
		$hexcolour = '#[0-9a-f]{6}';

		// Strip the leading <code> tag and initial wrapping <span>
		$strip = array('/^<code><span style="color: ' . $hexcolour . '">/i', '/<\/span>\s*<\/code>$/');
		$code = preg_replace($strip, '', $code);

		// Split into lines
		$contents = explode('<br />', $code);

		// How many lines to include before the exception
		$extract = 8;

		// Where to start the extract
		$start = ($linenumber > $extract) ?
			$linenumber - $extract : 0;

		// How many lines to include after the exception
		$length = ($linenumber - $start) + 3;

		$stragglers = 0;
		$colour = '';

		// Process each line so that it is self-contained in <spans>
		foreach ($contents as $key => $val) {
			// Strip newlines
			$line = trim($val);

			if (!empty($line)) {
				// Is this the line containing the exception
				if ($key == ($linenumber - 1)) {
					// Strip all colouring <span> markup as this like will have it's own class
					$line = preg_replace(array('/<\/span>/', '/<span style="color: ' . $hexcolour . '">/i'), '', $line);
				} else {
					// If applicable, prepend the new line with the coloured <span> that was left open on the previous line
					$line = str_repeat('<span style="color: ' . $colour . '">', $stragglers) . $line;

					// Count the opening and closing <spans> of this line to see if any remain open
					$open = substr_count($line, '<span ');
					$closed = substr_count($line, '</span>');
					$stragglers = ($open - $closed);

					// An open <span> was found. Take a note of the colour to start the next line with
					if (
						   $stragglers
						&& preg_match_all('/<span style="color: (' . $hexcolour . ')">/i', $line, $matches)
					) {
						$colour = array_pop($matches[1]);
					}

					// Close the open <span>
					$line .= str_repeat('</span>', $stragglers);
				}

				// Update the line to the amended stand-alone coloured version
				$contents[$key] = $line;
			}
		}

		// Grab the pertinent part of the file
		$lines = (count($contents) > $extract) ?
			array_slice($contents, $start, $length) : $contents;

		// Font size as defined in CSS
		$fontsize = 16 * 0.8;

		// Length of maximum line number
		$chars = strlen($start + $length);

		// Calculate CSS values for left-hand gutter "margin" (actually a border)
		$border = ($fontsize - 1) * ($chars + 1);
		$padding = $border;
		$margin = $fontsize * $chars;

		if (ob_get_level()) {
			ob_end_clean();
		}

		echo '<!doctype html>
<html>
	<head>
		<title>sprocket | exception</title>
		<style type="text/css">
			html {
				font: 16px/1.2 Trebuchet MS, sans-serif;
			} 

			ol {
				background: #eee;
				border-left: ', $border, 'px solid #ccc;
				font-family: monospace;
				padding: 8px 12px 8px ', $padding, 'px;
			}

			ol, blockquote {
				font-size: 0.8em;
			}

			li {
				margin-left: -', $margin, 'px;
			}

			li span.highlight {
				background: #c00;
				color: #fff;
				font-weight: bold;
			}
		</style>
	</head>
	<body>
		<h3>Error in ', $filename, ', line ', $linenumber, ':</h3><h4>', $e->getMessage(), '</h4>';

		// Display extract, delegating line numbering to a HTML ordered list
		echo '<h3>Extract</h3><ol start="', ($start + 1), '">';
		foreach ($lines as $number => $line) {
			echo '<li>';

			// File contents array, zero-based
			echo (($number+$start+1) == $linenumber) ?
				'<span class="highlight">' . $line . '</span>' : $line;

			echo '</li>';
		}

		// Get the backtrace of the exception
		$lines = $e->getTrace();

		// Backtrace may be empty (i.e. exception was raised in initial file)
		if (!empty($lines)) {
			echo '</ol><h3>Backtrace</h3>';

			// List in ascending order
			$lines = array_reverse($lines);
			$line = array_shift($lines);

			do {
				// Indicate if we're in a blockquote
				$inblock = false;

				// Exception may not be in a file (i.e. an eval or somesuch)
				if (isset($line['file'])) {
					echo '<p><strong>', $line['file'], '</strong></p><blockquote>';
					$inblock = true;
				}

				// Array of calls for the current scope
				$calls = array();

				do {
					// Build the call trace
					$call = (isset($line['line'])) ? $line['line'] . ': ' : '';

					// Call may not be a class method
					if (isset($line['class'])) {
						$call .= $line['class'] . '::';
					}

					// Call may not be a function
					$call .= $line['function'];
					if (is_array($line['args'])) {
						$call .= '(' . implode(', ', $line['args']) . ')';
					}

					// Add the call to the array
					$calls[] = $call;

					// Take note of current scope
					if (isset($line['file'])) {
						$file = $line['file'];
					}

					// Continue while we have lines left in the trace and we don't change scope
				} while (($line = array_shift($lines)) && isset($line['file']) && ($file == $line['file']));

				// Display the calls for this scope
				echo '<tt>', implode('<br/>', $calls), '</tt>';

				// Close any open blockquote tag
				if ($inblock) {
					echo '</blockquote>';
				}

				// Loop until the trace has been exhasuted
			} while (!empty($line));
		}

	echo '
	</body>
</html>';

		// Bug out
		die();
	}

	/**
	 * Logs PHP errors
	 * 
	 * Does not immediately display raised errors. Instead, collates messages
	 * to be 
	 * 
	 * Delegate to this class by using the following in your code:
	 * 
	 * <code>
	 * set_error_handler(array('nevstokes\Utilities\Trace', 'errors') [, int $error_types] );
	 * </code>
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile (optional)
	 * @param int $errline (optional)
	 * @param array $errcontext (optional)
	 * 
	 * @link http://www.php.net/manual/en/function.set-error-handler.php
	 */
	public static function errors($errno, $errstr, $errfile, $errline, $errcontext)
	{
		// Check if error code is included in error_reporting
		if (!(error_reporting() & $errno)) {
			return;
		}

		// Give error numbers readable meanings
		switch ($errno) {
			case E_RECOVERABLE_ERROR:
				$errtype = 'FATAL';
				break;

			case E_USER_ERROR:
				$errtype = 'ERROR';
				break;

			case E_PARSE:
				$errtype = 'PARSE';
				break;

			case E_WARNING:
			case E_CORE_WARNING:
			case E_USER_WARNING:
			case E_COMPILE_WARNING:
				$errtype = 'WARNING';
				break;

			case E_STRICT:
				$errtype = 'STRICT';
				break;

			case E_NOTICE:
			case E_USER_NOTICE:
				$errtype = 'NOTICE';

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$errtype = 'DEPRECATED';
				break;

			default:
				$errtype = 'UNKNOWN';

			// Uncatchable: E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR
		}

		self::$_errors[] = array(
			'type'    => $errtype,
			'message' => $errstr,
			'file'    => $errfile,
			'line'    => $errline,
			'context' => $errcontext,
		);

		// Suppress native PHP error handling
		return true;
	}

	/**
	 * Returns an array of logged errors
	 * 
	 * @return array
	 */
	public static function getErrors()
	{
		$errors = self::$_errors;
		self::$_errors = array();

		return $errors;
	}
}
