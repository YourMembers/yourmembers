<?php

/*
* $Id: ym-package_types.class.php 2249 2012-07-18 13:56:56Z bcarlyon $
* $Revision: 2249 $
* $Date: 2012-07-18 14:56:56 +0100 (Wed, 18 Jul 2012) $
*/

class YourMember_Package_Types {
	var $types;

	function initialise($option = 'ym_package_types') {
		$this->types = array(
			'Guest',
			'Free',
			'Member'
		);

		// dont' overwrite
		add_option($option, $this);
	}
	function save($option = 'ym_package_types') {
		$this->sanity($option);
		update_option($option, $this);
	}

	function update($vars) {
		$this->types = $vars;
	}

	function create($newtype) {
		if (empty($newtype)) {
			return NULL;
		}
		$exists = FALSE;

		foreach ($this->types as $type) {
			if (strtolower($newtype) == strtolower($type)) {
				$exists = TRUE;
				break;
			}
		}

		if ($exists) {
			return FALSE;
		} else {
			$this->types[] = $newtype;
			$this->save();
			return TRUE;
		}
	}
	
	function delete($deletetype) {
		$deleted = FALSE;
		// would use array search, but do a strtolower search for master check
		foreach ($this->types as $key => $type) {
			if (strtolower($deletetype) == strtolower($type)) {
				unset($this->types[$key]);
				$deleted = TRUE;
				break;
			}
		}

		$this->save();

		return $deleted;
	}

	function sanity($option = 'ym_package_types') {
		$types = array();
		foreach ($this->types as $type) {
			if (!in_array($type, $types)) {
				$types[] = $type;
			}
		}
		$this->types = $types;
	}
}
