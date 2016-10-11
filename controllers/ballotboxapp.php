<?php

/**
 * Ballotboxapp Controller for BallotBoxApp Component.
 *
 * @link http:// docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_4
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
        $id    = ((int) $array[0]);

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

    public function saveStep2()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        // having timeout issues 2015.11.17
        ini_set('max_execution_time', 360);

        $election_year_id = JRequest::getVar('id');
        $ids              = JRequest::getVar('office_id');
        $publish          = JRequest::getVar('office_publish');
        $name             = JRequest::getVar('office_name');
        $order            = JRequest::getVar('publish_order');
        $election_name    = JRequest::getVar('election_name');
        $election_date    = JRequest::getVar('election_date');
        $model            = $this->getModel('ballotboxapp');
        if (JRequest::getVar('deleted')) {
            $model->delete_election($election_year_id);
            $model->delete_related($ids);
            $msg = JText::_('Record Deleted.');
        } else {
            $active_office    = array();
            $in_active_office = array();

            $model->update_election_name($election_name, $election_year_id, $election_date);

            // First Delete all wards and divisions from tables and then reinsert them. As it will save time and easy peasy task performance boost will occur by doing this.
            $model->delete_related($ids);

            $in_id = array();
            foreach ($ids as $id => $value) {
                if ($publish[$id]) {
                    $active_office[$id] = $name[$id];
                    $model->update_office($order[$id], $id);
                    $model->insert_office_ward($ids[$id], $name[$id], $election_year_id);
                } else {
                    $in_active_office[$id] = $name[$id];
                    $in_id[]               = $id;
                    $model->update_office($order[$id], $id);
                }
            }
            // $model->bulk_insert($bulk_insert_array);
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

        $post  = JRequest::get('post');
        $files = JRequest::get('files');

        // having timeout issues 2015.11.17
        ini_set('max_execution_time', 360);
        $e_year         = JRequest::getVar('e_year');
        $exclude_header = isset($post['header']) ? true : false;
        $move_file      = strtolower(str_replace(' ', '_', $e_year));
        $move_file      = preg_replace('/[^A-Za-z0-9\-]/', '_', $move_file) . '.csv';
        $model          = $this->getModel('ballotboxapp');
        $insertStart    = 'INSERT into #__rt_cold_data (`office`,`ward`,`division`,`vote_type`,`name`,`party`,`votes`,`e_year`,`date_created`) VALUES ';

        $oldFileName = $files['results_file']['name'];

        $uploads = JPATH_COMPONENT . DS . 'uploads';
        $src     = $files['results_file']['tmp_name'];
        $dest    = $uploads . DS . $oldFileName;

        // Run the move_uploaded_file() function here
        $moveResult = move_uploaded_file($src, $dest);
        // Evaluate the value returned from the function if needed
        if (!$moveResult) {
            // echo "ERROR: File not moved correctly";
            $link = 'index.php?option=com_ballotboxapp';
            $msg .= JText::_('ERROR: File not moved correctly');
            return $this->setRedirect($link, $msg);
        }
        $path_parts = pathinfo($dest);
        // if this is one of the extensions JArchive handles, lets extract it
        if (in_array($path_parts['extension'], array('zip', 'tar', 'tgz', 'gz', 'gzip', 'bz2', 'bzip2', 'tbz2'))) {
            // we have an archive.  pull in JArchive to handle it
            jimport('joomla.filesystem.archive');

            // when unzipping a 50MB text file, you take up a crapload of memory
            $extracted = JArchive::extract($dest, $path_parts['dirname']);

            // drop the archive now
            @unlink($dest);

            // reset the filename
            $dest = $uploads . DS . strtolower($path_parts['filename']);
            if ($path_parts['extension'] === 'zip') {
                $dest = $uploads . DS . $path_parts['filename'] . ".txt";
            }
            jimport('joomla.fiesystem.file');
            JFile::move($dest, $uploads . DS . "jos_pv_live_imports.txt");
            $dest = $uploads . DS . "jos_pv_live_imports.txt";
        }

        if (!$inputFile = fopen($dest, 'r')) {
            return $this->setRedirect($baseLink, 'unable to open file!');
        }

        $storagePath = JPATH_SITE . DS . 'files' . DS . 'raw-data';
        $outputFile  = $storagePath . DS . $newFileName;

        // default
        // 7 column import
        // [0]ward    [1]division    [2]type    [3]office  [4]candidate   [5]party   [6]votes
        $delim = ',';

        $line = fgets($inputFile);

        if (count(str_getcsv($line, '@')) > 1) {
            // option 2
            // 8 column import
            // Precinct_Name@Office/Prop Name@Tape_Text@Vote_Count@Last_Name@First_Name@Middle_Name@Party_Name@
            // [0]Precinct_Name   [1]Office/Prop Name   [2]Tape_Text   [3]Vote_Count   [4]Last_Name   [5]First_Name   [6]Middle_Name   [7]Party_Name
            $delim = "@";
        }
        fclose($inputFile);

        $ignore = "0";
        if ($excludeHeader) {
            $ignore = "1";
            // $ignore = "    IGNORE 1 LINES \n";
        }

        $coldDataFields = " `ward`, `division`, `vote_type`, `office`, `candidate`, `party`, `votes` ";
        $inputFields = " `ward`, `division`, `type`, `office`, `candidate`, `party`, `votes` ";
        $outputHeader = array('ward', 'division', 'type', 'office', 'candidate', 'party', 'votes');

        switch ($delim) {
            case "@":
                $sFields = "ward_division,office,candidate,votes,lname,fname,mname,party";
                $fields  = " (ward_division, office, candidate, votes, lname, fname, mname, party) ";
                break;
            default:
                $sFields = "ward,division,type,office,candidate,party,votes";
                $fields  = " (ward, division, type, office, candidate, party, votes) ";
                break;
        }

        $date = &JFactory::getDate();
        $now = $dateNow->toMySQL();

        $db = &JFactory::getDBO();

        // Let's pull our creds from the site config
        $config = JFactory::getConfig();
        $host   = $config->getValue('config.host');
        $user   = $config->getValue('config.user');
        $pass   = $config->getValue('config.password');
        $dbName = $config->getValue('config.db');

        // create temportary table
        $create = <<<_CREATE
CREATE TABLE IF NOT EXISTS `#__rt_imports` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT
, `ward` smallint(5) NOT NULL
, `division` smallint(5) NOT NULL
, `type` char(1) NOT NULL
, `office` varchar(255) NOT NULL
, `candidate` varchar(255) NOT NULL
, `party` varchar(255) NOT NULL
, `votes` int(11) NOT NULL
, `ward_division` varchar(255) NOT NULL
, `lname` varchar(255) NOT NULL
, `fname` varchar(255) NOT NULL
, `mname` varchar(255) NOT NULL
, PRIMARY KEY (`id`)
, INDEX `ward_imports` (`ward`)
, INDEX `division_imports` (`division`)
, INDEX `ward_division_imports` (`ward`,`division`)
, INDEX `candidate_imports` (`candidate`)
, INDEX `office_imports` (`candidate`)
, INDEX `party_imports` (`party`)
, INDEX `votes_imports` (`votes`)
) ENGINE=ARIA COLLATE='utf8_general_ci';
_CREATE;

        $db->setQuery($create);
        $db->query();

        // drop indexes for import
        $deindex = <<<_DEINDEX
ALTER TABLE `#__rt_imports` DISABLE KEYS
_DEINDEX;

        $db->setQuery($deindex);
        $db->query();

        // import all together
        $import = <<<_IMPORT
mysqlimport \
--local \
--compress \
--user=$user \
--password=$pass \
--host=$host \
--ignore-lines=$ignore \
--fields-terminated-by='$delim' \
--fields-optionally-enclosed-by='"' \
--columns='$sFields' \
$dbName \
$dest
_IMPORT;

        $importReturn = @system($import);

        // index altogether
        $index = <<<_INDEX
"ALTER TABLE `#_rt_imports` ENABLE KEYS"
_INDEX;

        $db->setQuery($index);
        $db->query();

        // transform data if needed here
        if ($delim === "@") {
            // missing fields: ward, division, type
            $db->setQuery("UPDATE `#__rt_imports` SET `type` = 'M', `ward` = LEFT(`ward_division`, 2), `division` = RIGHT(`ward_division`, 2)");
            $db->query();
            // improve our candidates where possible
            $db->setQuery("UPDATE `#__rt_imports` SET `candidate` = REPLACE(CONCAT_WS(' ', `fname`, `mname`, `lname`), '  ', ' ') WHERE `lname` IS NOT NULL AND `lname` != '' ");
            $db->query();
        }

        // drop indexes for import
        $deindex = <<<_DEINDEX
ALTER TABLE `#__rt_cold_data` DISABLE KEYS
_DEINDEX;

        $db->setQuery($deindex);
        $db->query();

        $populate = <<<_POPULATE
INSERT INTO `#__rt_cold_data` ($coldDataFields , `e_year`, `data_created`) SELECT $outputFields , '$e_year', '$now' FROM `#__rt_imports`
_POPULATE;

        $db->setQuery($populate);
        $db->query();

        // index altogether
        $index = <<<_INDEX
"ALTER TABLE `#_rt_cold_data` ENABLE KEYS"
_INDEX;

        $db->setQuery($index);
        $db->query();

        // open our file-for-download
        $handle    = fopen($outputFile, 'w');

        // write a header for the file-for-download
        fputscsv($handle, $outputHeader);

        $backup = <<<_BACKUP
SELECT $outputFields FROM `#__rt_imports
_BACKUP;

        $db->setQuery($backup);

        // Output one line until end-of-file
        while (($line = $db->loadRow()) !== false) {
            fputcsv($handle, $line);
        }

        // done writing file-for-download
        fclose($handle);

        // we're finished, drop the import table
        $drop = <<<_DROP
DROP TABLE IF EXISTS `#__rt_imports`
_DROP;

        $db->setQuery($drop);
        $db->query();

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

        $msg .= JText::_('Data Saved');
        $link = 'index.php?option=com_ballotboxapp&controller=ballotboxapp&task=edit&cid[]=' . $year_id;

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
