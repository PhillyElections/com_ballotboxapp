<?php
/**
 * @version		$Id: offices.php 1812 2015-11-1 18:45:06Z lefteris.kavadas $
 * @package		K2
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

class TableOffice extends JTable
{

    var $id = null;
    var $election_id = null;
    var $name = null;
    var $deleted = null;
    var $published = null;
    var $publish_order = null;
    var $date_modified = null;

    public function __construct(&$db)
    {
        parent::__construct('#__rt_offices', 'id', $db);
    }
}