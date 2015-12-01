<?php defined('_JEXEC') or die('Restricted access'); ?>
<form action="index.php" method="post" name="adminForm">
<div id="editcell">
	<table class="adminlist">
	<thead>
		<tr>
			<th width="5">
				<?=JText::_( 'ID' ); ?>
			</th>
			<th width="20">
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?=count( $this->items ); ?>);" />
			</th>			
			<th>
				<?=JText::_( 'Years' ); ?>
			</th>
		</tr>
	</thead>
	<?php
	$k = 0;
	for ($i=0, $n=count( $this->items ); $i < $n; $i++)	{
		$row = &$this->items[$i];
		$link = JRoute::_( 'index.php?option=com_ballotboxapp&controller=ballotboxapp&task=edit&cid[]='. $row->id );
		?>
		<tr class="<?="row$k"; ?>">
			<td>
				<?=$row->id; ?>
			</td>
			<td>
				<?=JHTML::_('grid.id',   $i, $row->id ); ?>
			</td>
			<td>
				<a href="<?=$link; ?>"><?=$row->e_year; ?></a>
			</td>
		</tr>
		<?php
		$k = 1 - $k;
	}
	?>
	</table>
</div>

<input type="hidden" name="option" value="com_ballotboxapp" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="ballotboxapp" />
</form>
