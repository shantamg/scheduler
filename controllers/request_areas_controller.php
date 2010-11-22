<?php
class RequestAreasController extends AppController {

	var $name = 'RequestAreas';
	var $helpers = array('schedule');
	
	function edit($id = null,$force = false) {
		$this->redirectIfNotManager($id);
		$this->set('area',$this->RequestArea->edit($id,$force));
		$this->set('bounds', $this->getBounds());
	}

	function editNotes($id = null) {
		if (!empty($this->data)) {
			$this->record();
			$this->RequestArea->save($this->data);
			$this->set('url',$this->referer());
		} else {
			$this->id = $id;
			$this->data = $this->RequestArea->find('first');
		}
	}

	// the id should be negative (the request area to be submited)
	function submit($id) {
		$this->redirectIfNotManager($id * -1);
		$this->RequestArea->submit($id);
		
		// send email!!

		$this->redirect(array('controller'=>'requestAreas','action'=>'edit',$id*-1));
	}

	function view($id = null) {
		$this->redirectIfNot('operations');
		if (!$id) {
			$this->set('areas',$this->RequestArea->getList());
			$this->render('select');
		} else {
			$this->set('area',$this->RequestArea->view($id));
			$this->set('bounds', $this->getBounds());
		}
	}

}
