<?php
class Person extends AppModel {

	var $name = 'Person';

	var $names = null;

	var $hasMany = array(
		'Assignment',
		'FloatingShift',
		'OffDay',
		'ProfileNote'
	);
	
	var $hasOne = array(
		'PeopleSchedules',
		'House'
	);

	function valid($data) {
		if ($data['Person']['first'] == '') {
			$this->errorField = 'first';
			$this->errorMessage = "First name must not be blank";
			return false;
		}	
		return true;
	}

	function sSave($data) {
		$changes = $this->save($data);
		if(!isset($data['Person']['id'])) { // if this is a new person
			$noteData = array('ProfileNote' => array(
				'person_id' => $this->id,
				'note' =>      'Person Created'
			));
			$this->ProfileNote->create();
			$this->ProfileNote->save($noteData);
			$this->PeopleSchedules->schedule_id = $this->schedule_id;
			$this->PeopleSchedules->sSave(array(
				'PeopleSchedules' => array(
					'person_id' =>            $this->id,
					'resident_category_id' => 1
				)
			));
		}
	}
	
	function retire($id) {
		$this->id = $id;
		$this->Assignment->schedule_id = $this->schedule_id;
		$this->sContain('Assignment');
		$person = $this->find('first');
		foreach($person['Assignment'] as $assignment) {
			$this->Assignment->sDelete($assignment['id']);
		}
		$this->PeopleSchedules->schedule_id = $this->schedule_id;
		$this->PeopleSchedules->sDelete($this->getPeopleSchedulesId($id));
		$this->description = $this->PeopleSchedules->description;
	}
	
	function restore($id) {
		
	}

	function getPerson($id,$simple = false) {
		if ($simple) {
			$this->sContain(
				'PeopleSchedules.ResidentCategory'
			);
		} else {
			$this->sContain(
				'Assignment.Shift.Area',
				'PeopleSchedules.ResidentCategory',
				'OffDay',
				'FloatingShift.Area'
			);
		}
		$person = $this->find('first',array(
			'conditions' => array('Person.id' => $id)
		));
		return $person;
	}	

	function available($shift_id) {
		$this->Assignment->Shift->id = $shift_id;
		$this->Assignment->Shift->recursive = -1;
		$shift = $this->Assignment->Shift->sFind('first');

		$currentPeople = $this->getCurrent();

		$this->sContain('OffDay','Assignment.Shift','PeopleSchedules.ResidentCategory');
		$this->order = array('Person.first');
		$people = $this->find('all',array(
			'conditions' => array(
				'Person.id' => $currentPeople
			)
		));

		$list = array();
		foreach($people as $person_num => $person) {
			$list[$person_num] = $person['Person'];
			$list[$person_num]['ResidentCategory'] = $person['PeopleSchedules']['ResidentCategory'];
			$list[$person_num]['available'] = true; // benefit of the doubt
			foreach($person['OffDay'] as $OffDay) {
				
				if ($shift['Shift']['day_id'] == $OffDay['day']) {
					$list[$person_num]['available'] = false; // it's their off day
					continue;
				}
			}
			foreach($person['Assignment'] as $assignment) {
				$a = $shift['Shift'];
				$b = $assignment['Shift'];
				if ($a['id'] == $b['id']) {
					$list[$person_num]['available'] = false; // they are already on that shift
					continue;
				}
				if (
					($a['day_id'] == $b['day_id']) && ( // the shifts are on the same day AND
						($a['start'] >= $b['start'] && $a['start'] <= $b['end']) || // a starts during b or
						($b['start'] >= $a['start'] && $b['start'] <= $a['end'])    // b starts during a
					)
				) {
					$list[$person_num]['available'] = false; // they have are on a conflicting shift
					continue;
				}
			}
		}
		return $list;
	}

	function getPeople() {
		$currentPeople = $this->getCurrent();

		$this->order = 'Person.first, Person.last';
		$this->sContain(
			'PeopleSchedules.ResidentCategory'
		);
		$people = $this->find('all',array(
			'conditions' => array(
				'Person.id' => $currentPeople
			)
		));
		return $people;
	}

	function getList() {
		$currentPeople = $this->getCurrent();

		$this->order = array('Person.first');
		$this->recursive = -1;
		$people = $this->find('all',array(
			'conditions' => array(
				'Person.id' => $currentPeople
			),
			'fields' => array('Person.id', 'Person.first')
		));
		$people = Set::combine($people, '{n}.Person.id', '{n}.Person.first');
		return $people;
	}

	function listByResidentCategory() {	
		$currentPeople = $this->getCurrent();

		$this->order = array('PeopleSchedules.resident_category_id','Person.first');
		$this->sContain(
			'PeopleSchedules.ResidentCategory'
		);
		$people = $this->find('all',array(
			'conditions' => array(
				'Person.id' => $currentPeople
			)
		));
		foreach ($people as &$person) {
			$this->addDisplayName($person['Person']);
		}
		return $people;
	}

	function getCurrent() {
		$currentPeople = $this->PeopleSchedules->find('all',array(
			'conditions' => array('PeopleSchedules.schedule_id' => $this->schedule_id),
			'fields' => array('PeopleSchedules.person_id')
		));
		$currentPeople = Set::combine(
			$currentPeople,
			'{n}.PeopleSchedules.person_id',
			'{n}.PeopleSchedules.person_id'
		);
		return $currentPeople;
	}

	function getPeopleSchedulesId($id) {
		return $this->PeopleSchedules->field('id',array(
			'PeopleSchedules.person_id' => $id,
			'PeopleSchedules.schedule_id' => $this->schedule_id
		));
	}

	function addDisplayName(&$person) {
		// first time this function is called, set up a list of people's last names grouped by first name
		$person['name'] = ($person['name']) ? $person['name'] : $person['first'];
		$this->names = (!$this->names) ?
			Set::combine($this->getPeople(),'{n}.Person.id','{n}.Person.last','{n}.Person.first') 
			: $this->names;

		// now find out how many letters of the last name we need for each first name
		$lastNames = &$this->names[$person['first']];
		if (!isset($lastNames['numLetters'])) {
			$others = $lastNames;
			unset($others[$person['id']]); // don't compare with itself
			$numLetters = 0;
			if (count($others) != 0) {	
				$numLetters++; // there's another with this first name, so at least use 1 letter of last
			}
			foreach ($others as $id => $other) {
				while (substr($person['last'], 0, $numLetters) == substr($other, 0, $numLetters)) {
					$numLetters++; // up to this letter the lasts are the same, so use one more
				}
			$lastNames['numLetters'] = $numLetters;
			}
		}
		$lastNames['numLetters'] = (isset($lastNames['numLetters'])) ? $lastNames['numLetters'] : 0;
		$shortLast = substr($person['last'], 0, $lastNames['numLetters']);
		$person['name'] .= ($lastNames['numLetters'] > 0) ? " {$shortLast}" : '';
	}

}
?>
