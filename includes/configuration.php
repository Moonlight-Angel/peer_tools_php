<?php

/**
 * Configuration class, loads and saves configuration to file in JSON format.
 */
class Configuration
{
	private $configuration;

	public function __construct()
	{
		$this->configuration = array(
			'username'				=> '',
			'password'				=> '',
			'ask_storage'			=> true
		);
	}

	/**
	 * Initializes configuration.
	 */
	public function init()
	{
		Utils::info('Loading configuration...');
		if ($this->has_configuration() && $this->get_configuration())
			Utils::info('Configuration loaded successfully.');
		else
			Utils::info('No configuration file found. Using default.');
	}

	/**
	 * Loads configuration from file.
	 *
	 * @return Configuration array or false if an error occured.
	 */
	public function get_configuration()
	{
		if (!$this->has_configuration())
			return false;
		$content = file_get_contents(CONFIGURATION_FILE);
		if (!$content)
			return false;
		Utils::info('Successfully readed from file.');
		$data = json_decode($content, true);
		if (!$data)
			return false;
		Utils::info('Successfully decoded data.');
		$this->configuration = array_merge($this->configuration, $data);
		$this->configuration['password'] = str_rot13($this->configuration['password']);
		return $this->configuration;
	}

	/**
	 * Saves configuration to file.
	 *
	 * @return true if all went fine or false if an error occured.
	 */
	public function save_configuration()
	{
		if (!is_dir(CONFIGURATION_PATH))
		{
			Utils::info('No configuration folder, creating it...');
			if (!mkdir(CONFIGURATION_PATH, 0700))
				return false;
			Utils::info('Successfully created configuration folder.');
		}
		$this->configuration['password'] = str_rot13($this->configuration['password']);
		$content = json_encode($this->configuration, JSON_PRETTY_PRINT);
		$this->configuration['password'] = str_rot13($this->configuration['password']);
		if (!$content)
			return false;
		Utils::info('Successfully encoded data.');
		if (!file_put_contents(CONFIGURATION_FILE, $content))
			return false;
		Utils::info('Successfully written data to file.');
		return chmod(CONFIGURATION_FILE, 0600);
	}

	/**
	 * Says if any configuration file exists.
	 *
	 * @return true or false if any configuration file exists or not.
	 */
	public function has_configuration()
	{
		return file_exists(CONFIGURATION_FILE);
	}

	/**
	 * Deletes the configuration.
	 */
	public function delete_configuration()
	{
		if (!$this->has_configuration())
		{
			Utils::info('No configuration file found.');
			return ;
		}
		Utils::info('Deleting configuration file.');
		if (unlink(CONFIGURATION_FILE))
		{
			Utils::success('Configuration file successfully deleted.');
			$GLOBALS['app']->quit(0);
		}
		else
			Utils::error('Error while trying to delete configuration file, try again.');
	}

	/**
	 * Getters & Setters
	 */

	public function get_username()
	{
		return $this->configuration['username'];
	}

	public function set_username($username)
	{
		$this->configuration['username'] = $username;
	}

	public function get_password()
	{
		return $this->configuration['password'];
	}

	public function set_password($password)
	{
		$this->configuration['password'] = $password;
	}

	public function get_ask_storage()
	{
		return $this->configuration['ask_storage'];
	}

	public function set_ask_storage($ask_storage)
	{
		$this->configuration['ask_storage'] = $ask_storage;
	}
}

?>
