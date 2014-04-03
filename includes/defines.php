<?php

/**
 * File constants
 */
define('CONFIGURATION_PATH', APP_PATH . '/configuration');
define('CONFIGURATION_FILE', CONFIGURATION_PATH . '/configuration.pt');
define('COOKIE_JAR', APP_PATH . '/cookies.pt');

/**
 * URL constants
 */
define('INTRA_URL', 'https://intra.42.fr');
define('DASHBOARD_URL', 'https://dashboard.42.fr');
define('DASHBOARD_LOGIN_URL', 'https://dashboard.42.fr/login/');

/**
 * Regex constants
 */
define('CSRF_TOKEN_REGEX', '/name=\'csrfmiddlewaretoken\' value=\'(.*)\'/');
define('CORRECTIONS_HEAD_REGEX', '/^.*noter vos pairs<\/a> sur le projet <a.*>(.*)<\/a> avant le <span.*>(.*)<\/span>.*<ul>(.*)<\/ul>.*$/misU');
define('CORRECTIONS_PEOPLE_REGEX', '/^.*<a href=\"(.*)\".*>.*le groupe (.*)<\/a>.*$/misU');
define('VOGSPHERE_REGEX', '/"url_repository":"(vogsphere@vogsphere\.42\.fr:.*?)"/');

?>
