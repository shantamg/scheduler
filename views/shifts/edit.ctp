<?= $ajax->form($this->action,'post',array(
	'model'=>'Shift',
	'update'=>'dialog_content',
	'before'=>'wait();saveScroll()',
	'inputDefaults' => array('between' => '&nbsp;')
));?>
	<fieldset>
 		<legend><?php __('Edit Shift');?></legend>
	<?php
		echo $form->hidden('id');
		echo $form->input('area_id',array('id'=>'area_id'));
		echo $form->input('day_id', array('escape' => false));
		echo $form->input('start', array(
			'interval' => 15,
		));
		echo $form->input('end', array(
			'interval' => 15,
		));
		echo $form->input('num_people', array(
			'size' => 1,
			'id' => 'num_people',
			'label' => '# of People',
		));
	?>
	<?= $form->end('Submit');?>
	</fieldset>
<?=$html->link('Delete Shift',array('action'=>'delete',$this->data['Shift']['id']),
	array(
		'style'=>'position:relative;top:5px',
		'onclick'=>'wait();saveScroll()'
	)
);?>
<?=$this->element('message',array('default_field'=>'area_id'));?>
