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

            $temp = explode('=', trim(trim($regs[0][0], '[]'), '[]'));
            if (sizeof($temp) === 2) {
                $temp2 = explode(':', $temp[0]);
                $field = $temp2[1];
                $value = $temp[1];
            }

            if ($field && $value && $content = $this->getOfficials($field, $value)) {
                $text = JString::str_ireplace($regs[0][0], $content, $text);
            }
        }
        return true;
    }

    /**
     * Get officials data,
     * return officials display.
     *
     * @param   string   $field  db column
     * @param   string   $value  db value
     * @return  method
     */
    public function getOfficials($field, $value)
    {
        $db = &JFactory::getDBO();

        $query1 = 'SELECT distinct `level` from `#__electedofficials`';

        $db->setQuery($query1);
        $levels = $db->loadObjectList();
        foreach ($levels as $level) {
            d($level);
            $query2 = 'SELECT distinct `office` from `#__electedofficials` where `level`="$level"';

        }
        dd($results);
        return $this->getContent($results);
    }

    /**
     * Get officials data,
     * return officials display.
     *
     * @param   objectList   $results  officials data
     * @return  string
     */
    public function getContent(&$results)
    {
        $return = "";
        foreach ($results as $result) {
            $sid = $result->sid;
            if (JString::strpos($result->sid, '%') !== false && JString::strpos($result->sid, '^') !== false) {
                $temp = explode('%', $result->sid);
                $sid = JString::trim(JString::str_ireplace('^', ' ', $temp[1]));
            }
            $return .= '<li><a href="/ballot_paper/' . $result->file_id . '.pdf" target="_blank">District ' . $sid . '</a></li>';
        }
        return "<h4>Download Sample Ballots</h4><ul>" . $return . "</ul>";
    }
}
