<?php
class RequestAreasController extends AppController {

	var $name = 'RequestAreas';
	var $helpers = array('schedule');
	var $components = array('Email');
	
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
			$this->RequestArea->recursive = -1;
			$this->data = $this->RequestArea->find('first',array(
				'conditions' => array(
					'RequestArea.id' => $id
				)
			));
		}
	}

	// the id should be negative (the request area to be submited)
	function submit($id) {
		$this->redirectIfNotManager($id * -1);
		$this->RequestArea->submit($id);

		$areaName = $this->RequestArea->field('name',array('RequestArea.id' => $id));
		$userEmail = Authsome::get('User.email');
		$username = Inflector::humanize(Authsome::get('User.username'));
		$this->loadModel('EmailAuth');
		if (!$this->EmailAuth->field('email',array('id' => 1))) {
		  $this->redirect(array('controller' => 'emailAuths', 'action' => 'noEmail','Operations'));
		}
		$auth = $this->EmailAuth->findById(1);

		// email the manager that the request was received
		if (!$this->_sendEmail(
			$userEmail, 
			'Area Request Form Received!', 
			'request_submit_mgr',
			array(
				'username' => $username,
				'areaName' => $areaName,
				'operationsEmail' => $auth['EmailAuth']['email']
				'operationsName' => $auth['EmailAuth']['name']
			)
		)) $this->set('errorMessage',$this->Email->smtpError);

		$this->Email->reset();

		// email operations about the submitted request
		if ($this->_sendEmail(
			$auth['EmailAuth']['email'],
			"{$areaName} Request Form Submitted",
			'request_submit_prsnl',
			array(
				'username' => $username,
				'areaName' => $areaName,
				'userEmail' => $userEmail
			)
		)) $this->set('errorMessage',$this->Email->smtpError);

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

	function delete($id = null) {
		$this->redirectIfNot('operations');
		$this->RequestArea->order = 'name';
		$this->set('areas',$this->RequestArea->find('list'));
		if (!empty($this->data) ) {
			$this->set('area_id',$this->data['RequestArea']['area_id']);
			$this->set('url', $this->referer());
			if ($this->data['RequestArea']['area_id']) {
				$this->RequestArea->delete($this->data['RequestArea']['area_id']);
			}
		} else {
			$this->data['RequestArea']['area_id'] = array($id);
		}
	}

}
