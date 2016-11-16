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

        jimport('kint.kint');

        $t=array();
        array_push($t, array('msg'=>'start', 'time'=>microtime(1)));

        $post  = JRequest::get('post');
        $files = JRequest::get('files');

        // having timeout issues 2015.11.17
        ini_set('max_execution_time', 360);
        $baseLink = 'index.php?option=com_ballotboxapp';
        $e_year         = JRequest::getVar('e_year');
        $excludeHeader = isset($post['header']) ? true : false;
        $newFileName      = strtolower(str_replace(' ', '_', $e_year));
        $newFileName      = preg_replace('/[^A-Za-z0-9\-]/', '_', $newFileName) . '.csv';
        $model          = $this->getModel('ballotboxapp');
        $oldFileName = $files['fileToUpload']['name'];
        $uploads = JPATH_COMPONENT . DS . 'uploads';
        $src     = $files['fileToUpload']['tmp_name'];
        $dest    = $uploads . DS . $oldFileName;

        // Run the move_uploaded_file() function here
        $moveResult = move_uploaded_file($src, $dest);
        // Evaluate the value returned from the function if needed
        if (!$moveResult) {
            // echo "ERROR: File not moved correctly";
            $msg .= JText::_('ERROR: File not moved correctly');
            return $this->setRedirect($baseLink, $msg);
        }

        array_push($t, array('msg'=>'file moved', 'time'=>microtime(1)));

        $path_parts = pathinfo($dest);
        // if this is one of the extensions JArchive handles, lets extract it
        if (in_array($path_parts['extension'], array('zip', 'tar', 'tgz', 'gz', 'gzip', 'bz2', 'bzip2', 'tbz2'))) {
            // we have an archive.  pull in JArchive to handle it
            jimport('joomla.filesystem.archive');

            // when unzipping a 50MB text file, you take up a crapload of memory
            JArchive::extract($dest, $path_parts['dirname']);

            // drop the archive now
            @unlink($dest);

            // reset the filename
            $dest = $uploads . DS . strtolower($path_parts['filename']);
            if ($path_parts['extension'] === 'zip') {
                $dest = $uploads . DS . $path_parts['filename'] . ".txt";
            }
        }

        // we need a specific filename for import
        jimport('joomla.fiesystem.file');
        JFile::move($dest, $uploads . DS . "jos_rt_imports.txt");
        $dest = $uploads . DS . "jos_rt_imports.txt";

        if (!$inputFile = fopen($dest, 'r')) {
            return $this->setRedirect($baseLink, 'unable to open file!');
        }

        $storagePath = JPATH_SITE . DS . 'files' . DS . 'raw-data';
        $outputFile  = $storagePath . DS . $newFileName;

        system("sed -i 's/\r$//g' '$dest'");
        // default
        // 7 column import
        // [0]ward    [1]division    [2]type    [3]office  [4]name   [5]party   [6]votes
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

        array_push($t, array('msg'=>'delim set to: ' . $delim, 'time'=>microtime(1)));

        $ignore = "";
        // we're just going to drop all rows without ward data
        if ($excludeHeader) {
            //$ignore = "1";
            //$ignore = " IGNORE 1 LINES";
        }

        $coldDataFields = " `ward`, `division`, `vote_type`, `office`, `name`, `party`, `votes`, `e_year`, `date_created` ";
        $inputFields = " `ward`, `division`, `type`, `office`, `name`, `party`, `votes` ";
        $outputHeader = array('ward', 'division', 'type', 'office', 'name', 'party', 'votes');

        switch ($delim) {
            case "@":
                $sFields = "ward_division,office,name,votes,lname,fname,mname,@var";
                $fields  = " (ward_division, office, name, votes, lname, fname, mname, party) ";
                $lField = 'party';
                $create = "
                CREATE TABLE IF NOT EXISTS `#__rt_imports` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT
                , `ward` smallint(5) NOT NULL
                , `division` smallint(5) NOT NULL
                , `type` char(1) NOT NULL
                , `office` varchar(255) NOT NULL
                , `name` varchar(255) NOT NULL
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
                , INDEX `name_imports` (`name`)
                , INDEX `office_imports` (`office`)
                , INDEX `party_imports` (`party`)
                , INDEX `votes_imports` (`votes`)
                ) ENGINE=MYISAM COLLATE='utf8_general_ci';";

                break;
            default:
                $sFields = "ward,division,type,office,name,party,@var";
                $fields  = " (ward, division, type, office, name, party, votes) ";
                $lField = 'votes';
                $create = "
                CREATE TABLE IF NOT EXISTS `#__rt_imports` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT
                , `ward` smallint(5) NOT NULL
                , `division` smallint(5) NOT NULL
                , `type` char(1) NOT NULL
                , `office` varchar(255) NOT NULL
                , `name` varchar(255) NOT NULL
                , `party` varchar(255) NOT NULL
                , `votes` int(11) NOT NULL
                , PRIMARY KEY (`id`)
                , INDEX `ward_imports` (`ward`)
                , INDEX `division_imports` (`division`)
                , INDEX `name_imports` (`name`)
                , INDEX `office_imports` (`office`)
                , INDEX `party_imports` (`party`)
                , INDEX `votes_imports` (`votes`)
                ) ENGINE=MYISAM COLLATE='utf8_general_ci';";

                break;
        }

        $date = &JFactory::getDate();
        $now = $date->toMySQL();

        $db = &JFactory::getDBO();

        // drop the import table
        $drop = <<<_DROP
