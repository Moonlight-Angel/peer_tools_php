<?php

/**
 * Auth class, to handle authentification to intra and dashboard.
 */
class Auth
{
  /**
   * Logs the user to the intranet.
   *
   * @param  string  $login     User's login.
   * @param  string  $password  User's password.
   * @param  string  &$content  Where to store the last fetched content.
   * @return curl handler or false if the user is connected or not.
   */
  public static function login_intra($login, $password, &$content = NULL)
  {
    $data = array(
      'login'   => $login,
      'password'  => $password,
      'remind'  => 'on'
    );
    Utils::message('Connecting to intra...');
    $ch = curl_init(INTRA_URL);
    curl_setopt($ch, CURLOPT_POST, count($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_JAR);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_JAR);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $content = curl_exec($ch);
    if (strstr($content, 'Bienvenue'))
    {
      Utils::success('Connected to intra.');
      return $ch;
    }
    else if (strstr($content, 'sont invalides'))
      Utils::error('Error while connecting to intra : wrong credentials.');
    else if (strstr($content, 'in progress'))
      Utils::error('Error while connecting to intra : deployment in progress.');
    else
      Utils::error('Error while connecting to intra : unknown error.');
    $content = '';
    return false;
  }

  /**
   * Logs the user to the dashboard.
   *
   * @param  string  $login     User's login.
   * @param  string  $password  User's password.
   * @param  string  &$content  Where to store the last fetched content.
   * @return curl handler or false if the user is connected or not.
   */
  public static function login_dashboard($login, $password, &$content = NULL)
  {
    $token = '';

    Utils::message('Connecting to dashboard...');
    $ch = curl_init(DASHBOARD_LOGIN_URL);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_JAR);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_JAR);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $content = curl_exec($ch);
    if (preg_match(CSRF_TOKEN_REGEX, $content, $matches))
      $token = $matches[1];
    else
    {
      Utils::error('Unable to find login token.');
      return false;
    }
    $data = array(
      'csrfmiddlewaretoken' => $token,
      'username'        => $login,
      'password'        => $password
    );
    curl_setopt($ch, CURLOPT_POST, count($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $content = curl_exec($ch);
    if (strstr($content, 'Hello,'))
    {
      Utils::success('Connected to dashboard.');
      return $ch;
    }
    else if (strstr($content, 'didn\'t match'))
      Utils::error('Error while connecting to dashboard : wrong credentials.');
    else
      Utils::error('Error while connecting to dashboard : unknown error.');
    $content = '';
    return false;
  }

  /**
   * Prepares the curl request for the passed url;
   *
   * @param  string  $url  The url to init in curl.
   * @return curl handler.
   */
  public static function prepare($url)
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_JAR);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_JAR);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    return $ch;
  }

  /**
   * Closes the curl connection and erases the cookies file.
   *
   * @param  resource  $ch              Curl handler.
   * @param  bool      $delete_cookies  Set to true to delete cookie jar.
   * @return curl handler or false if the user is connected or not.
   */
  public static function close_connection($ch, $delete_cookies = false)
  {
    if ($ch !== false)
      curl_close($ch);
  }
}

?>
