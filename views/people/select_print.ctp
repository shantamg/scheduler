<?= $form->create('Person',array('type'=>'post','onsubmit'=>'wait()'));?>
	<fieldset>
 		<legend><?php __('Print People Schedules');?></legend>
	<div class='tall'>
<?
	foreach($people as $category) {
		$categoryData = current($category);
		$categoryName = $categoryData['PeopleSchedules']['ResidentCategory']['name'];
		$categoryId = $categoryData['PeopleSchedules']['ResidentCategory']['id'];
		$categoryColor = $categoryData['PeopleSchedules']['ResidentCategory']['color'];
?>	
		<div class='left' id='people<?=$categoryId; ?>' style='float:left;padding:10px'>
			<span style='position:relative;left:10px;'>
			<strong><?=$categoryName?></strong></span><br/>
<?	
		echo $form->input($categoryId,array(
			'label'    =>  false,
			'type'     => 'select',
			'multiple' => 'checkbox',
			'div'      => array('style' => 'color:'.$categoryColor),
			'options'  => Set::combine(array_values($category),'{n}.Person.id','{n}.Person.name'),
		));
?>	
	<hr/>
	<div class='left'>
	<?=$form->checkbox("all{$categoryId}",array(
		'onclick' => "checkAll('people{$categoryId}',this)"
	));?>
	<label for='PersonAll<?=$categoryId;?>'>All</label>
	</div>
<?
	?></div><?
	}
?>
	</div>
	</fieldset>
<?= $form->submit('Submit');?>
<?php echo $form->end();?>
<?=$this->element('message');?>
