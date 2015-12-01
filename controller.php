<?php
/**
 * BallotBoxApp default controller
 * 
 * @package    Joomla.Tutorials
 * @subpackage Components
 * @link http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
 * @license		GNU/GPL
 */

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * BallotBoxApp Component Controller
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class BallotboxappsController extends JController
{
	/**
	 * Method to display the view
	 *
	 * @access	public
	 */
	function display()
	{
		parent::display();
	}
	function step_next(){
		parent::display();
		die("nono babrrr");
	}
}