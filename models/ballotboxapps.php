<?php
/**
 * Hellos Model for Hello World Component
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 * @link http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
 * @license         GNU/GPL Ballotbox
 */

// No direct access
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.model');

/**
 * Hello Model
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class BallotboxappsModelBallotboxapps extends JModel
{
    /**
     * Hellos data array
     *
     * @var array
     */
    public $_data;


    /**
     * Returns the query
     * @return string The query to be used to retrieve the rows from the database
     */
    public function _buildQuery()
    {
    	// added order by -- id desc for a defacto recent date sort
        $query = 'SELECT * ' . ' FROM #__rt_election_year where deleted = 0 order by `election_date` DESC';
        return $query;
    }

    /**
     * Retrieves the hello data
     * @return array Array of objects containing the data from the database
     */
    public function getData()
    {
        // Lets load the data if it doesn't already exist
        if (empty( $this->_data )) {
            $query = $this->_buildQuery();
            $this->_data = $this->_getList($query);
        }

        return $this->_data;
    }
}
