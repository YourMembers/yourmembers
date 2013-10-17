<?php

/*
* $Id: ym-form_generation.class.php 2561 2013-01-24 13:29:41Z bcarlyon $
* $Revision: 2561 $
* $Date: 2013-01-24 13:29:41 +0000 (Thu, 24 Jan 2013) $
*/

class ym_form_generation extends YourMember_FormGeneration {
}

class YourMember_FormGeneration {
	var $tr_class = '';
	var $style = '';
	var $jquery = TRUE;

	function __construct($return = FALSE) {
		if (is_admin()) {
			$this->input_class = 'ym_admin_input';
		} else {
			$this->input_class = 'ym_input';
		}

		$this->return = $return;
	}

	function render_form_table_text_row($label, $name, $value = '', $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					<input type="text" class="' . $this->input_class . '" name="' . $name . '" value="' . esc_html($value) . '" />
				</td>
			</tr>';
		
		if ($this->return) {
			return $html;
		}
		echo $html;
	}
	function render_form_table_email_row($label, $name, $value = '', $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					<input type="email" class="' . $this->input_class . '" name="' . $name . '" value="' . esc_html($value) . '" />
				</td>
			</tr>';
			
		if ($this->return) {
			return $html;
		}
		echo $html;
	}
	function render_form_table_password_row($label, $name, $value = '', $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					<input type="password" class="' . $this->input_class . '" name="' . $name . '" value="' . esc_html($value) . '" />
				</td>
			</tr>';
			
		if ($this->return) {
			return $html;
		}
		echo $html;
	}
	function render_form_table_textarea_row($label, $name, $value = '', $caption=false, $rows=6) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th style="vertical-align: top; text-align: left;">' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					<textarea class="editorContent" style="font-family:\'Lucida Grande\',Verdana; font-size: 11px;" rows="' . $rows . '" cols="70" name="' . $name . '">' . esc_html($value) . '</textarea>
				</td>
			</tr>';
			
		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	function render_form_table_wp_editor_row($label, $name, $value = '', $caption=false, $args = FALSE) {
		if (!$args) {
			$args = array(
				'media_buttons' => FALSE,
			);
		}

		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th style="vertical-align: top; text-align: left;">' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					';

					ob_start();
					wp_editor($value, $name, $args);
					$html .= ob_get_contents();
					ob_end_clean();

					$html .= '
				</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	function render_form_table_url_row($label, $name, $value = '', $caption=false, $url=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}

		if ( !$url )
			$url = site_url();
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">' . $url . '
					<input type="text" class="' . $this->input_class . '" name="' . $name . '" value="' . esc_html($value) . '" />
				</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	/**
	jQuery things
	*/
	function render_form_table_date_picker_row($label, $name, $value = '', $caption=false, $year = FALSE) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}

		if (!is_numeric($value)) {
			$value = FALSE;
		}
		if (!$value) {
			$value = '';
		}

		$class = 'ym_datepicker';
		if ($year)
			$class = 'ym_yearpicker';

		$html = '';
		$html .= '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">';
		if ($this->jquery) {
			if ($value) {
				$value = date(YM_DATE_ONLY, $value);
			}
			$html .= '<input type="text" name="' . $name . '" class="' . $class . '" value="' . $value . '" />';
			wp_enqueue_script('ym_admin_post', YM_JS_DIR_URL . 'ym_admin_post.js', array('jquery'));
		} else {
			// jquery is off
			if (is_array($value)) {
				$value['month'] = array_shift($value);
				$value['date'] = array_shift($value);
				$value['year'] = array_shift($value);
			} else if ($value) {
				// timestamp!
				$tos = $value;
				$value = array();
				list($value['month'], $value['date'], $value['year'], $value['hour'], $value['min']) = explode(',', date('n,j,Y,H,i', $tos));
			} else {
				$value = array(
					'month'	=> '',
					'date'	=> '',
					'yes'	=> '',
					'hour'	=> '',
					'min'	=> ''
				);
			}

			$html .= '<select name="ym_month_' . $name . '">
						';
						
						$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
						foreach ($months as $index => $month) {
							if ($month) {
								$html .= '<option value="' . $index . '"';
								
								if ($value['month'] == $index) {
									$html .= ' selected="selected"';
								}
								
								$html .= '/>' . $month . '</option>';
							}
						}
						
						$html .= '
					</select>
					/
					<input name="ym_date_' . $name . '" style="width: 20px;" value="' . esc_html($value['date']) . '" />
					/
					<input name="ym_year_' . $name . '" style="width: 30px;" value="' . esc_html($value['year']) . '" />';
		}
		$html .= '
			</td>
		</tr>
		';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	function render_form_table_datetime_picker_row($label, $name, $value = '', $caption=false, $year=false, $minutes_to_use=1) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}

		// check the input is sane
		if (!is_numeric($value)) {
			$value = FALSE;
		}
		if (!$value) {
			$value = '';
		}

		$class = 'ym_datepicker';
		if ($year)
			$class = 'ym_yearpicker';

		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td>';

