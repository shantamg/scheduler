<?
class ScheduleHelper extends AppHelper {

	var $legend = array();
	var $total_hours = array(
		'total'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0,'7'=>0);
	var $helpers = array('html','text','role','ajax','session');
		
	function displayPersonShift($assignment,$bound,$day) {
		if (isset($assignment['Shift'])) {
			$assignment_id = $assignment['assignment_id'];
			$shift = $assignment['Shift'];	
			// if the shift is within the bounds for this day and time
			if ($shift['start'] >= $bound['start'] && 
			$shift['start'] < $bound['end'] && 
			$shift['day_id'] == $day) {
				$area_title = $shift['Area']['short_name'];
				$area_url = array('controller'=>'areas','action'=>'schedule',$shift['Area']['id']);
				$time_title = $this->displayTime($shift['start']) . " - " . 
					$this->displayTime($shift['end']);
				$time_url = array('controller'=>'assignments','action'=>'unassign',$assignment_id);
			
				$length = $this->timeToHours($shift['end']) - $this->timeToHours($shift['start']);
				$this->total_hours[$day] += $length;
				$this->total_hours['total'] += $length;
				
				/**
				 * Make $legend an array of area ids, each of which is an array (short_name, name, manager)
				 * for displaying the key (legend) at the bottom. Only one key needs to be made for each
				 * area that is on this schedule, thus the "if !isset"
				 */
				if (!isset($this->legend[$shift['Area']['id']])) {
					$this->legend[$shift['Area']['id']]['short_name'] = 
						str_replace(' ', '&nbsp;', $shift['Area']['short_name']);
					$this->legend[$shift['Area']['id']]['name'] = 
						str_replace(' ', '&nbsp;', $shift['Area']['name']);
					$this->legend[$shift['Area']['id']]['manager'] = 
						str_replace(' ', '&nbsp;', $shift['Area']['manager']);
				}
			}
			if (isset($area_title)) {
				$output = "<b>" . $this->html->link($area_title, $area_url) . "</b> ";
				$output .= $this->role->link(
					$time_title,
					array(
						'operations' => array(
							'url' => $time_url,
							'attributes' => array('class'=>'remove')
						)
					),
					!$this->session->read('Schedule.editable')
				) . "<br/>";
				return $output;
			}
		}
		if (isset($assignment['ConstantShift'])) {
			$shift = $assignment['ConstantShift'];
			// if the shift is within the bounds for this day and time
			if ($shift['start'] >= $bound['start'] && 
			$shift['start'] < $bound['end'] && 
			$shift['day_id'] == $day) {
				$title = "<b>{$shift['name']}</b><br>".
					$this->displayTime($shift['start']) . " - " . $this->displayTime($shift['end']);
				$url = array('controller'=>'constant_shifts','action'=>'edit',$shift['id']);
				$length = ($shift['specify_hours']) ? 
					$shift['hours'] :
					$this->timeToHours($shift['end']) - $this->timeToHours($shift['start']);
				$this->total_hours[$day] += $length;
				$this->total_hours['total'] += $length;
				$output = "<span class='const'>".$this->role->link(
					$title,
					array(
						'operations' => array(
							'url' => $url,
							'attributes' => array(
								'escape' => false,
								'update'=>'dialog_content',
								'complete'=>"openDialog('constant_{$shift['id']}')"
							),
							'ajax'
						)
					),
					!$this->session->read('Schedule.editable')
				) . "</span><br/>";
				return "<span id='constant_{$shift['id']}'>{$output}</span>";
			}
		}
	}

