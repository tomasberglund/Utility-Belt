<?php

/**
 * This file contains a class to facilitate common textual operations such as
 * converting strings to a URL safe format and generating excerpts.
 * 
 * @author Nev Stokes <mail@nevstokes.com>
 * @package Utilities
 */

namespace nevstokes\Utilities;

/**
 * Class of various text/string-related methods
 * 
 * @package Utilities
 */
class TextProcessor
{

	/**
	 * Process a string to contain purely basic latin characters
	 * 
	 * This method processes text to remove diacritic marks and translate
	 * ligatures into individual characters.
	 * 
	 * Requires the PHP intl extension and ICU.
	 * 
	 * @param string $original The string to process
	 * @return string
	 * 
	 * @link http://ahinea.com/en/tech/accented-translate.html
	 */
	public function normalise($original)
	{
		// Check to make sure the extension is available
		if (false === class_exists('Normalizer', false)) {
			return $original;
		}

		// map European characters onto two characters before removing diacritics
		$doubles = array(
			'@\x{00c4}@u' => 'AE', // Ä
			'@\x{00d6}@u' => 'OE', // Ö
			'@\x{00dc}@u' => 'UE', // Ü
			'@\x{00e4}@u' => 'ae', // ä
			'@\x{00f6}@u' => 'oe', // ö
			'@\x{00fc}@u' => 'ue', // ü
			'@\x{00f1}@u' => 'ny', // ñ
			'@\x{00ff}@u' => 'yu', // ÿ
		);

		$string = preg_replace(array_keys($doubles), array_values($doubles), $original);

		// map characters with diacritics on their base-character followed by the diacritical mark
		$string = \Normalizer::normalize($string, \Normalizer::FORM_D);

		$pairs = array(
			'@\pM@u'		=> '',   // removes diacritics
			'@\x{00c6}@u'   => 'AE', // Æ
			'@\x{00e6}@u'   => 'ae', // æ
			'@\x{00df}@u'   => 'ss', // ß
			'@\x{0132}@u'   => 'IJ', // Ĳ
			'@\x{0133}@u'   => 'ij', // ĳ
			'@\x{0152}@u'   => 'OE', // Œ
			'@\x{0153}@u'   => 'oe', // œ
			'@\x{00d0}@u'   => 'D',  // Ð
			'@\x{0110}@u'   => 'D',  // Ð
			'@\x{0111}@u'   => 'd',  // đ
			'@\x{00f0}@u'   => 'd',  // ð
			'@\x{0126}@u'   => 'H',  // Ħ
			'@\x{0127}@u'   => 'h',  // ħ
			'@\x{0131}@u'   => 'i',  // ı
			'@\x{0138}@u'   => 'k',  // ĸ
			'@\x{013f}@u'   => 'L',  // Ŀ
			'@\x{0140}@u'   => 'l',  // ŀ
			'@\x{0141}@u'   => 'L',  // Ł
			'@\x{0142}@u'   => 'l',  // ł
			'@\x{0149}@u'   => 'n',  // ŉ
			'@\x{014a}@u'   => 'N',  // Ŋ
			'@\x{014b}@u'   => 'n',  // ŋ
			'@\x{00d8}@u'   => 'O',  // Ø
			'@\x{00f8}@u'   => 'o',  // ø
			'@\x{017f}@u'   => 's',  // ſ
			'@\x{00de}@u'   => 'T',  // Þ
			'@\x{0166}@u'   => 'T',  // T
			'@\x{00fe}@u'   => 't',  // þ
			'@\x{0167}@u'   => 't',  // t
			'@[^\0-\x80]@u' => '',   // remove non-ASCii
		);

		$string = preg_replace(array_keys($pairs), array_values($pairs), $string);

		// Allow for possible errors in UTF8-regular-expressions
		return (empty($string)) ? $original : $string;
	}

