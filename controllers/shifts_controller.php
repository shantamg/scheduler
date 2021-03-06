<?php
class ShiftsController extends AppController {

	var $name = 'Shifts';

	function add($area_id = null,$day_id = null, $start = null, $end = null) {
		if ($area_id < 0) { // if it's a request form
			$this->redirect(array('controller'=>'RequestShifts','action'=>'add',$area_id,$day_id,$start,$end));
		}
		$this->redirectIfNotEditable();
		if (!empty($this->data)) {
			if ($this->Shift->valid($this->data)) {
				$this->Shift->create();
				$this->record();
				$changes = $this->Shift->sSave($this->data);
				$this->stop($this->Shift->description($changes));
				$this->set('url', array('controller' => 'areas', 'action' => 'schedule', $this->data['Shift']['area_id']));
			} else {
				$this->set('errorField',$this->Shift->errorField);
				$this->set('errorMessage',$this->Shift->errorMessage);
			}
			$start = $this->data['Shift']['start'];
			$end = $this->data['Shift']['end'];
		} else {
			$start = str_replace("-",":",$start);
		}
		$this->loadModel('Area');
		$this->Area->order = 'name';
		$this->set('areas',$this->Area->sFind('list'));
		$this->set('area_id',$area_id);
		$day_id = ($day_id) ? $day_id : 1;
		$this->set('day_id',$day_id);
		$start = ($start) ? $start : '13:00:00';
		$end = ($end) ? $end : date("H:i:s",strtotime($start." + 1 hour"));
		$this->set('start',$start);
		$this->set('end',$end);
		$this->loadModel('Day');
		$this->Day->order = 'id';
		$this->set('days',$this->Day->sFind('list',array(
			'conditions' => array(
				'Day.name <>' => ''
			)
		)));
	}
	
	function edit($id = null) {
		if ($id < 0) { // if it's a request form
			$this->redirect(array('controller'=>'RequestShifts','action'=>'edit',$id));
		}
		$this->redirectIfNotEditable();
		if (!$id && empty($this->data)) {
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Shift->valid($this->data)) {
				$this->record();
				$changes = $this->Shift->sSave($this->data);
				$this->stop($this->Shift->description($changes));
				$this->set('url', array('controller' => 'areas', 'action' => 'schedule', $this->data['Shift']['area_id']));
			} else {
				$this->set('errorField',$this->Shift->errorField);
				$this->set('errorMessage',$this->Shift->errorMessage);
			}
		}
		if (empty($this->data)) {
			$this->id = $id;
			$this->data = $this->Shift->sFind('first');
		}
		$this->loadModel('Area');
		$this->Area->order = 'name';
		$this->set('areas',$this->Area->sFind('list'));
		$this->loadModel('Day');
		$this->Day->order = 'id';
		$this->set('days',$this->Day->sFind('list',array(
			'conditions' => array(
				'Day.name <>' => ''
			)
		)));
	}
	
	function delete($id) {
		if ($id < 0) { // if it's a request form
			$this->redirect(array('controller'=>'RequestShifts','action'=>'delete',$id));
		}
		$this->redirectIfNotEditable();
		$this->record();
		$changes = $this->Shift->sDelete($id);
		$this->stop($this->Shift->description($changes));
		$this->redirect($this->referer());
	}
		
	function listBySlot($person_id,$day_id,$start,$end) {
		$start = str_replace("-",":",$start);
		$end = str_replace("-",":",$end);
		$this->set('shifts',$this->Shift->listBySlot($person_id,$day_id,$start,$end));
		$this->loadModel('Day');
		$day = $this->Day->sFind('first', array(
			'conditions' => array('Day.id' => $day_id),
			'recursive' => -1
		));
		$this->set('person_id', $person_id);
		$this->set('day', $day['Day']['name']);
	}


}
?>