		if ($this->jquery) {
			$hour = $min = '';
			if ($value) {
				$hour = date('H', $value);
				$min = date('i', $value);
				$value = date(YM_DATE_ONLY, $value);
			}
			$html .= '<input type="text" name="' . $name . '" class="' . $class . '" value="' . $value . '" />';
			wp_enqueue_script('ym_admin_post', YM_JS_DIR_URL . 'ym_admin_post.js', array('jquery'));

			$value = array(
				'hour'	=> $hour,
				'min'	=> $min,
			);
		} else {
			if (is_array($value)) {
				$value['month'] = array_shift($value);
				$value['date'] = array_shift($value);
				$value['year'] = array_shift($value);
				$value['hour'] = array_shift($value);
				$value['min'] = array_shift($value);
			} else if ($value) {
				// timestamp!
				$tos = $value;
				$value = array();
				list($value['month'], $value['date'], $value['year'], $value['hour'], $value['min']) = explode(',', date('n,j,Y,H,i', $tos));
			} else {
				$value = array(
					'month'	=> '',
					'date'	=> '',
					'yes'	=> '',
					'hour'	=> '',
					'min'	=> ''
				);
			}
		
			$html .= '
					<select name="ym_month_' . $name . '">
						';
						
						$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
						foreach ($months as $index => $month) {
							if ($month) {
								$html .= '<option value="' . $index . '"';
								
								if ($value['month'] == $index) {
									$html .= ' selected="selected"';
								}
								
								$html .= '/>' . $month . '</option>';
							}
						}
						
						$html .= '
					</select>
					/
					<input name="ym_date_' . $name . '" style="width: 20px;" value="' . esc_html($value['date']) . '" />
					/
					<input name="ym_year_' . $name . '" style="width: 30px;" value="' . esc_html($value['year']) . '" />
			';
		}

		$html .= '
					@
					<select name="ym_hour_' . $name . '" style="width: 50px;">';
					for($x=0;$x<24;$x++) {
						$html .= '<option value="' . $x . '"';
						if ($x == $value['hour']) {
							$html .= ' selected="selected"';
						}
						$html .= '>' . $x . '</option>';
					}
					$html .= '
					</select>
					:
					<select name="ym_min_' . $name . '" style="width: 50px;">';
					for($x=0;$x<60;$x+=$minutes_to_use) {
						$html .= '<option value="' . $x . '"';
						if ($x == $value['min']) {
							$html .= ' selected="selected"';
						}
						$html .= '>' . $x . '</option>';
					}
					$html .= '</select>
				</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}
	/**
	end jquery tings
	*/

	function render_form_table_file_row($label, $name, $value=false, $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					<input type="file" name="' . $name . '" />
					' . ($value ? '<br />' . $value:'') . '
				</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	function render_form_table_checkbox_row($label, $name, $value=false, $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">
					<input type="checkbox" class="' . $this->input_class . '" name="' . $name . '" value="1" ' . ($value ? 'checked="checked"' : '') . ' />
				</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	/**
	@todo: processor
	@todo: cost/price field
	*/

	/**
	Divider
	*/
	function render_form_table_divider($label='&nbsp;') {
		$html = '<tr class="table_divider"><td></td><th><h4>' . $label . '</h4></th></tr>';
		if ($this->return) {
			return $html;
		}
		echo $html;
	}

	/**
	RADIOs
	*/
	function render_form_table_radio_row($label, $name, $value, $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">';
				
		$html .= $this->render_yesno_radio($name, $value);

		$html .= 	'</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}
	
	function render_yesno_radio($name, $value = FALSE, $id=false) {
		if (!$id) {
			$id = $name;
		}
		if (!$value) {
			$value = 0;
		}
		
		$html = '<select name="' . $name . '" id="' . $id . '">';
		$html .= '<option ' . selected($value, 1, false) . ' value="1">' . __('Yes', 'ym') . '</option>';
		$html .= '<option ' . selected($value, 0, false) . ' value="0">' . __('No', 'ym') . '</option>';
		$html .= '</select>';
		
		return $html;
	}
		
	function render_combo_from_query($name, $sql, $default=false, $excuse=false, $return=false) {
		global $wpdb;
	
		if ($results = $wpdb->get_results($sql)) {
			$html = '<select name="' . $name . '">';
		
			foreach ($results as $key=>$obj) {
				$html .= '<option ';
				if ($default == $obj->value) {
					$html .= 'selected="selected"';
				}
				$html .= ' value="' . $obj->value . '">' . $obj->label . '</option>';
			}
		
			$html .= '</select>';
		} else {
		    if ($excuse) {
			$html = $excuse;
		    } else {
			$html = __('<em>Sorry no data for this dropdown</em>', 'ym');
		    }
		}
		
		if ($return) {
		    return $html;
		} else {
		    echo $html;
		}
	}
	
	function render_combo_from_array($name, $data, $default=false, $return=false) {
		$html = '<select name="' . $name . '">';
	
		foreach ($data as $value=>$label) {
			$html .= '<option ';
			if ($default == $value) {
				$html .= 'selected="selected"';
			}
			$html .= ' value="' . $value . '">' . $label . '</option>';
		}
	
		$html .= '</select>';

		if ($return) {
		    return $html;
		} else {
		    echo $html;
		}
	}
	
	function render_combo_from_array_row($label, $name, $data, $default=false, $caption=false) {
		if ($caption) {
			$caption = '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . $caption . '</div>';
		}
		
		$html = '<tr class="' . $this->tr_class . '" style="' . $this->style . '">
				<th>' . $label . $caption . '</th>
				<td style="vertical-align: top;">';
				
		$html .= $this->render_combo_from_array($name, $data, $default, true);
		
		$html .= 	'</td>
			</tr>';

		if ($this->return) {
			return $html;
		}
		echo $html;
	}
}
