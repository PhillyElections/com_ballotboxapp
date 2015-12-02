<?php

/**
 * Ballotboxapp Controller for BallotBoxApp Component.
 *
 * @link http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
 *
 * @license         GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.archive');
/**
 * Ballotboxapps Ballotboxapp Controller.
 */
class BallotboxappsControllerBallotboxapp extends BallotboxappsController
{
    /**
     * constructor (registers additional tasks to methods).
     */
    public function __construct()
    {
        parent::__construct();

        // Register Extra tasks
        $this->registerTask('add', 'edit');
    }

    /**
     * display the edit form.
     */
    public function edit()
    {
        $array = JRequest::getVar('cid', 0, '', 'array');
        $id = ((int) $array[0]);

        JRequest::setVar('view', 'ballotboxapp');
        if ($id) {
            JRequest::setVar('layout', 'list');
            JRequest::setVar('year_id', $id);
        } else {
            JRequest::setVar('layout', 'form');
        }

        JRequest::setVar('hidemainmenu', 1);

        parent::display();
    }

    public function save_step2()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        // having timeout issues 2015.11.17
        ini_set('max_execution_time', 360);

        $election_year_id = JRequest::getVar('id');
        $ids = JRequest::getVar('office_id');
        $publish = JRequest::getVar('office_publish');
        $name = JRequest::getVar('office_name');
        $order = JRequest::getVar('publish_order');
        $election_name = JRequest::getVar('election_name');
        $election_date = JRequest::getVar('election_date');
        $model = $this->getModel('ballotboxapp');
        if (JRequest::getVar('deleted')) {
            $model->delete_election($election_year_id);
            $model->delete_related($ids);
            $msg = JText::_('Record Deleted.');
        } else {
            $active_office = array();
            $in_active_office = array();

            $model->update_election_name($election_name, $election_year_id, $election_date);

            //First Delete all wards and divisions from tables and then reinsert them. As it will save time and easy peasy task performance boost will occur by doing this.
            $model->delete_related($ids);

            $in_id = array();
            foreach ($ids as $id => $value) {
                if ($publish[$id]) {
                    $active_office[$id] = $name[$id];
                    $model->update_office($order[$id], $id);
                    $model->insert_office_ward($ids[$id], $name[$id], $election_year_id);
                } else {
                    $in_active_office[$id] = $name[$id];
                    $in_id[] = $id;
                    $model->update_office($order[$id], $id);
                }
            }
            //$model->bulk_insert($bulk_insert_array);
            $msg = JText::_('Data Saved');
        }
        $link = 'index.php?option=com_ballotboxapp';
        $this->setRedirect($link, $msg);
    }

    /**
     * save a record (and redirect to main page).
     */
    public function save()
    {
        JRequest::checkToken() or jexit('Invalid Token');        
        // having timeout issues 2015.11.17
        ini_set('max_execution_time', 360);
        $e_year = JRequest::getVar('e_year');
        $exclude_header = isset($_POST['header']) ? true : false;
        $move_file = strtolower(str_replace(' ', '_', $e_year));
        $move_file = preg_replace('/[^A-Za-z0-9\-]/', '_', $move_file).'.csv';
        $model = $this->getModel('ballotboxapp');
        $insertStart = 'INSERT into #__rt_cold_data (`office`,`ward`,`division`,`vote_type`,`name`,`party`,`votes`,`e_year`,`date_created`) VALUES ';

        $path = JPATH_COMPONENT.DS.'uploads'.DS;
        $fileName = $_FILES['fileToUpload']['name'];
        $fileTmpLoc = $_FILES['fileToUpload']['tmp_name'];

        // Path and file name
        $pathAndName = $path.$fileName;
        // Run the move_uploaded_file() function here
        $moveResult = move_uploaded_file($fileTmpLoc, $pathAndName);
        // Evaluate the value returned from the function if needed
        if ($moveResult) {
            $path_parts = pathinfo($pathAndName);
            // if this is one of the extensions JArchive handles, lets extract it
            if (in_array($path_parts['extension'], array('zip','tar','tgz','gz','gzip','bz2','bzip2','tbz2'))) {
                // when unzipping a 50MB text file, you take up a crapload of memory
                ini_set('memory_limit', '200M');
                JArchive::extract($pathAndName, $path_parts['dirname']);
                // drop the archive now
                @unlink($pathAndName);
                // reset the filename
                $pathAndName=$path.$path_parts['filename'].".txt";
            }

            $insert = '';
            $counter = 0;
            $myfile = fopen($pathAndName, 'r') or die('update_election_nameble to open file!');
            $path_site = JPATH_SITE.DS.'files'.DS.'raw-data'.DS;
            $handle = fopen($path_site.$move_file, 'w');

            if ($exclude_header) {
                //lets drop that first row
                $arr = str_getcsv(fgets($myfile));
                fputcsv($handle, $arr);
            }

            // Output one line until end-of-file
            while (($line = fgets($myfile)) !== false) {
                $arr = str_getcsv($line);
                fputcsv($handle, $arr);
                // if the line is blank or unparsable...
                if (count($arr) === 1) {
                    $msg .= 'Note, the following line was not processed: '.$line."\n";
                    continue;
                }
                foreach ($arr as $a_key => $a_value) {
                    $arr[$a_key] = str_replace('"', '', $a_value);
                    $arr[$a_key] = trim($a_value);
                }
                $insert .= '("'.$arr[3].'", '.(int) $arr[0].', '.(int) $arr[1].', "'.$arr[2].'", "'.$arr[4].'", "'.$arr[5].'", '.(int) $arr[6].', "'.$e_year.'", NOW()),';
                ++$counter;
                if ($counter > 1000) {
                    $insert = rtrim($insert, ',');
                    $bulk_insert_array = $insertStart.$insert;
                    $insert = '';
                    try {
                        $model->bulk_insert($bulk_insert_array);
                    } catch (Exception $e) {
                        sd($e, $insert);
                    }
                    $counter = 0;
                }
            }

            // catch the leftovers
            if ($counter) {
                $insert = rtrim($insert, ',');
                $bulk_insert_array = $insertStart.$insert;
                $insert = '';
                try {
                    $model->bulk_insert($bulk_insert_array);
                } catch (Exception $e) {
                    sd($e, $insert);
                }
            }

            fclose($handle);
            fclose($myfile);
            if ($e_year) {
                try {
                    $year_id = $model->insert_year($e_year);
                } catch (Exception $e) {
                    sd($e, $model, $e_year);
                }
                try {
                    $office = $model->insert_office($e_year, $year_id);
                } catch (Exception $e) {
                    sd($e, $model, $e_year);
                }
            }

            /* $str = implode(",",$bulk_insert_array);
            echo $str;die; */
            @unlink($pathAndName);
            $msg .= JText::_('Data Saved');
            $link = 'index.php?option=com_ballotboxapp&controller=ballotboxapp&task=edit&cid[]='.$year_id;
        } else {
            //echo "ERROR: File not moved correctly";
            $link = 'index.php?option=com_ballotboxapp';
            $msg .= JText::_('ERROR: File not moved correctly');
        }

        // Check the table in so it can be edited.... we are done with it anyway

        $this->setRedirect($link, $msg);
    }

    /**
     * remove record(s).
     */
    public function remove()
    {
        $model = $this->getModel('ballotboxapp');
        if (!$model->delete()) {
            $msg = JText::_('Error: One or More Greetings Could not be Deleted');
        } else {
            $msg = JText::_('Greeting(s) Deleted');
        }

        $this->setRedirect('index.php?option=com_ballotboxapp', $msg);
    }

    /**
     * cancel editing a record.
     */
    public function cancel()
    {
        $msg = JText::_('Operation Cancelled');
        $this->setRedirect('index.php?option=com_ballotboxapp', $msg);
    }

    public function publish()
    {
        JRequest::checkToken() or jexit('Invalid Token');

        $model = $this->getModel('office');
        $model->publish_offices(JRequest::getVar('id'));
    }

  
    public function unpublish()
    {
        JRequest::checkToken() or jexit('Invalid Token');

        $model = $this->getModel('office');
        $model->unpublish_offices(JRequest::getVar('id'));
    }
}
