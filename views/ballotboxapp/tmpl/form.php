<?php defined('_JEXEC') or die('Restricted access'); ?>

<form action="index.php" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
<div class="col100">
	<fieldset class="adminform">
		<legend><?php echo JText::_( 'Details' ); ?></legend>

		<table class="admintable">
		<tr>
			<td width="100" align="right" class="key">
				<label for="greeting">
					<?php echo JText::_( 'Year' ); ?>:
				</label>
			</td>
			<td>
				<input class="text_area" type="text" name="e_year" id="e_year" size="32" maxlength="250" value="<?php echo $this->hello->greeting;?>" />
			</td>
		</tr>
		<tr>
			<td width="100" align="right" class="key">
				<label for="greeting">
					<?php echo JText::_( 'Exclude Header Row' ); ?>:
				</label>
			</td>
			<td>
				<input class="text_area" type="checkbox" name="header" value="Include" checked=checked> <sup>1</sup>
			</td>
		</tr>
		<tr>
			<td width="100" align="right" class="key">
				<label for="greeting">
					<?php echo JText::_( 'File' ); ?>:
				</label>
			</td>
			<td>
				<input type="file" name="fileToUpload" id="fileToUpload"> <sup>2</sup>
			</td>
		</tr>
	</table>
<p><sup>1</sup>The first row is usually column headers, which are not needed.  Check if you're unsure.<br>  Blank lines will simply be ignored.</p>
<p><sup>2</sup>Expected format is CSV either with text fields quoted or all fields quoted. ...I.E. the format exported by Guardian will work as-is (currently gives a .txt file in a .csv format), or will work after opening and casually saving out as .csv.<br>  Stray blank lines will be ignored.</p>
	</fieldset>

</div>
<div class="clr"></div>

<input type="hidden" name="option" value="com_ballotboxapp" />
<input type="hidden" name="id" value="<?php echo $this->hello->id; ?>" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="ballotboxapp" />
<?php echo JHTML::_('form.token');?>
</form>
