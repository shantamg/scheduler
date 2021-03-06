<?php
class ResidentCategoriesController extends AppController {

	var $name = 'ResidentCategories';

	function add() {
		$this->redirectIfNotEditable();
		if (!empty($this->data)) {
			if ($this->ResidentCategory->valid($this->data)) {
				$this->ResidentCategory->create();
				$this->record();
				$changes = $this->ResidentCategory->sSave($this->data);
				$this->stop($this->ResidentCategory->description($changes));
				$this->set('url', $this->referer());
			} else {
				$this->set('errorField',$this->ResidentCategory->errorField);
				$this->set('errorMessage',$this->ResidentCategory->errorMessage);
			}
		}
	}

	function edit($id = null) {
		$this->redirectIfNotEditable();
		if ($id || $this->data) {
			if (!empty($this->data)) {
				if ($this->ResidentCategory->valid($this->data)) {
					$this->record();
					$changes = $this->ResidentCategory->sSave($this->data);
					$this->stop($this->ResidentCategory->description($changes));
					$this->set('url', $this->referer());
				 } else {
					$this->set('errorField',$this->ResidentCategory->errorField);
					$this->set('errorMessage',$this->ResidentCategory->errorMessage);
				}
			} else {
				$this->ResidentCategory->id = $id;
				$this->ResidentCategory->recursive = -1;
				$this->data = $this->ResidentCategory->sFind('first');
			}
		} else {
			$this->ResidentCategory->order = array('sort_order asc');
			$this->set('categories',$this->ResidentCategory->sFind('list'));
			$this->render('select_edit');
		}
	}

	function delete($id = null) {
		$this->redirectIfNotEditable();
		$this->set('categories',$this->ResidentCategory->sFind('list'));
		if (!empty($this->data)) {
			$this->set('category_id',$this->data['ResidentCategory']['category_id']);
			$this->set('url', $this->referer());
			if ($this->data['ResidentCategory']['category_id']) {
				$this->record();
				$description = $this->ResidentCategory->sDelete($this->data['ResidentCategory']['category_id']);
				deleteCache('people');
				$this->stop($description);
			}
		} else {
			$this->data['ResidentCategory']['category_id'] = array($id);
		}
	}

	function reorder() {
		$this->redirectIfNotEditable();
		if (!empty($this->data)) {
			$order = explode(',',$this->data['ResidentCategory']['lcategories_order']);
			$this->record();
			foreach($order as $num => $id) {
				$this->ResidentCategory->sSave(array(
					'ResidentCategory' => array(
						'id' => $id,
						'sort_order' => $num
					)
				));
			}
			$this->stop('Categories reordered');
		}
		$this->ResidentCategory->order = 'ResidentCategory.sort_order asc';
		$this->ResidentCategory->recursive = -1;
		$categories = $this->ResidentCategory->sFind('all');
		$this->set('categories', Set::combine(
			$categories,'{n}.ResidentCategory.id','{n}.ResidentCategory.name'));
	}

}
?>