DROP TABLE `#__rt_imports`
_DROP;

        $db->setQuery($drop);
        $db->query();

//        $truncate = <<<_TRUNCATE
//TRUNCATE TABLE IF EXISTS `#__rt_imports`
//_TRUNCATE;

//        $db->setQuery($truncate);
//        $db->query();

        // Let's pull our creds from the site config
        $config = JFactory::getConfig();
        $host   = $config->getValue('config.host');
        $user   = $config->getValue('config.user');
        $pass   = $config->getValue('config.password');
        $dbName = $config->getValue('config.db');


        $db->setQuery($create);
        $db->query();

        //sleep(10);
        //array_push($t, array('msg'=>'table created', 'time'=>microtime(1)));

        // drop indexes for import
        $deindex = <<<_DEINDEX
ALTER TABLE `#__rt_imports` DISABLE KEYS
_DEINDEX;

        $db->setQuery($deindex);
        $db->query();

        // import all together
        $import = <<<_IMPORT
mysql --user=$user --password=$pass $dbName --execute="LOAD DATA LOCAL INFILE '$dest' INTO TABLE jos_rt_imports FIELDS TERMINATED BY '$delim' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' $ignore ($sFields) SET $lField = TRIM(TRAILING '\r' FROM @var);"
_IMPORT;
/*mysqlimport \
    --verbose \
    --debug \
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
    $dest*/
        $importReturn = system($import);

        // index altogether
        $index = <<<_INDEX
ALTER TABLE `#_rt_imports` ENABLE KEYS
_INDEX;

        $db->setQuery($index);
        $db->query();

        array_push($t, array('msg'=>'file imported', 'time'=>microtime(1)));

        // transform data if needed here
        if ($delim === "@") {
            // missing fields: ward, division, type
            $db->setQuery("UPDATE `#__rt_imports` SET `type` = 'M', `ward` = LEFT(`ward_division`, 2), `division` = RIGHT(`ward_division`, 2)");
            $db->query();
            // improve our names where possible
            $db->setQuery("UPDATE `#__rt_imports` SET `name` = REPLACE(CONCAT_WS(' ', TRIM(`fname`), TRIM(`mname`), TRIM(`lname`)), '  ', ' ') WHERE `lname` IS NOT NULL AND `lname` != '' ");
            $db->query();
        }

        $db->setQuery("DELETE FROM `#__rt_imports` WHERE ward=0 and division=0");
        $db->query();

        // we have the target fields, but they may contain some garbage
        $db->setQuery("UPDATE `#__rt_imports` SET `name` = REPLACE(REPLACE(TRIM(`name`), '  ', ' '), '  ', ' '), `party` = REPLACE(REPLACE(TRIM(`party`), '  ', ' '), '  ', ' '), `office` = REPLACE(REPLACE(TRIM(`office`), '  ', ' '), '  ', ' ')");
        $db->query();

        array_push($t, array('msg'=>'transform complete', 'time'=>microtime(1)));

        // drop indexes for import
        $deindex = <<<_DEINDEX
ALTER TABLE `#__rt_cold_data` DISABLE KEYS
_DEINDEX;
        
        $db->setQuery($delindex);
        $db->query();
        
        $populate = <<<_POPULATE
INSERT INTO `#__rt_cold_data` ($coldDataFields) SELECT $inputFields, '$e_year', '$now' FROM `#__rt_imports`
_POPULATE;

        $db->setQuery($populate);
        $db->query();

        // drop indexes for import
        $index = <<<_INDEX
ALTER TABLE `#__rt_cold_data` ENABLE KEYS
_INDEX;

        $db->setQuery($index);
        $db->query();
        
        array_push($t, array('msg'=>'finished cold_data transfer', 'time'=>microtime(1)));

        // save a backup
        JFile::move($dest, $outputFile);

        array_push($t, array('msg'=>'finished copying backup', 'time'=>microtime(1)));

        $year_id = $model->insert_year($e_year);
        $office = $model->insert_office($e_year, $year_id);

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