	function displayAreaShift($shift,$bound,$day) {
		// if the shift is within the bounds for this day and time
		if ($shift['start'] >= $bound['start'] && $shift['start'] < $bound['end'] && $shift['day_id'] == $day) {
			$time = $this->displayTime($shift['start']) . " - " . 
				$this->displayTime($shift['end']);
			$people = '';
			$people_displayed = 0;
			foreach ($shift['Assignment'] as $assignment) {
				$people_displayed++;
				$length = $this->timeToHours($shift['end']) - $this->timeToHours($shift['start']);
				$this->total_hours[$day] += $length;
				$this->total_hours['total'] += $length;
				
				if (Authsome::get('role') == 'operations' && $this->session->read('Schedule.editable')) {
					$people .= $this->html->tag('span', null, array(
						'style'=>"position:relative",
						'onmouseover' => "showElement('goto_{$assignment['Assignment']['id']}')",
						'onmouseout' => "hideElement('goto_{$assignment['Assignment']['id']}')"
					));
				}
				$people .= $this->role->link(
					$assignment['Person']['name'],
					array(
						'' =>  array( 
							'url' => ($assignment['Person']['id'] == 0) ? null : array(
								'controller'=>'people','action'=>'schedule',$assignment['Person']['id']
							),
							'attributes' => array(
								'class' => 'RC_' . $assignment['PeopleSchedules']['resident_category_id']
							)
						),
						'operations' => array(
							'url' => array(
								'controller'=>'assignments',
								'action'=>'unassign',
								$assignment['Assignment']['id']
							),
							'attributes' => array(
								'class' => 'remove_RC_'.$assignment['PeopleSchedules']['resident_category_id'],
								'style' => 'margin:10px',
								'onmouseover' => "showElement('goto_{$assignment['Assignment']['id']}')",
								'onmouseout' => "hideElement('goto_{$assignment['Assignment']['id']}')",
								'onclick' => 'saveScroll()'
							)
						)
					),
					(!$this->session->read('Schedule.editable')) 
				) . '<br/>';
				if (Authsome::get('role') == 'operations' && 
				$this->session->read('Schedule.editable') && $assignment['Person']['id'] != 0) {
					$people .= $this->html->link('(view)',
						array('controller'=>'people','action'=>'schedule',$assignment['Person']['id']),
						array(
							'style'=>
								'display:none;
								position:absolute;
								top:0;
								right:-3.0em;
								background-color:#DDDDDD;
								padding:5px',
							'id'=>"goto_{$assignment['Assignment']['id']}"
						)
					)."</span>";
				}

				
			}
			for ($i = $people_displayed; $i < $shift['num_people']; $i++) {
				$unassigned = $this->role->link(
					'________',
					array(
						'operations' => array(
							'url' => array('controller'=>'assignments','action'=>'assign',$shift['id']),
							'attributes'=>array(
								'update'=>'dialog_content',
								'complete'=>"openDialog({$shift['id']})"
							),
							'ajax'
						)
					),
					(!$this->session->read('Schedule.editable')) 
				);
				$people .= "{$unassigned}<br/>";
			}
		}
		if (isset($time)) {
			$time = $this->role->link(
				$time,
				array(
					'operations' => array(
						'url' => array('controller'=>'shifts','action'=>'edit',$shift['id']),
						'attributes'=>array(
							'update'=>'dialog_content',
							'complete'=>"openDialog({$shift['id']})"
						),
						'ajax'
					)
				),
				(!$this->session->read('Schedule.editable')) 
			);
			return "<span id='{$shift['id']}'><b>" .
				$time . "</b><br/>" . $people . "</span><br/><br/>";
		}
	}
	
	function displayTime($time) {
		$time = strtotime($time);
		$hours = date('g', $time);
		$minutes = date('i', $time);
		$minutes = ($minutes == '00') ? "" : ":" . $minutes;
		return $hours . $minutes;
	}	

	function timeToHours($time) {
		$time = strtotime($time);
 		$hour = date('G', $time);
 		$decimal = (date('i', $time) / 60);
 		return $hour + $decimal;
	}
	
	function offDays($off_days,$day) {
		foreach ($off_days as $off_day) {
			if ($off_day['day_id'] == $day) {
				return 'class="dayoff_bg"';
			}
		}
	}
	
