<?php
/**
 * @version     $Id: electedofficials.php
 * @package     PVotes
 * @subpackage  Content
 * @copyright   Copyright (C) 2015 Philadelphia Elections Commission
 * @license     GNU/GPL, see LICENSE.php
 * @author      Matthew Murphy <matthew.e.murphy@phila.gov>
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('kint.kint');

/**
 * Example Content Plugin
 *
 * @package     Joomla
 * @subpackage  Content
 * @since       1.5
 */
class plgContentElectedofficials extends JPlugin {

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */
	public function plgContentElectedofficials(&$subject, $params) {
		parent::__construct($subject, $params);
	}

	/**
	 * Default event
	 *
	 * Isolate the content and call actual processor
	 *
	 * @param   object      The article object.  Note $article->text is also available
	 * @param   object      The article params
	 * @param   int         The 'page' number
	 */
	public function onPrepareContent(&$article, &$params, $limitstart) {
		global $mainframe;

		if (is_object($article)) {
			return $this->prepOfficialsDisplay($article->text);
		}
		return $this->prepOfficialsDisplay($article);
	}

	/**
	 * use onAfterDispatch to wire in the stylesheet
	 * @return void
	 */
	public function onAfterDispatch() {
		$document = &JFactory::getDocument();
		$document->addStyleSheet(JURI::base().'plugins/content/electedofficials.style.css', 'text/css', null, array());
	}

	/**
	 * Example after display title method
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param   object   $article   The article object.  Note $article->text is also available
	 * @param   object   $params   The article params
	 * @param   int      $limitstart   The 'page' number
	 * @return  string
	 */
	public function onAfterDisplayTitle(&$article, &$params, $limitstart) {
		global $mainframe;

		return '';
	}

	/**
	 * Example before display content method
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param   object   $article   The article object.  Note $article->text is also available
	 * @param   object   $params   The article params
	 * @param   int      $limitstart   The 'page' number
	 * @return  string
	 */
	public function onBeforeDisplayContent(&$article, &$params, $limitstart) {
		global $mainframe;

		return '';
	}

	/**
	 * Example after display content method
	 *
	 * Method is called by the view and the results are imploded and displayed in a placeholder
	 *
	 * @param   object   $article   The article object.  Note $article->text is also available
	 * @param   object   $params   The article params
	 * @param   int      $limitstart   The 'page' number
	 * @return  string
	 */
	public function onAfterDisplayContent(&$article, &$params, $limitstart) {
		global $mainframe;

		return '';
	}

	/**
	 * Example before save content method
	 *
	 * Method is called right before content is saved into the database.
	 * Article object is passed by reference, so any changes will be saved!
	 * NOTE:  Returning false will abort the save with an error.
	 *  You can set the error by calling $article->setError($message)
	 *
	 * @param   object   $article   A JTableContent object
	 * @param   bool     $isNew   If the content is just about to be created
	 * @return  bool        If false, abort the save
	 */
	public function onBeforeContentSave(&$article, $isNew) {
		global $mainframe;

		return true;
	}

	/**
	 * Example after save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 *
	 * @param   object   $article   A JTableContent object
	 * @param   bool     $isNew   If the content is just about to be created
	 * @return  void
	 */
	public function onAfterContentSave(&$article, $isNew) {
		global $mainframe;

		return true;
	}

	/**
	 * Check for a Electedofficials block,
	 * skip <script> blocks, and
	 * call getElectedofficialsStrings() as appropriate.
	 *
	 * @param   string   $text  content
	 * @return  bool
	 */
	public function prepOfficialsDisplay(&$text) {

		// Quick, cheap chance to back out.
		if (JString::strpos($text, 'Electedofficials') === false) {
			return true;
		}

		$text = explode('<script', $text);
		foreach ($text as $i => $str) {
			if ($i == 0) {
				$this->getElectedofficialsStrings($text[$i]);
			} else {
				$str_split = explode('</script>', $str);
				foreach ($str_split as $j => $str_split_part) {
					if (($j%2) == 1) {
						$this->getElectedofficialsStrings($str_split[$i]);
					}
				}
				$text[$i] = implode('</script>', $str_split);
			}
		}
		$text = implode('<script', $text);

		return true;
	}

