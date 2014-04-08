<?php

/**
 * Cloning class, to clone corrections.
 */
class Cloning
{
	private $credentials;
	private $corrections_matches;
	private $correctors_matches;

	public function __construct($credentials_instance)
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
		if ($this->parse_corrections($content))
		{
			$this->epur_corrections();
			$choice = $this->display_corrections_menu();
			if ($choice == -1)
			{
				Utils::error('Operation canceled.');
				return ;
			}
			$this->do_clone($choice);
		}
	}

	/**
	 * Parses the source code to fetch corrections.
	 *
	 * @param  string  $content  The page source.
	 * @return true and false if the parsing has succeeded or failed.
	 */
	private function parse_corrections($content)
	{
		if (preg_match_all(CORRECTIONS_HEAD_REGEX, $content, $matches))
		{
			array_shift($matches);
			$this->corrections_matches['projects'] = $matches[0];
			$this->corrections_matches['enddates'] = $matches[1];
			$this->corrections_matches['corrections'] = $matches[2];
			$this->corrections_matches['stats']['count'] = 0;
			$this->corrections_matches['stats']['done'] = 0;
			$lines = explode(PHP_EOL, $this->corrections_matches['corrections'][0]);
			foreach ($lines as $value) {
				if (strpos($value, 'note'))
				{
					if (strpos($value, 'avez donné la note'))
						$this->corrections_matches['stats']['done']++;
					$this->corrections_matches['stats']['count']++;
				}
			}
			foreach ($this->corrections_matches['corrections'] as $key => $value) {
				if (!preg_match_all(CORRECTIONS_PEOPLE_REGEX, $value, $matches))
				{
					Utils::error('Error while trying to parse corrections.');
					return false;
				}
				array_shift($matches);
				$this->corrections_matches['corrections'][$key] = array();
				$this->corrections_matches['corrections'][$key]['urls'] = $matches[0];
				$this->corrections_matches['corrections'][$key]['uids'] = $matches[1];
			}
			return true;
		}
		else
		{
			Utils::error('No peer corrections available.');
			return false;
		}
	}

	/**
	 * Cleans the whole corrections array from unneeded text.
	 */
	private function epur_corrections()
	{
		$projects = &$this->corrections_matches['projects'];
		$corrections = &$this->corrections_matches['corrections'];
		foreach ($projects as $project_id => $project_name) {
			foreach ($corrections[$project_id]['uids'] as $correction_id => $correction_name) {
				$actual_uid = $corrections[$project_id]['uids'][$correction_id];
				$corrections[$project_id]['uids'][$correction_id] = str_replace($project_name . ' ', '', $actual_uid);
				$corrections[$project_id]['urls'][$correction_id] = INTRA_URL . $corrections[$project_id]['urls'][$correction_id];
			}
			$projects[$project_id] = str_replace('Sujet ', '', $project_name);
		}
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
				$this->corrections_matches['stats']['done'],
				$this->corrections_matches['stats']['count']);
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
				return true;
			Utils::message(sprintf('No read access on repository, trying again (%d/10)...', $i + 1));
			sleep(1);
		}
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
			return str_replace('\\', '', $matches[1]);
		return false;
	}
}

?>
