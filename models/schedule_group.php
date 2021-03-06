<?
class ScheduleGroup extends AppModel {

	var $name = 'ScheduleGroup';
	
	var $hasMany = array(
		'Schedule'
	);

	function getPublished() {
		$published = $this->find('all',array(
			'order' => 'ScheduleGroup.end desc, ScheduleGroup.start desc',
			'contain' => array(
				'Schedule'
			)
		));
		foreach($published as &$schedules) {
			foreach($schedules['Schedule'] as $num => $schedule) {
				if ($schedule['name'] != 'Published') unset($schedules['Schedule'][$num]);
			}
			$schedules['Schedule'] = Set::sort(array_values($schedules['Schedule']),'{n}.updated','desc');
		}
		return $published;
	}

	function firstInGroup($id) {
		return $this->Schedule->field('id',
			array(
				'Schedule.schedule_group_id' => $id,
				'Schedule.name' => 'Published'
			),
			array('Schedule.updated desc')
		);
	}

}
