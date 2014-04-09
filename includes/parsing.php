<?php

class Parsing
{
	/**
	 * Parses the source code to fetch corrections.
	 *
	 * @param  string  $content  The page source.
	 * @return Array containing the parsed source or false if the parsing has failed.
	 */
	public static function parse_corrections($content)
	{
		$corrections_matches = array();
		if (preg_match_all(CORRECTIONS_HEAD_REGEX, $content, $matches))
		{
			array_shift($matches);
			$corrections_matches['projects']	= $matches[0];
			$corrections_matches['enddates']	= $matches[1];
			$corrections_matches['stats']		= array();
			$corrections_matches['corrections']	= $matches[2];
			foreach ($corrections_matches['projects'] as $project_id => $project_value)
			{
				$corrections_matches['projects'][$project_id] = str_replace('Sujet ', '', $project_value);
			}
			foreach ($corrections_matches['corrections'] as $correction_id => $correction_value)
			{
				if (!preg_match_all(CORRECTIONS_PEOPLE_REGEX, $correction_value, $matches))
				{
					Utils::error('Error while trying to parse corrections.');
					return false;
				}
				$lines = explode(PHP_EOL, $corrections_matches['corrections'][$correction_id]);
				$corrections_matches['stats'][$correction_id]['count']	= 0;
				$corrections_matches['stats'][$correction_id]['done']	= 0;
				foreach ($lines as $line)
				{
					if (strpos($line, 'note'))
					{
						if (strpos($line, 'avez donné la note'))
							$corrections_matches['stats'][$correction_id]['done']++;
						$corrections_matches['stats'][$correction_id]['count']++;
					}
				}
				array_shift($matches);
				$corrections_matches['corrections'][$correction_id]			= array();
				$corrections_matches['corrections'][$correction_id]['urls']	= $matches[0];
				$corrections_matches['corrections'][$correction_id]['uids']	= $matches[1];
				foreach ($corrections_matches['corrections'][$correction_id]['uids'] as $uid_id => $uid_value)
				{
					$actual_uid	= &$corrections_matches['corrections'][$correction_id]['uids'][$uid_id];
					$actual_url	= &$corrections_matches['corrections'][$correction_id]['urls'][$uid_id];
					$actual_uid	= str_replace($corrections_matches['projects'][$correction_id] . ' ', '', $uid_value);
					$actual_url	= INTRA_URL . $actual_url;
				}
			}
			return $corrections_matches;
		}
		else
		{
			Utils::error('No peer corrections available.');
			return false;
		}
	}

	/**
	 * Parses the source code to fetch correctors.
	 *
	 * @param  string  $content  The page source.
	 * @return Array containing the parsed source or false if the parsing has failed.
	 */
	public static function parse_correctors($content)
	{
		$correctors_matches = array();
		if (preg_match_all(CORRECTORS_HEAD_REGEX, $content, $matches))
		{
			array_shift($matches);
			$correctors_matches['projects']		= $matches[0];
			$correctors_matches['stats']		= array();
			$correctors_matches['done']			= $matches[1];
			$correctors_matches['pending']		= $matches[2];
			$correctors_matches['correctors']	= $matches[3];
			foreach ($correctors_matches['projects'] as $project_id => $project_value)
			{
				$correctors_matches['projects'][$project_id] = str_replace('Sujet ', '', $project_value);
			}
			foreach ($correctors_matches['correctors'] as $corrector_id => $corrector_value)
			{
				if (!preg_match_all(CORRECTORS_PEOPLE_REGEX, $corrector_value, $matches))
				{
					Utils::error('Error while trying to parse corrections.');
					return false;
				}
				$lines = explode(PHP_EOL, $correctors_matches['correctors'][$corrector_id]);
				$correctors_matches['stats'][$corrector_id]['count']	= 0;
				$correctors_matches['stats'][$corrector_id]['done']		= 0;
				foreach ($lines as $line)
				{
					if (strpos($line, 'par'))
					{
						if (strpos($line, 'avez été noté'))
							$correctors_matches['stats'][$corrector_id]['done']++;
						$correctors_matches['stats'][$corrector_id]['count']++;
					}
				}
				$correctors_matches['correctors'][$corrector_id]			= array();
				$correctors_matches['correctors'][$corrector_id]['names']	= $matches[1];
				$correctors_matches['correctors'][$corrector_id]['uids']	= $matches[2];
			}
			return $correctors_matches;
			var_dump($correctors_matches);
		}
		else
		{
			Utils::error('No peer correctors available.');
			return false;
		}
	}
}

?>