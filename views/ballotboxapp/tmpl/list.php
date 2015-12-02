<?php 
defined('_JEXEC') or die('Restricted access');
jimport( 'joomla.html.html' );
?>
<script>

</script>
<form action="index.php" method="post" name="adminForm">
<div id="editcell">
	<table class="adminform">
		<tr>
			<td>Election Name</td>
			<td><input type="text" name="election_name" value="<?=$this->election[1][0]->e_year; ?>"></td>
		</tr>
		<tr>
			<td>Deleted</td>
			<td><input type="checkbox" name="deleted" <?= $this->election[1][0]->deleted ? "checked='checked'" :'' ?>></td>
		</tr>
		<tr>
			<td>Election Date</td>
			<td class="paramlist_value">
				<?php  echo JHTML::calendar( $this->election[1][0]->election_date, 'election_date', 'election_date', '%Y-%m-%d', 'class="inputbox" size="25" maxlength="19"' ) ; ?>
			</td>
		</tr>
		
	</table>
	<table class="adminlist">
	<thead>
		<tr>
			<th width="1">
				<?=JText::_( 'NUM' ); ?>
			</th>
			<th width="1">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?=count( $this->election[0] ); ?>);" />
			</th>
			<th width="0">
				<?=JText::_( 'NAME' ); ?>
			</th>
			<th width="15">
				<?=JText::_( 'PUBLISH' ); ?>
			</th>			
			<th width="20">
				<?=JText::_( 'ORDER' ); ?>
			</th>
		</tr>
	</thead>
	<?php
	$k = 0;
	$election_year_id = 0;
	for ($i=0, $n=count( $this->election[0] ); $i < $n; $i++)	{
		$row = &$this->election[0][$i];
		$link = JRoute::_( 'index.php?option=com_ballotboxapp&controller=ballotboxapp&task=edit&cid[]=' . $row->id );
		$election_year_id = $row->election_id;
		?>
		<tr class="<?="row$k"; ?>">
			<td>
				<?=$i + 1; ?>
			</td>
			<td>
				<?=JHTML::_('grid.id', $i, $row->id ); ?>
			</td>
			<td>
				<?=html_entity_decode($row->name , ENT_QUOTES); ?>
				<input type="hidden" name="office_publish[<?=$row->id ;?>]" value="<?=$row->published; ?>" />
				<input type="hidden" name="office_id[<?=$row->id ;?>]" value="<?=$row->id; ?>" />
				<input type="hidden" name="office_name[<?=$row->id ;?>]" value="<?=html_entity_decode($row->name , ENT_QUOTES); ?>" />
			</td>
			<td>
				<?=JHTML::_('grid.published', $row, $i );?>
			</td>
			<td>
				<input type="text" name="publish_order[<?=$row->id ;?>]" value="<?=$row->publish_order ?>" />
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>

<input type="hidden" name="option" value="com_ballotboxapp" />
<input type="hidden" name="id" value="<?=$election_year_id; ?>" />
<input type="hidden" name="task" value="save_step2" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="ballotboxapp" />
<?php echo JHTML::_('form.token');?>
</form>