	/**
	 * Find Electedofficials blocks,
	 * get display per block.
	 *
	 * @param   string   $text  content
	 * @return  bool
	 */
	public function getElectedofficialsStrings(&$text) {
		// Quick, cheap chance to back out.

		if (JString::strpos($text, 'Electedofficials') === false) {
			return true;
		}

		$search = "(\[\[Electedofficials\]\])";

		while (preg_match($search, $text, $regs, PREG_OFFSET_CAPTURE)) {
			if ($content = $this->getOfficials()) {
				$text = JString::str_ireplace($regs[0][0], $content, $text);
			}
		}

		return true;
	}

	/**
	 * Get officials data,
	 * return officials display.
	 *
	 * @return  method
	 */
	public function getOfficials() {
		$db = &JFactory::getDBO();

		$q = 'SELECT DISTINCT `office_level` FROM `#__electedofficials` WHERE `published`= 1';

		// initialize all our display data segments
		//$segments = array('City Officials', 'City Commissioners', 'City Council Members', 'State Officials', 'State Representatives', 'State Senators', 'United States President', 'United States Senators', 'United States Representatives');
		$segments = array('City Officials', 'City Commissioners', 'City Council Members', 'State Officials', 'State Representatives', 'State Senators', 'United States Senators', 'United States Representatives');
		foreach ($segments as $segment) {
			$results[$segment] = array();
		}

		$db->setQuery($q);
		$levels = $db->loadObjectList();

		foreach ($levels as $level) {
			$q = 'SELECT DISTINCT `office` FROM `#__electedofficials` WHERE `office_level`="'.$level->office_level.'" AND `published`= 1';
			$db->setQuery($q);
			$offices = $db->loadObjectList();
			foreach ($offices as $office) {
				$q = 'SELECT * FROM `#__electedofficials` WHERE `office_level`="'.$level->office_level.'" AND `office`="'.$office->office.'" ORDER BY (CASE WHEN `leadership_role` IS NULL THEN "ZZZ" ELSE `leadership_role` END) ASC, CAST(`congressional_district` AS UNSIGNED) ASC, CAST(`state_senate_district` AS UNSIGNED) ASC, CAST(`state_representative_district` AS UNSIGNED) ASC, CAST(`council_district` AS UNSIGNED) ASC';

				$db->setQuery($q);
				$data = $db->loadAssocList();
				$this->placeResult($results, $data, $level->office_level, $office->office);
			}
		}

		return $this->getContent($results);
	}

	/**
	 * placeResult slots a record into a display-friendly position in the results array
	 *
	 * @param  array  &$results [description]
	 * @param  array  $array    group of related, pre-sorted results
	 * @param  string $level    group value for office_level
	 * @param  string $office   group label for office
	 * @return void
	 */
	public function placeResult(&$results, $array, $level, $office) {
		switch ($level) {
			case 'federal':
				switch ($office) {
				case 'U.S. Senate':
						$segment = 'United States Senators';
						break;
				case 'U.S. Representative':
						$segment = 'United States Representatives';
						break;
				default:
						$segment = 'United States President';
						break;
				}
				break;
			case 'state':
				switch ($office) {
				case 'State Representative':
						$segment = 'State Representatives';
						break;
				case 'State Senator':
						$segment = 'State Senators';
						break;
				default:
						$segment = 'State Officials';
						break;
				}
				break;
			case 'local':
				switch ($office) {
				case 'City Commissioner':
						$segment = 'City Commissioners';
						break;
				case 'City Council':
				case 'City Council At-Large':
						$segment = 'City Council Members';
						break;
				default:
						$segment = 'City Officials';
						break;
				}
				break;
		}

		if (count($results[$segment]) === 1 && count($array) === 1) {
			// additional single element
			array_push($results[$segment][0], $array[0]);
		} elseif (count($results[$segment]) === 1 && count($array) > 1) {
			foreach ($array as $arr) {
				// additional group of elements
				array_push($results[$segment][0], $arr);
			}
		} else {
			// first (or solo) chunk
			array_push($results[$segment], $array);
		}
	}