	function displayPersonFloating($floating_shifts) {
		$output = array();
		foreach ($floating_shifts as $floating_shift) {
			$hours = $floating_shift['hours'];
			$this->total_hours['total'] += $hours;
			$hours = ($hours == 1) ? 
				"$hours hour " :
				"$hours hours ";
			$hours = $this->role->link(
				$hours,
				array(
					'operations' => array(
						'url' => array('controller' => 'floating_shifts', 'action' => 'edit', $floating_shift['id']),
						'attributes' => array(
							'update' => 'dialog_content',
							'complete' => "openDialog('floating_{$floating_shift['id']}',false,'top')",
						),
						'ajax'
					)
				),
				!$this->session->read('Schedule.editable')
			);
			$link_title = $floating_shift['Area'] ? $floating_shift['Area']['name'] : '';
			$link_url = $floating_shift['Area'] ? 
				array('controller'=>'areas','action'=>'schedule',$floating_shift['Area']['id']) :
				array();
			$note = " ({$floating_shift['note']})";
			$note = ($note == ' ()') ? ' ' : $note;
			
			// need to make sure floating shifts are in the legend as well.
			// replace spaces with &nbsp; so that line breaks are not in the middle of something
			if ($floating_shift['Area']) {
				if (!isset($this->legend[$floating_shift['Area']['id']])) {
					$this->legend[$floating_shift['Area']['id']]['short_name'] = 
						str_replace(' ', '&nbsp;', $floating_shift['Area']['short_name']);
					$this->legend[$floating_shift['Area']['id']]['name'] = 
						str_replace(' ', '&nbsp;', $floating_shift['Area']['name']);
					$this->legend[$floating_shift['Area']['id']]['manager'] = 
						str_replace(' ', '&nbsp;', $floating_shift['Area']['manager']);
				}
			}
			$output[] = "<span id='floating_".$floating_shift['id']."'>"
			. $hours . $this->html->link($link_title, $link_url) . $note . '</span>';
			
		}
		return (!$output ? '' : 'Plus ' . $this->text->toList($output));	
	}
	
	function displayAreaFloating($floating_shifts) {
		$output = array();
		foreach($floating_shifts as $floating_shift) {
			$hours = $floating_shift['hours'];
			$this->total_hours['total'] += $hours;
			$hours = ($hours == 1) ? 
				"$hours hour" :
				"$hours hours ";
			$hours = $this->role->link(
				$hours,
				array(
					'operations' => array(
						'url' => array('controller' => 'floating_shifts', 'action' => 'edit', $floating_shift['id']),
						'attributes' => array(
							'update' => 'dialog_content',
							'complete' => "openDialog('floating_{$floating_shift['id']}',false,'top')",
						),
						'ajax'
					)
				),
				!$this->session->read('Schedule.editable')
			);
			$link_title = $floating_shift['Person']['name'];
			$link_url = array('controller'=>'people','action'=>'schedule',$floating_shift['Person']['id']);
			$note = " ({$floating_shift['note']})";
			$note = ($note == ' ()') ? ' ' : $note;
			$output[] = "<span id='floating_".$floating_shift['id']."'>"
			. $hours . " w/ " . $this->html->link(
				$link_title, $link_url, array(
					'class' => 'RC_' . $floating_shift['Person']['PeopleSchedules']['resident_category_id']
				)
			) . $note . '</span>';
		}	
		return (!$output ? '' : 'Also ' . $this->text->toList($output));	
	}

	// this to be called after all shifts have been displayed so that $this->legend is accurate
	function displayLegend() {
		$output = '';
		foreach($this->legend as $legend) {
			$output .= 
			"<strong>{$legend['short_name']}</strong>&nbsp;=&nbsp;{$legend['name']}&nbsp;({$legend['manager']}) ";
		}
		return $output;
	}
}
?>
