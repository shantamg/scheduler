<fieldset>
	<legend><?php __('Delete Schedule');?></legend>
<?
foreach($schedules as $schedule) {
	$selected = ($schedule['Schedule']['id'] == $schedule_id) ? 
		'selected' : '';
	echo $html->link($schedule['Schedule']['name'],
		array($schedule['Schedule']['id']),
		array(
			'class'=>$selected
		),
		"Delete the schedule \"{$schedule['Schedule']['name']}\"?"
	);
	echo '<br/>';
}
?>
</fieldset>
<?=$this->element('message');?>