	/**
	 * Get officials data,
	 * return officials display.
	 *
	 * @param   array   $results  officials data
	 * @return  string
	 */
	public function getContent(&$results) {
		$return = '';
		foreach ($results as $label => $group) {
			$return .= '<div class="section">';
			$return .= '<h4>'.$label.'</h4>';

			foreach ($group as $items) {
				foreach ($items as $item) {
					// defaults
					$contact_address_1 = $item['main_contact_address_1'];
					$contact_address_2 = $item['main_contact_address_2'];
					$contact_city      = $item['main_contact_city'];
					$contact_state     = $item['main_contact_state'];
					$contact_zip       = $item['main_contact_zip'];
					$contact_phone     = $item['main_contact_phone'];
					$contact_phone2    = null;
					$contact_fax       = $item['main_contact_fax'];

					if (strtolower($item['first_name']) === 'vacant') {
						//keep defaults
					} elseif (strtolower($item['main_contact_city']) === 'philadelphia') {
						// city overrides
						$contact_phone2 = $item['local_contact_1_phone'];
						$contact_fax    = $item['main_contact_fax']?$item['main_contact_fax']:$item['local_contact_1_fax'];
					} elseif (strtolower($item['local_contact_1_city']) === 'philadelphia' || in_array($label, array('State Officials', 'State Representatives', 'State Senators', 'United States Senators', 'United States Representatives'))) {
						// out-of-city overrides
						$contact_address_1 = $item['local_contact_1_address_1'];
						$contact_address_2 = $item['local_contact_1_address_2'];
						$contact_city      = $item['local_contact_1_city'];
						$contact_state     = $item['local_contact_1_state'];
						$contact_zip       = $item['local_contact_1_zip'];
						$contact_phone     = $item['local_contact_1_phone'];
						$contact_phone2    = $item['local_contact_2_phone'];
						$contact_fax       = $item['local_contact_1_fax'];
					}
					$fullname = $item['first_name'].' '.($item['middle_name']?$item['middle_name'].' ':'').$item['last_name'].($item['suffix']?' '.$item['suffix']:'');
					$district = trim($item['congressional_district']).trim($item['state_senate_district']).trim($item['state_representative_district']).trim($item['council_district']);
					$return .= '	<div class="h-card">';

					if (in_array($label, array('City Officials', 'State Officials'))) {
						$return .= '        <div class="p-job-title">'.$item['office'].'</div>';
					}
					if ($item['website']) {
						$return .= '		<a class="p-name u-url" href="http://'.$item['website'].'" target="_blank">'.$fullname.'</a> <sup class="p-note" title="'.(strtoupper($item['party']) === 'D'?'Democratic':'Republican').'">'.strtoupper($item['party']).'</sup>';
					} else {
						$return .= '		'.$fullname.' <sup class="p-note" title="'.(strtoupper($item['party']) === 'D'?'Democratic':'Republican').'">'.strtoupper($item['party']).'</sup>';
					}
					if ($item['leadership_role']) {
						$return .= '<div class="p-role">'.$item['leadership_role'].'</div>';
					}
					if ($district) {
						$return .= '        <div class="p-location">District '.$district.'</div>';
					}
					$return .= '		<div class="p-adr h-adr">';
					$return .= '			<div class="p-street-address">'.$contact_address_1.'</div>';
					if ($contact_address_2) {
						$return .= '			<div class="p-street-address">'.$contact_address_2.'</div>';
					}
					$return .= '			<div class="p-locality">'.$contact_city.'</div>';
					$return .= '			<div class="p-region">'.$contact_state.'</div>';
					$return .= '			<div class="p-postal-code">'.$contact_zip.'</div>';
					$return .= '		</div>';
					if ($contact_phone) {
						$return .= '		<div class="p-tel">'.$contact_phone.'</div>';
					}
					if ($contact_phone2) {
						$return .= '        <div class="p-tel">'.$contact_phone2.'</div>';
					}
					if ($contact_fax) {
						$return .= '        <div class="p-tel-fax">'.$contact_fax.'</div>';
					}
					$return .= '	</div>';
				}
			}
			$return .= '</div>';
		}
		return $return;
	}
}
