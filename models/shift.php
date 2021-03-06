<?php
class Shift extends AppModel {

	var $name = 'Shift';

	var $belongsTo = array(
		'Area',
		'Day'
	);
	
	var $hasMany = array(
		'Assignment'
	);
	
	function valid($data) {
		$start = $this->dbTime($data['Shift']['start']);
		$end = $this->dbTime($data['Shift']['end']);
		if ($end <= $start) {	
			$this->errorField = 'start';
			$this->errorMessage = "Oops! Those hours don't make sense";
			return false;
		}
		$num_people = $data['Shift']['num_people'];
		if (!is_numeric($num_people) ||	$num_people < 1) {
			$this->errorField = 'num_people';
			$this->errorMessage = "Invalid # of people";
			return false;
		}	
		if (isset($data['Shift']['id'])) {
			$this->sContain('Assignment');
			$this->id = $data['Shift']['id'];
			$shift = $this->sFind('first');
			$num_assigned = count($shift['Assignment']);
			if ($num_people < $num_assigned) {
				$this->errorField = 'num_people';
				$this->errorMessage = "Too many people already assigned";
				return false;
			}
		}
		return true;
	}

	function sSave($data) {
		if (is_array($data['Shift']['start'])) {
			$times = array('start', 'end');
			foreach($times as $time) {
				$data['Shift'][$time] = $this->dbTime($data['Shift'][$time]);
			}
		}
		return parent::sSave($data);
	}
	
	function sDelete($id) {
		$this->clear($id);
		return parent::sDelete($id);
	}

	function clear($id) {
		$this->id = $id;
		$this->sContain('Assignment');
		$shift = $this->sFind('first');
		foreach($shift['Assignment'] as $assignment) {
			$this->Assignment->sDelete($assignment['id']);
		}
	}
	
	function description($changes) {
		if (isset($changes['newData'])) {
			$newData = $this->format($changes['newData']);
			if ($changes['oldData']['id'] == '') {
				$desc = "New Shift: {$newData['name']}";
			} else {
				$oldData = $this->format($changes['oldData']);				
				$desc = "Shift changed: ({$oldData['name']})";
				$listed = false;
				foreach($changes['newData'] as $field => $val) {
					if ($changes['newData'][$field] != $changes['oldData'][$field]) {
						switch ($field) {
							case 'area_id':
								$desc .= $listed ? ', ' : ' ';
								$desc .= 
									'area:'.$newData['area_id'];
								break;
							case 'day_id':
								$desc .= $listed ? ', ' : ' ';
								$desc .= 
									'day:'.$newData['day_id'];
								break;		
							case 'start':
								$desc .= $listed ? ', ' : ' ';
								$desc .= 
									'start:'.$newData['start'];
								break;		
							case 'end':
								$desc .= $listed ? ', ' : ' ';
								$desc .= 
									'end:'.$newData['end'];
								break;		
							case 'num_people':
								$desc .= $listed ? ', ' : ' ';
								$desc .= 
									'# of people:'.$newData['num_people'];
								break;	
						}
						$listed = true;
					}
				}
			}
		} else {
			$oldData = $this->format($changes);
			$desc = "Shift deleted: {$oldData['name']}";
		}
		return $desc;
	}
	
	function format($data, $rich = false, $show_day = true) {
		$tags = $rich ?
			array(
				'short_name' => array('<b>','</b>')
			)
		:
			array(
				'short_name' => array('','')
			);
		$this->Area->id = $data['area_id'];
		$this->Area->recursive = -1;
		$area = $this->Area->sFind('first');
		$data['area_id'] = $tags['short_name'][0].$area['Area']['short_name'].$tags['short_name'][1];
		if ($show_day) {
			$this->Day->id = $data['day_id'];
			$this->Day->recursive = -1;
			$day = $this->Day->sFind('first');
			$data['day_id'] = substr($day['Day']['name'],0,3);
		} else {
			$data['day_id'] = '';
		}
		$start = strtotime($data['start']);
		$minutes = (date('i',$start) == '00') ? '' : ':i';
		$data['start'] = date("g{$minutes}",$start);
		$end = strtotime($data['end']);
		$minutes = (date('i',$end) == '00') ? '' : ':i';
		$data['end'] = date("g{$minutes}",$end);
		$data['name'] = 
			$data['area_id'].' '.
			$data['day_id'].' '.
			$data['start'].' - '.
			$data['end'];
		return $data;
	}

	function listBySlot($person_id,$day,$start,$end) {
		$this->Person = &$this->Assignment->Person;
		$this->Person->sContain('OffDay','Assignment.Shift','PeopleSchedules.ResidentCategory');
		$person = $this->Person->find('first',array(
			'conditions' => array(
				'Person.id' => $person_id
			)
		));
		$this->id = '';
		$this->sContain('Area','Assignment.Person.PeopleSchedules.ResidentCategory');
		$shifts = $this->sFind('all', array(
			'conditions' => array(
				'Shift.start <' => $end,
				'AND' => array(
					'Shift.start >=' => $start
				),
				'Shift.day_id' => $day
			),
			'order' => 'Area.short_name, Shift.start, Shift.end'
		));
		$unassigned = array();
		$noAssignment = array();
		foreach($shifts as $num => &$shift) {
			$shift['available'] = $this->Person->available($person, $shift);
			$shift['Shift'] = $this->format($shift['Shift'],true,false);
			if (count($shift['Assignment']) < $shift['Shift']['num_people']) {
				$unassigned[] = $shift;
				if (!$shift['Assignment']) {
					$noAssignment[] = $num;
					continue;
				}
			}
			foreach($shift['Assignment'] as $num => &$assignment) {
				if ($assignment['person_id'] == 0) {
					$assignment['Person']['name'] = $assignment['name'];
					$assignment['Person']['id'] = 0; 
					$assignment['Person']['PeopleSchedules']['resident_category_id'] = 0;
				} else {
					if ($assignment['Person']['id'] == $person_id) {
						unset($shift['Assignment'][$num]);
						continue;
					}
					$this->Person->addDisplayName($assignment['Person']);
				}
			}
		}
		foreach($noAssignment as $num) {
			unset($shifts[$num]);
		}
		return array('unassigned' => $unassigned, 'assigned' => $shifts);
	}

	function addAssignedShifts(&$data) {
		if (!isset($data['Assignment'])) {
			return;
		}
		$shift_ids = array();
		$stars = array();
		foreach($data['Assignment'] as &$assignment) {
			$shift_ids[$assignment['id']] = $assignment['shift_id'];
			$stars[$assignment['id']] = $assignment['star'];
		}
		$this->sContain('Area');
		$shifts = $this->sFind('all',array(
			'conditions' => array('Shift.id' => $shift_ids),
			'order' => 'Shift.day_id, Shift.start, Shift.end, Area.short_name'
		));
		foreach($shifts as &$assignment) {
			$assignment['assignment_id'] = array_search($assignment['Shift']['id'],$shift_ids);
			$assignment['star'] = $stars[$assignment['assignment_id']];
			$assignment['Shift']['Area'] = $assignment['Area'];
			unset($assignment['Area']);
		}
		$data['Assignment'] = $shifts;
	}

}
?>
