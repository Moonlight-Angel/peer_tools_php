<?php

/**
 * Cleaning class, to clean folders.
 */
class Cleaning
{
  /**
   * Fires make fclean in the current directory and its subdirectories.
   */
  public static function fclean_corrections()
  {
    Utils::message('This option does "make fclean" in each subfolder of the folder you\'re in.');
    Utils::message('It cleans your corrections and reduces the corrections folder weight.');
    Utils::message('Make sure you are in your corrections folder for this option to work.');
    echo PHP_EOL;
    $pwd = exec('pwd');
    $home = exec('echo $HOME');
    $pwd = str_replace($home, '~', $pwd);
    Utils::message(sprintf('Your are currently in \'%s\'.', $pwd));
    $response = Utils::ask('Is this your corrections folder ?', 'y');
    if ($response)
    {
      Utils::message("Cleaning...");
      exec('find . ! -path \'*/\.*\' -type d -exec make -C "{}" fclean \; > /dev/null 2>&1', $output, $return);
      if ($return > '0')
        Utils::error('Error while cleaning.');
      else
        Utils::success('Successfully cleaned the folder.');
    }
    else
      Utils::error('Operation canceled.');
  }

  /**
   * Removes .git folder in the current directory and its subdirectories.
   */
  public static function remove_git_corrections()
  {
    Utils::message('This option removes .git folder in each subfolder of the folder you\'re in.');
    Utils::message('Make sure you are in your corrections folder for this option to work.');
    echo PHP_EOL;
    $pwd = exec('pwd');
    $home = exec('echo $HOME');
    $pwd = str_replace($home, '~', $pwd);
    Utils::message(sprintf('Your are currently in \'%s\'.', $pwd));
    $response = Utils::ask('Is this your corrections folder ?', 'y');
    if ($response)
    {
      Utils::message("Removing .git folders...");
      exec('find . -type d -name ".git" -exec rm -rf "{}" \; > /dev/null 2>&1', $output, $return);
      if ($return > '0')
        Utils::error('Error while removing .git folders.');
      else
        Utils::success('Successfully removed .git folders.');
    }
    else
      Utils::error('Operation canceled.');
  }
}

?>
