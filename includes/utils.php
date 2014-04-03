<?php

/**
 * Utils class, misc stuff.
 */
class Utils
{
	/**
	 * Displays an error in red.
	 */
	public static function error($message)
	{
		echo "\033[31m-> " . $message . "\033[0m" . PHP_EOL;
	}

	/**
	 * Displays a success message in green.
	 */
	public static function success($message)
	{
		echo "\033[32m-> " . $message . "\033[0m" . PHP_EOL;
	}

	/**
	 * Displays a message.
	 */
	public static function message($message)
	{
		echo '-> ' . $message . PHP_EOL;
	}

	/**
	 * Displays an info message in blue if verbose mode enabled.
	 */
	public static function info($message)
	{
		global $verbose;

		if ($verbose)
			echo "\033[94m-> " . $message . "\033[0m" . PHP_EOL;
	}

	/**
	 * Asks a question to the user.
	 *
	 * @return true or false if the user responded yes or no.
	 */
	public static function ask($prompt, $default = '')
	{
		$response = '';
		$suffix = '[y/n]';

		if ($default == 'y')
			$suffix = '[Y/n]';
		else if ($default == 'n')
			$suffix = '[y/N]';
		while (empty($response))
		{
			echo '-> ' . $prompt . ' ' . $suffix . ' ';
			$response = strtolower(trim(fgets(STDIN)));
			if (($response == 'y' || $response == 'yes')
				|| ($default == 'y' && empty($response)))
				return true;
			else if (($response == 'n' || $response == 'no')
				|| ($default == 'n' && empty($response)))
				return false;
			else
				self::info('This is not a correct answer.');
			$response = '';
		}
	}

	/**
	 * Parses the command line parameters.
	 */
	public static function parse_options()
	{
		global $verbose;
		$options = getopt('v', ['verbose']);

		if (array_key_exists('v', $options)
			|| array_key_exists('verbose', $options))
		{
			$verbose = true;
			self::info("Verbose mode activated.");
		}
	}

	/**
	 * Displays a menu.
	 *
	 * @param string  $header   Header displayed before the menu.
	 * @param array   $entries  Menu entries.
	 */
	public static function display_menu($header, $entries)
	{
		self::message($header);
		echo '-----------------------------------------------------' . PHP_EOL;
		foreach ($entries as $key => $value)
		{
			echo '   ';
			echo $key + 1 . ' - ' . $value . PHP_EOL;
		}
		echo '-----------------------------------------------------' . PHP_EOL;
	}

	/**
	 * Displays a menu and a prompt to choose an option.
	 *
	 * @param string  $header   Header displayed before the menu.
	 * @param array   $entries  Menu entries.
	 * @param string  $prompt   Prompt displayed before user entry asked.
	 * @return Chosen entry text.
	 */
	public static function menu($header, $entries, $prompt)
	{
		$choice = 0;

		while (42)
		{
			echo PHP_EOL;
			self::display_menu($header, $entries);
			echo '-> ' . $prompt . ' : ';
			$choice = trim(fgets(STDIN));
			if ($choice == 'clear')
				system('clear');
			else
			{
				$choice = (intval($choice) - 1);
				if ($choice < 0 || !array_key_exists($choice, $entries))
					self::error('Invalid options.');
				else
				{
					echo PHP_EOL;
					return ($choice);
				}
			}
		}
	}
}

?>
