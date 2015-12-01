<?php
/**
 * Hello Model for Hello World Component
 * 
 * @package    Joomla.Tutorials
 * @subpackage Components
 * @link http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
 * @license		GNU/GPL
 */

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.model');

/**
 * Hello Hello Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class BallotboxappsModelOffice extends JModel
{
	/**
	 * Constructor that retrieves the ID from the request
	 *
	 * @access	public
	 * @return	void
	 */
	function __construct()
	{
		parent::__construct();
	}

    function publish_offices($currentElection)
    {
        $mainframe = JFactory::getApplication();
        $cid = JRequest::getVar('cid');

        foreach ($cid as $id)
        {
        	$row = JTable::getInstance('Office', 'Table');
            $row->load($id);
            $row->publish($id, 1);
        }

        $mainframe->redirect('index.php?option=com_ballotboxapp&controller=ballotboxapp&task=edit&cid[]='.$currentElection);
    }


    function unpublish_offices($currentElection)
    {
        $mainframe = JFactory::getApplication();
        $cid = JRequest::getVar('cid');

        foreach ($cid as $id)
        {
        	$row = JTable::getInstance('Office', 'Table');
            $row->load($id);
            $row->publish($id, 0);
        }

        $mainframe->redirect('index.php?option=com_ballotboxapp&controller=ballotboxapp&task=edit&cid[]='.$currentElection);
    }
}