<?php
/**
 * Ballotboxapp View for BallotBoxApp Component
 * 
 * @package    Joomla.Tutorials
 * @subpackage Components
 * @link http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
 * @license		GNU/GPL
 */

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.component.view' );

/**
 * Ballotboxapp View
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class BallotboxappsViewBallotboxapp extends JView
{
	/**
	 * display method of Ballotboxapp view
	 * @return void ballotbox
	 **/
	function display($tpl = null)
	{

		//get the election
		$election		=& $this->get('Data');
		$isNew		= ($election[0][0]->id < 1);

		$text = $isNew ? JText::_( 'New' ) : JText::_( 'Edit' );
		JToolBarHelper::title(   JText::_( 'BallotBox App' ).': <small><small>[ ' . $text.' ]</small></small>' );

		if ($isNew)  {
			JToolBarHelper::save();
			JToolBarHelper::cancel();
		} else {
			// for existing items the button is renamed `close`
			JToolBarHelper::publish();
			JToolBarHelper::unpublish();
			JToolBarHelper::save('save_step2', 'Save');
			JToolBarHelper::cancel( 'cancel', 'Close' );
			
		}

		$this->assignRef('election', $election);

		parent::display($tpl);
	}
}