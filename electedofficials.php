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
class plgContentElectedofficials extends JPlugin
{

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
    public function plgContentElectedofficials(&$subject, $params)
    {
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
    public function onPrepareContent(&$article, &$params, $limitstart)
    {
        global $mainframe;
        if (is_object($article)) {
            return $this->prepOfficialsDisplay($article->text);
        }
        return $this->prepOfficialsDisplay($article);
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
    public function onAfterDisplayTitle(&$article, &$params, $limitstart)
    {
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
    public function onBeforeDisplayContent(&$article, &$params, $limitstart)
    {
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
    public function onAfterDisplayContent(&$article, &$params, $limitstart)
    {
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
    public function onBeforeContentSave(&$article, $isNew)
    {
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
    public function onAfterContentSave(&$article, $isNew)
    {
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
    public function prepOfficialsDisplay(&$text)
    {

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
                    if (($j % 2) == 1) {
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
    public function getElectedofficialsStrings(&$text)
    {
        // Quick, cheap chance to back out.

        if (JString::strpos($text, 'Electedofficials') === false) {
            return true;
        }

        $search = "(\[\[Electedofficials\]\])";

        while (preg_match($search, $text, $regs, PREG_OFFSET_CAPTURE)) {

/*            $temp = explode('=', trim(trim($regs[0][0], '[]'), '[]'));
if (sizeof($temp) === 2) {
$temp2 = explode(':', $temp[0]);
$field = $temp2[1];
$value = $temp[1];
}*/

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
    public function getOfficials()
    {
        $db = &JFactory::getDBO();

        $q = 'SELECT DISTINCT `office_level` FROM `#__electedofficials` WHERE `published`= 1';

        // initialize all our display data segments
        $segments = array('City Officials', 'City Commissioners', 'City Council Members', 'State Officials', 'State Representatives', 'State Senators', 'United States President', 'United States Senators', 'United States Representatives');
        foreach ($segments as $segment) {
            $results[$segment] = array();
        }

        $db->setQuery($q);
        $levels = $db->loadObjectList();

        foreach ($levels as $level) {
            $q = 'SELECT DISTINCT `office` FROM `#__electedofficials` WHERE `office_level`="' . $level->office_level . '" AND `published`= 1';
            $db->setQuery($q);
            $offices = $db->loadObjectList();
            foreach ($offices as $office) {
                $q = 'SELECT * FROM `#__electedofficials` WHERE `office_level`="' . $level->office_level . '" AND `office`="' . $office->office . '" ORDER BY (CASE WHEN `leadership_role` IS NULL THEN "ZZZ" ELSE `leadership_role` END) ASC, CAST(`congressional_district` AS UNSIGNED) ASC, CAST(`state_senate_district` AS UNSIGNED) ASC, CAST(`state_representative_district` AS UNSIGNED) ASC, CAST(`council_district` AS UNSIGNED) ASC';

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
    public function placeResult(&$results, $array, $level, $office)
    {
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
        } else if (count($results[$segment]) === 1 && count($array) > 1) {
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
    public function getContent(&$results)
    {
        $return = '';
        foreach ($results as $label => $group) {
            $return .= '<div class="card-group">';
            $return .= '<h3>' . $label . '</h3>';

            foreach ($group as $items) {
                foreach ($items as $item) {
                    $fullname = $item['first_name'] . ' ' . ($item['middle_name'] ? $item['middle_name'] . ' ' : '') . $item['last_name'] . ($item['middle_name'] ? ' ' . $item['middle_name'] : '');
                    $return .= '	<div class="h-card">';
                    if ($item['website']) {
                        $return .= '		<a class="p-name u-url" ' . ($item['url'] ? 'href="' . $item['url'] . '"' : '') . ' target="_blank">' . $fullname . '</a> <sup class="p-note" title="' . (strtoupper($item['party']) === 'D' ? 'Democratic' : 'Republican') . '">' . strtoupper($item['party']) . '</sup>';
                    } else {
                        $return .= '		' . $fullname . ' <sup class="p-note" title="' . (strtoupper($item['party']) === 'D' ? 'Democratic' : 'Republican') . '">' . strtoupper($item['party']) . '</sup>';
                    }
                    $return .= '		<div class="p-location">District 4</div>';
                    $return .= '		<div class="p-adr h-adr">';
                    $return .= '			<div class="p-street-address">' . $item['main_contact_address_1'] . '</div>';
                    if ($item['main_contact_address_2']) {
                        $return .= '			<div class="p-street-address">' . $item['main_contact_address_2'] . '</div>';
                    }
                    $return .= '			<div class="p-locality">' . $item['main_contact_city'] . '</div>';
                    $return .= '			<div class="p-region">' . $item['main_contact_state'] . '</div>';
                    $return .= '			<div class="p-postal-code">' . $item['main_contact_zip'] . '</div>';
                    $return .= '		</div>';
                    if ($item['main_contact_phone']) {
                        $return .= '		<div class="p-tel">' . $item['main_contact_phone'] . '</div>';
                    }
                    if ($item['main_contact_fax']) {
                        $return .= '		<div class="p-tel-fax">' . $item['main_contact_fax'] . '</div>';
                    }

                    $return .= '	</div>';
                }
            }
            $return .= '</div>';
        }
/*

'id' => string (2) "72"

'office_level' => string (7) "federal"

'leadership_role' => NULL

'office' => string (30) "President of the United States"

'congressional_district' => NULL

'state_senate_district' => NULL

'state_representative_district' => NULL

'council_district' => NULL

'first_name' => string (6) "Barack"

'middle_name' => NULL

'last_name' => string (5) "Obama"

'suffix' => NULL

'party' => string (1) "D"

'first_elected' => string (4) "2008"

'next_election' => string (4) "2016"

'website' => string (18) "www.whitehouse.gov"

'email' => NULL

'main_contact_address_1' => string (21) "1600 Pennsylvania Ave"

'main_contact_address_2' => NULL

'main_contact_city' => string (10) "Washington"

'main_contact_state' => string (2) "D."

'main_contact_zip' => string (5) "20500"

'main_contact_phone' => string (12) "202-456-1111"

'main_contact_fax' => string (12) "202-456-2461"

'local_contact_1_address_1' => string (0) ""

'local_contact_1_address_2' => NULL

'local_contact_1_city' => string (0) ""

'local_contact_1_state' => string (0) ""

'local_contact_1_zip' => string (0) ""

'local_contact_1_phone' => NULL

'local_contact_1_fax' => NULL

'local_contact_2_address_1' => string (0) ""

'local_contact_2_address_2' => NULL

'local_contact_2_city' => string (0) ""

'local_contact_2_state' => string (0) ""

'local_contact_2_zip' => string (0) ""

'local_contact_2_phone' => NULL

'local_contact_2_fax' => NULL

'local_contact_3_address_1' => string (0) ""

'local_contact_3_address_2' => NULL

'local_contact_3_city' => string (0) ""

'local_contact_3_state' => string (0) ""

'local_contact_3_zip' => string (0) ""

'local_contact_3_phone' => NULL

'local_contact_3_fax' => NULL

'published' => string (1) "1"
 */

        return $return;
    }
}
