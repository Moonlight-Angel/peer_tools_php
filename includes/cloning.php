<?php

/**
 * Cloning class, to clone corrections.
 */
class Cloning
{
	private $credentials;
	private $corrections_matches;
	private $correctors_matches;

	public function __construct(Credentials $credentials_instance)
	{
		$this->credentials = $credentials_instance;
		$this->corrections_matches = '';
	}

	/**
	 * Clones the remaining corrections.
	 */
	public function clone_corrections()
	{
		$content = '';
		$ch = Auth::login_intra($this->credentials->get_username(), $this->credentials->get_password(), $content);
		if ($ch === false)
			return ;
		$this->corrections_matches = Parsing::parse_corrections($content);
		if ($this->corrections_matches !== false)
		{
			$choice = $this->display_corrections_menu();
			if ($choice == -1)
				Utils::error('Operation canceled.');
			else
				$this->do_clone($choice);
		}
		Auth::close_connection($ch);
	}

	/**
	 * Displays the corrections list.
	 *
	 * @return The selected correction index (beginning at 0).
	 */
	private function display_corrections_menu()
	{
		$choices = $this->corrections_matches['projects'];
		foreach ($choices as $key => $value) {
			$choices[$key] = sprintf(
				'%s (%s) (%d/%d)',
				$choices[$key],
				$this->corrections_matches['enddates'][$key],
				$this->corrections_matches['stats'][$key]['done'],
				$this->corrections_matches['stats'][$key]['count']);
		}
		$choices[] = "Return to main menu";
		$choice = Utils::menu('Select the project you want to clone', $choices, 'Please enter your choice');
		if ($choice == (count($choices) - 1))
			return -1;
		return $choice;
	}

	/**
	 * Begins the process of cloning.
	 */
	private function do_clone($index)
	{
		$corrections_list = $this->corrections_matches['corrections'][$index];
		Utils::message(sprintf('Cloning project %s...', $this->corrections_matches['projects'][$index]));
		$subdirectory = $this->ask_subdirectory($index);
		if ($subdirectory === false)
			return ;
		foreach ($corrections_list['uids'] as $key => $value) {
			echo PHP_EOL;
			Utils::message(sprintf("Cloning %s's repository...", $value));
			if (file_exists($subdirectory . $value))
			{
				$response = Utils::ask(sprintf('The repository \'%s\' is already cloned. Would you like to re-clone it ?', $value), 'y');
				if ($response)
				{
					exec('rm -rf ./' . $subdirectory . $value, $output, $return);
					if ($return > '0')
					{
						Utils::error('Error while removing directory. Switching to next.');
						continue ;
					}
				}
				else
				{
					Utils::message('Nothing to clone, switching to next.');
					continue ;
				}
			}
			if (!$this->check_repository_open($index, $key))
			{
				Utils::error('No read access on repository after 10 tries. Switching to next.');
				continue ;
			}
			Utils::success('We got read access on repository. Fetching vogsphere url...');
			$vogsphere = $this->get_vogsphere_url($index, $key);
			if ($vogsphere === false)
			{
				Utils::error('Unable to fetch vogsphere url. Switching to next.');
				continue ;
			}
			Utils::success('Got vogsphere url, cloning...');
			exec('git clone -q ' . $vogsphere . ' ./' . $subdirectory . $value . ' 2>&1', $output, $return);
			if ($return == '0')
				Utils::success('Successfully cloned.');
			else
				Utils::error('Error while cloning.');
		}
	}

	/**
	 * Asks the user if he would like to clone in subdirectory.
	 *
	 * @param  int  $index The correction index in projects array.
	 * @return The folder name with finishing slash (empty if no folder) or false if an error occured.
	 */
	private function ask_subdirectory($index)
	{
		$name = strtolower(preg_replace('/[^A-Za-z0-9]/', '_', $this->corrections_matches['projects'][$index]));
		$name = preg_replace('/_{2,}/', '_', $name);
		$response = Utils::ask('Would you like to create a subdirectory for the repositories ?', 'y');
		if ($response)
		{
			echo '-> Enter the name of the subdirectory (default : \'' . $name . '\') : ';
			$new_name = trim(fgets(STDIN));
			if (!empty($new_name))
				$name = $new_name;
			if (file_exists($name))
			{
				$response = Utils::ask(sprintf('The subdirectory \'%s\' already exists. Would you like to delete it ?', $name), 'y');
				if ($response)
				{
					exec('rm -rf ' . $name, $output, $return);
					if ($return == '0')
						Utils::success(sprintf('Subdirectory \'%s\' successfully deleted.', $name));
					else
					{
						Utils::error('Error while deleting subdirectory.');
						return false;
					}
				}
				else
					return $name . '/';
			}
			exec('mkdir ' . $name, $output, $return);
			if ($return == '0')
			{
				Utils::success(sprintf('Subdirectory \'%s\' successfully created.', $name));
				return $name . '/';
			}
			else
			{
				Utils::error('Error while creating subdirectory.');
				return false;
			}
		}
		return '';
	}

	/**
	 * Checks if the repository is open for the passed correction index and uid.
	 *
	 * @param  int  $index The correction index in projects array.
	 * @param  int  $uid   The uid index in the corrections array.
	 * @return true or false if the repository is open or not.
	 */
	private function check_repository_open($index, $uid)
	{
		$ch = Auth::prepare($this->corrections_matches['corrections'][$index]['urls'][$uid] . '/repository?format=json');
		for($i = 0; $i < 10; $i++)
		{
			$content = curl_exec($ch);
			if (strpos($content, 'success') !== false)
			{
				Auth::close_connection($ch);
				return true;
			}
			Utils::message(sprintf('No read access on repository, trying again (%d/10)...', $i + 1));
			sleep(1);
		}
		Auth::close_connection($ch);
		return false;
	}

	/**
	 * Fetches the vogsphere url from the source code for the passed correction index and uid.
	 *
	 * @param  int  $index The correction index in projects array.
	 * @param  int  $uid   The uid index in the corrections array.
	 * @return The vogsphere url or false if the url has not been found.
	 */
	private function get_vogsphere_url($index, $uid)
	{
		$ch = Auth::prepare($this->corrections_matches['corrections'][$index]['urls'][$uid]);
		$content = curl_exec($ch);
		if (preg_match(VOGSPHERE_REGEX, $content, $matches))
		{
			Auth::close_connection($ch);
			return str_replace('\\', '', $matches[1]);
		}
		Auth::close_connection($ch);
		return false;
	}
}

?>