	/**
	 * Convert a string to URL friendly format
	 * 
	 * After converting to lowercase and normalisation, any non-alphanumeric characters
	 * are stripped and spaces are converted to hyphens.
	 * 
	 * @param string $string The string to turn into a URL
	 * @return string
	 */
	public function urlify($string)
	{
		// Make sure we're dealing with a string
		if (is_string($string)) {
			$find = array('/[^a-z0-9 ]/', '/ /');
			$replace = array('', '-');

			// Normalise the string
			$string = strtolower($string);
			$string = $this->normalise($string);

			// Replace any unwanted characters
			$string = preg_replace($find, $replace, $string);

			return $string;
		}
	}

	/**
	 * Generates a URL path from given text
	 * 
	 * When a string is passed as the first argument it is simply delegated
	 * to the urlify method. If an array is supplied then each item is
	 * urlified and the array is joined with a slash. Either way a trailing
	 * slash is appended.
	 * 
	 * A callback function can be supplied that the generated url will be
	 * passed to before it is returned. This can be used, for example, to check
	 * for uniqueness in a collection.
	 * 
	 * @param mixed $path What to create the URL from
	 * @param callback $function (optional) A post-creation function
	 * @return string
	 * @uses TextProcessor::urlify
	 */
	public function constructURL($path, $function=null)
	{
		// Check what we have to build a URL from
		if (is_array($path)) {
			// Apply the urlify method to each item in the array
			$path = array_map(array($this, 'urlify'), $path);

			// Create the URL
			$url = implode('/', $path) . '/';
		} else {
			// Call the urlify method
			$url = $this->urlify($path) . '/';
		}

		// Apply the callback if defined
		if (is_callable($function)) {
			$url = call_user_func($function, $url);
		}

		return $url;
	}

	/**
	 * Wraps a piece of text with a HTML container tag pair
	 * 
	 * Dumb wrapper. Simply returns the supplied string prepended with <tag>
	 * and appended with </tag>. No check is made as to the validity of the
	 * tag supplied.
	 * 
	 * @param string $string The string to wrap
	 * @param string $tag The tag to wrap around the string
	 * @param string $class (optional) A class to give the opening tag
	 * @return string
	 */
	public function wrapWithTag($string, $tag, $class=null)
	{
		// Sanitise the tag
		$tag = preg_replace('/[^a-z1-6]/i', '', $tag);

		if (empty($class)) {
			// Create the opening tag
			$open = '<' . $tag . '>';
		} else {
			// Sanitise the class
			$class = preg_replace('/[^a-z0-9-_]/i', '', $class);

			// Append the given class to the opening tag
			$open = '<' . $tag . ' class="' . $class . '">';
		}

		// Return the tag wrapped with the tag pair
		return $open . $string . '</' . $tag . '>';
	}

	/**
	 * Generates a HTML list element from an array of strings
	 * 
	 * Each item in the supplied array will be wrapped in <li> tags and then
	 * the whole lot wrapped with the desired HTML list type. The first item
	 * in the list is given a courtesy class to identify it as such.
	 * 
	 * @param array $items The items to include in the list
	 * @param bool $ordered (optional, default false) Should the list be an
	 * ordered list (ol) or default to an unordered list (ul)
	 * @return string
	 * @uses TextProcessor::wrapWithTag
	 */
	public function arrayToList($items, $ordered=false)
	{
		// Check we actually have an array
		if (is_array($items)) {
			$list = '<li class="first">' . implode('</li><li>', $items) . '</li>';
			$tag = ($ordered) ? '<ol>' : '<ul>';

			// Wrap the list items with appropriate tags
			return $this->wrapWithTag($list, $tag);
		}
	}

