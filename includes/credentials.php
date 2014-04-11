<?php

/**
 * Credentials class, for asking and dealing with credentials.
 */
class Credentials
{
	private $configuration;
	private $username;
	private $password;

	public function __construct(Configuration $configuration_instance)
	{
		$this->configuration = $configuration_instance;
		$this->username = $this->configuration->get_username();
		$this->password = $this->configuration->get_password();
	}

	/**
	 * Initializes credentials.
	 *
	 * @return Configuration array or false if an error occured.
	 */
	public function init()
	{
		if ($this->has_credentials())
			Utils::info('Credentials available.');
		else
		{
			Utils::info('No credentials available, asking...');
			$this->ask_credentials();
			Utils::info('Checking if asked for storage...');
			if ($this->configuration->get_ask_storage())
			{
				Utils::info('Asked for storage configured, asking...');
				$this->ask_storage();
			}
		}
	}

	/**
	 * Says if there are credentials loaded.
	 *
	 * @return true or false if credentials are loaded or not.
	 */
	private function has_credentials()
	{
		return (!empty($this->username) && !empty($this->password));
	}

	/**
	 * Asks credentials to the user.
	 */
	private function ask_credentials()
	{
		$username = '';
		$password = '';

		Utils::message('It appers this is the first time you are using this tool.');
		Utils::message('Please enter your credentials.');
		while (empty($username))
		{
			echo "-> Enter your username : ";
			$username = trim(fgets(STDIN));
			if (empty($username))
				Utils::error('Username cannot be blank.');
		}
		while (empty($password))
		{
			echo "-> Enter your password : ";
			system('stty -echo');
			$password = trim(fgets(STDIN));
			system('stty echo');
			if (empty($password))
				Utils::error(PHP_EOL . 'Password cannot be blank.');
		}
		echo PHP_EOL;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Asks to store the credentials to the user.
	 */
	private function ask_storage()
	{
		if (Utils::ask('Would you like to store the credentials in your configuration file for further use ?', 'y'))
		{
			$this->configuration->set_username($this->username);
			$this->configuration->set_password($this->password);
		}
		else
		{
			if (!Utils::ask('Should I ask you this question every time ?', 'n'))
				$this->configuration->set_ask_storage(false);
		}
		$this->configuration->save_configuration();
		Utils::success('Credentials successfully saved in configuration.');
	}

	/**
	 * Deletes the credentials.
	 */
	public function delete_credentials()
	{
		Utils::info('Deleting credentials.');
		$this->configuration->set_username('');
		$this->configuration->set_password('');
		$this->configuration->set_ask_storage(true);
		$this->configuration->save_configuration();
		Utils::success('Credentials successfully deleted from configuration file.');
	}

	/**
	 * Getters & Setters
	 */

	public function get_username()
	{
		return ($this->username);
	}

	public function get_password()
	{
		return ($this->password);
	}
}

?>
