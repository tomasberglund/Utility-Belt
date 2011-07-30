<?php

/**
 * The class in this file contains helpers to assist in troubleshooting
 * 
 * It's generally a good idea to not output anything other than content in a
 * production environment so a DEBUG flag is set at the top of this file that
 * exists as a convienient way to stop displaying trace dumps. In practise,
 * this flag should be defined in a config file where error_reporting levels
 * can also be set depending on the environment.
 * 
 * @package Utilities
 * @author Nev Stokes <mail@nevstokes.com>
 */

namespace nevstokes\Utilities;

// Debugging flag
define('DEBUG', true);

/**
 * Methods to assist in debugging
 * 
 * @package Utilities
 */
class Debug
{

	/**
	 * Output information about a variable
	 * 
	 * Context-sensitive replacement for the native var_dump() function.
	 * If the xdebug extension is installed or if text/plain HTTP content
	 * headers have been sent then var_dump() is used as is. Otherwise the
	 * var_dump() call is wrapped with <pre> containter tags.
	 * 
	 * @param mixed $var
	 */
	public static function dump($var)
	{
		// Check if it's actually okay to output anything
		if (true === DEBUG) {
			$text = new TextProcessor();

			// Don't want to shoot our bolt too soon
			ob_start();

			if (extension_loaded('xdebug')) {
				// Bliss, nothing extra for us to do
				var_dump($var);
			} elseif (headers_sent()) {
				$http = headers_list();

				// Try to discover what kind of context we're in
				foreach ($http as $index => $header) {
					list($key, $val) = explode(': ', $header);

					if ('Content-type' == $key) {
						if ('text/plain' == substr($val, 0, 10)) {
							var_dump($var);
						} elseif ('text/html' == substr($val, 0, 9)) {
							echo $text->wrapWithTag($var, 'pre');
						}

						break;
					}
				}
			}

			// Gather what output we may already have
			$info = ob_get_clean();

			if (false === empty($info)) {
				// We have output already
				echo $info;
			} else if ('text/html' == ini_get('default_mimetype')) {
				// If the default mimetype is HTML
				echo $text->wrapWithTag($var, 'pre');
			} else {
				// Tried everything else, resort to simple var_dump
				var_dump($var);
			}
		}
	}

}

?>