	/**
	 * Generate an excerpt of n words from given text
	 * 
	 * Any HTML will be stripped before the text is split around spaces. If
	 * the resulting number of words is fewer than the excerpt length
	 * requested then the initial string is returned.
	 * 
	 * @param string $string The full text to create an excerpt from
	 * @param int $count The number of words to include in the excerpt
	 * @param bool $ellipsis (optional, default false) Whether to append
	 * ellipsis to the excerpt
	 * @return string
	 */
	public function excerpt($string, $count, $ellipsis=false)
	{
		// Strip any HTML
		$plaintext = strip_tags($string);

		// Split the string around spaces
		$words = array_filter(explode(' ', $plaintext));

		// Check the number of words in the initial string
		if ($count >= count($words)) {
			// Initial string not long enough to generate excerpt
			return $string;
		}

		// Reassemble the words to form the excerpt
		$excerpt = implode(' ', array_slice($words, 0, $count));

		// Append ellipsis if requested
		return ($ellipsis) ? $excerpt . ' . . .' : $excerpt;
	}

	/**
	 * Generate an excerpt of n words from given HTML
	 * 
	 * Provides the same functionality as excerpt but allows for the inclusion
	 * of HTML in the string. The resulting excerpt is passed to tidyHTML to
	 * ensure that any tags that may be left open will be closed.
	 * 
	 * @param string $string The full text to create an excerpt from
	 * @param int $count The number of words to include in the excerpt
	 * @param bool $ellipsis (optional, default false) Whether to append
	 * ellipsis to the excerpt
	 * @return string
	 * @uses TextProcessor::excerpt
	 * @uses TextProcessor::tidyHTML
	 */
	public function excerptHTML($string, $count, $ellipsis=false)
	{
		// Create a plain-text excerpt
		$excerpt = $this->excerpt($string, $count);

		$counter = 0;
		$output = '';

		// Scan over the excerpt and re-introduce any HTML tags
		for ($q = 0, $r = strlen($excerpt); $q < $r; $q++) {
			if ($excerpt{$q} != $string{$counter}) {
				do {
					$output .= $string{$counter};
				} while (($counter < strlen($string)) && ($string{$counter++} != '>'));
			}

			$output .= $excerpt{$q};
			$counter++;
		}

		// Make sure the excerpt is still valid HTML
		$excerpt = $this->tidyHTML($output);

		// Append ellipsis if requested
		return ($elipses) ? $excerpt . ' . . .' : $excerpt;
	}

	/**
	 * Ensures a string is well-formed HTML
	 * 
	 * By way of loading into DOMDocument, importing into SimpleXML and then
	 * processing after exporting to XML, this method will fix badly and
	 * incorrectly nested HTML markup.
	 * 
	 * @param string $html The HTML to check
	 * @return string
	 */
	public function tidyHTML($html)
	{
		// Take a note of current error reporting level
		$err = error_reporting();

		// Disable error reporting temporarily
		error_reporting(0);

		// Load HTML into new DOM
		$doc = new \DOMDocument();
		$doc->encoding = 'UTF-8';
		$doc->loadHTML($html);

		// Import into SimpleXML to fix any errors in the HTML
		$valid = simplexml_import_dom($doc)->asXML();

		// Restore initial error reporting level
		error_reporting($err);

		do {
			// Remove unwanted wrapping tags introduced by SimpleXML
			$valid = preg_replace('/^<([^>]+?)>(.+?)<\/\1>$/', '$2', $valid, 1, $count);
			if (preg_match('/^(<[^>]+?>)/', $valid, $matches)) {
				// Check we're not stripping a valid tag set from the initial string 
				if ($matches[1] == substr($html, 0, strlen($matches[1]))) {
					break;
				}
			}
		} while($count > 0);

		// The initial text was actually okay
		if ($valid != $html) {
			// Find the starting position of the initial text
			$start = strpos($valid, substr($html, 0, 1));

			// Check if the initial text starts the corrected string
			if ($start > 0) {
				// Get rid of any extra tags introduced by SimpleXML
				$valid = substr($valid, $start);

				// Remove any redundant closing tags
				$valid = preg_replace('/^([^<]+?)<\/[^>]+?>/', '$1', $valid);
			}
		}

		return $valid;
	}

}

?>