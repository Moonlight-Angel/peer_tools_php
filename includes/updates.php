<?php

/**
 * Updates class, to handle Peer Tools update via GitHub.
 */
class Updates
{
	/**
	 * Checks and installs updates from GitHub.
	 *
	 * @param  boolean  $silent  If set to true, status messages will be displayed.
	 * @return true or false if updates are available and were correctly installed or not.
	 */
	public static function check_updates($silent = false)
	{
		if (!$silent)
			Utils::message('Fetching the latest changes from the git...');
		exec('cd ' . APP_PATH . ' && git fetch -q origin', $output, $return);
		if ($return > '0')
			Utils::error('Unable to fetch latest changes.');
		else
		{
			if (!$silent)
				Utils::message('Checking if there is some update to install...');
			$output = array();
			exec('cd ' . APP_PATH . ' && git cherry master origin/master', $output, $return);
			if ($return > '0')
				Utils::error('Unable to git cherry.');
			else
			{
				if (count($output) > 0)
				{
					Utils::success('Updates are available !');
					$output = array();
					exec('cd ' . APP_PATH . ' && git show master..origin/master -n 1 -s --format=%B', $output, $return);
					if ($return > 0)
						Utils::error('Unable to fetch latest commit message.');
					else
					{
						Utils::message(sprintf('Latest update : %s.', $output[0]) . PHP_EOL);
						$response = Utils::ask('Would you like to install the update ?', 'y');
						if (!$response)
							Utils::error('Aborting update.');
						else
						{
							Utils::message('Installing...');
							exec('cd ' . APP_PATH . ' && git pull -q', $output, $return);
							if ($return > '0')
								Utils::error('Unable to git pull.');
							else
							{
								Utils::success('Update done. Restart Peer Tools and enjoy !');
								return true;
							}
						}
					}
				}
				else
				{
					if (!$silent)
						Utils::message('You are up to date.');
				}
			}
		}
		return false;
	}
}

?>