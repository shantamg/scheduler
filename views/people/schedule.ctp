<? $this->pageTitle=$person['Person']['name']."'s Schedule"; ?>
<?= (!$this->params['isAjax']) ? $this->element('menu'): '';?>
<span id='schedule_content' class="RC_<?=$person['Person']['resident_category_id']?>">
<?=$this->element('schedule_content');?>
</span>
<?=$this->element('dialog');?>