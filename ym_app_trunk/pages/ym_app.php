<?php

define('YM_PPP_ADMIN_URL', YM_ADMIN_INDEX_URL . '&ym_page=ym-hook-ym_app');

function ymfire_admin_page() {
	global $wpdb, $firetypes, $saletypes, $ym_formgen;
	$firesale = ym_request('firesale');
	
	$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	
		echo '<div class="wrap" id="poststuff">';
		$fire_id = ym_request('fire_id');
		$tier_id = ym_request('tier_id');
		
		if ($firesale == 'toggle') {
			$firesale = '';
			if ($fire_id) {
				$sql = 'SELECT fire_enable FROM ' . $wpdb->ym_app_models . ' WHERE fire_id = ' . $fire_id;
				$enabled = $wpdb->get_var($sql);
				$enabled = $enabled ? 0 : 1;
				$sql = 'UPDATE ' . $wpdb->ym_app_models . ' SET fire_enable = ' . $enabled . ' WHERE fire_id = ' . $fire_id;
				$wpdb->query($sql);
			}
		} else if ($firesale == 'delete') {
			$firesale = '';
			if ($fire_id) {
				$wpdb->query('DELETE FROM ' . $wpdb->ym_app_models . ' WHERE fire_id = ' . $fire_id);
				$wpdb->query('DELETE FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_id = ' . $fire_id);
			}
			if ($tier_id) {
				$wpdb->query('DELETE FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_tier_id = ' . $tier_id);
			}
		} else if ($firesale == 'enable') {
			// enabling a tier
			$sql = 'SELECT * FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_tier_id = ' . $tier_id;
			$thetier = $wpdb->get_results($sql);
			$thetier = $thetier[0];
			
			$fire_id = $thetier->fire_id;
			$tier_order = $thetier->fire_order;
			
			// reoder (order by)
			$tiers = array();
			foreach (ym_firesale_get_all_tiers($fire_id) as $tier) {
				$tiers[$tier->fire_order] = $tier;
			}

			unset($tiers[$tier_order]);
			array_unshift($tiers, $thetier);

			foreach ($tiers as $order => $tier) {
				$sql = 'UPDATE ' . $wpdb->ym_app_models_tiers . ' SET fire_order = ' . $order . ' WHERE fire_tier_id = ' . $tier->fire_tier_id;
				$wpdb->query($sql);
			}
			
			echo '<p>Tier Order was updated, if the Pricing Model is not active, it has not been activated yet</p>';
			
			$firesale = '';
		}
		
		if ($firesale == 'newtier') {
			$fire_id = ym_post('ym_firesale_add_post_tiers_fire_id');
			$fire_id = str_replace('firesale_', '', $fire_id);
			
			// put to end
			$sql = 'SELECT fire_order FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_id = ' . $fire_id . ' ORDER BY fire_order DESC LIMIT 1';
			$order = $wpdb->get_var($sql);
			if ($order) {
				$order++;
			} else {
				$order = 0;
			}
			
			$fire_tiers = array();
			
			$limit_bys = ym_post('ym_new_firesale_limit_by');
			$limit_sales = ym_post('ym_new_firesale_limit_sales');
			
			$limit_dates = ym_post('ym_date_ym_new_firesale_limit_time');
			$limit_months = ym_post('ym_month_ym_new_firesale_limit_time');
			$limit_years = ym_post('ym_year_ym_new_firesale_limit_time');
			$limit_hours = ym_post('ym_hour_ym_new_firesale_limit_time');
			$limit_mins = ym_post('ym_min_ym_new_firesale_limit_time');
			
			$limit_by_hours = ym_post('ym_new_firesale_limit_hours');
			
			foreach (ym_post('ym_new_firesale_price') as $tier_price) {
				$fire_tiers[] = array(
					'tier_price'		=> $tier_price,
					'tier_limit_by'		=> $limit_bys[0],
					'tier_limit_sales'	=> $limit_sales[0],
					'tier_limit_time'	=> strtotime($limit_dates[0] . ' ' . $months[$limit_months[0]] . ' ' . $limit_years[0] . ' ' . $limit_hours[0] . ':' . $limit_mins[0]),
					'tier_limit_hours'	=> $limit_by_hours[0],
				);
			}
			foreach ($fire_tiers as $tier) {
				$limit_var = 0;
				
				switch($tier['tier_limit_by']) {
					case 2:
						// hours
						$limit_by = 2;
						$limit_var = $tier['tier_limit_hours'];
						break;
					case 1:
						// time
						$limit_by = 1;
						$limit_var = $tier['tier_limit_time'];
						break;
					case 0:
					default:
						//sales
						$limit_by = 0;
						$limit_var = $tier['tier_limit_sales'];
				}

				$sql = 'INSERT INTO ' . $wpdb->ym_app_models_tiers . '(fire_id, fire_price, fire_limit_by, fire_limit_var, fire_order)
					VALUES (
						' . $fire_id . ',
						\'' . $tier['tier_price'] . '\',
						' . $limit_by . ',
						' . $limit_var . ',
						' . $order . '
					)';
				$wpdb->query($sql);

				echo ym_start_box('Pricing Models');
				if ($wpdb->insert_id) {
					echo '<div class="message" id="success"><p>Tier Added</p></div>';
				} else {
					echo '<div class="message" id="error"><p>Tier was not added</p></div>';
				}
				echo ym_end_box();
			}
			echo '<meta http-equiv="refresh" content="3;' . YM_PPP_ADMIN_URL . '" />';
		}
		
		if ($firesale == 'edit') {
			echo ym_start_box('Pricing Models');
			
			$tier_data = 'SELECT * FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_tier_id = ' . $tier_id;
			$tier_data = $wpdb->get_results($tier_data);
			if ($tier_data[0]) {
				$tier_data = $tier_data[0];
				//editing a tier
				echo '<form action="" method="post">
					<fieldset>';
				
				echo '
<input type="hidden" name="firesale" value="edittier" />
<input type="hidden" name="tier_id" value="' . $tier_id . '"/>
';
				
				echo '<table>';

				$ym_formgen->render_form_table_text_row('Price', 'ym_new_firesale_price[]', $tier_data->fire_price, 'Price for this Tier');
				$ym_formgen->render_combo_from_array_row('Limit By', 'ym_new_firesale_limit_by[]', $saletypes, $tier_data->fire_limit_by, 'What kind of Tier');
				$ym_formgen->render_form_table_text_row('Limit By Sales', 'ym_new_firesale_limit_sales[]', (!$tier_data->fire_limit_by ? $tier_data->fire_limit_var : ''), 'Tier ends after this many sales of this Tier');
				$ym_formgen->render_form_table_datetime_picker_row('Limit By Time', 'ym_new_firesale_limit_time[]', ($tier_data->fire_limit_by ? $tier_data->fire_limit_var : ''), 'Tier ends at this date');

				$ym_formgen->render_form_table_text_row('Hours', 'ym_new_firesale_limit_hours[]', ($tier_data->fire_limit_by == 2 ? $tier_data->fire_limit_var : ''), 'Expire Tier this many hours after it starts');
				echo '<tr><td colspan="5" style="border-top: 1px solid grey;">&nbsp;</td></tr>';
				
				echo '</table>';
				echo '
				<p class="submit" style="text-align: right;">
					<input type="submit" value="' . __('Update Pricing Model Tier','ym') . ' &raquo;" />
				</p>';
				echo '</fieldset></form>';
			} else {
				echo '<p>Could not find that Tier</p>';
				$firesale = '';
			}
			echo ym_end_box();
		}
		if ($firesale == 'edittier') {
			$fire_tiers = array();
			
			$limit_bys = ym_post('ym_new_firesale_limit_by');
			$limit_sales = ym_post('ym_new_firesale_limit_sales');
			
			$limit_dates = ym_post('ym_date_ym_new_firesale_limit_time');
			$limit_months = ym_post('ym_month_ym_new_firesale_limit_time');
			$limit_years = ym_post('ym_year_ym_new_firesale_limit_time');
			$limit_hours = ym_post('ym_hour_ym_new_firesale_limit_time');
			$limit_mins = ym_post('ym_min_ym_new_firesale_limit_time');
			
			$limit_by_hours = ym_post('ym_new_firesale_limit_hours');
			
			foreach (ym_post('ym_new_firesale_price') as $tier_price) {
				$fire_tiers[] = array(
					'tier_price'		=> $tier_price,
					'tier_limit_by'		=> $limit_bys[0],
					'tier_limit_sales'	=> $limit_sales[0],
					'tier_limit_time'	=> strtotime($limit_dates[0] . ' ' . $months[$limit_months[0]] . ' ' . $limit_years[0] . ' ' . $limit_hours[0] . ':' . $limit_mins[0]),
					'tier_limit_hours'	=> $limit_by_hours[0],
				);
			}
			foreach ($fire_tiers as $tier) {
				$limit_var = 0;
				
				switch($tier['tier_limit_by']) {
					case 2:
						// hours
						$limit_by = 2;
						$limit_var = $tier['tier_limit_hours'];
						break;
					case 1:
						// time
						$limit_by = 1;
						$limit_var = $tier['tier_limit_time'];
						break;
					case 0:
					default:
						//sales
						$limit_by = 0;
						$limit_var = $tier['tier_limit_sales'];
				}

				$data = array(
					'fire_price'		=> $tier['tier_price'],
					'fire_limit_by'		=> $limit_by,
					'fire_limit_var'	=> $limit_var
				);
				$wpdb->update($wpdb->ym_app_models_tiers, $data, array('fire_tier_id' => $tier_id));
				echo ym_start_box('Pricing Models');
				if ($wpdb->rows_affected) {
					echo '<p>Tier Updated</p>';
				} else {
					echo '<p>Tier was not updated</p>';
				}
				echo ym_end_box();
			}
			echo '<meta http-equiv="refresh" content="3;' . YM_PPP_ADMIN_URL . '" />';
		}
		
		if (empty($firesale)) {
			echo '<div style="width: 43%; float: left;">';
			echo ym_start_box('Pricing Models');

			$firesales = ym_firesale_get_all();
			echo '<h2>All Pricing Models</h2>';
			echo '<p>Click the Link to enable/disable</p>';

			echo '<table style="width: 100%;">';
			echo '<tr><th>Pricing Model Name</th><th>Type</th><th>Tiers</th><th>Enabled</th><th>Edit</th><th>Delete</th></tr>';
			foreach ($firesales as $firesale) {
				if ($firesale->fire_id) {
					echo '<tr>';
					echo '<td>(' . $firesale->fire_id . ')' . $firesale->fire_name . '</td>';
					echo '<td style="text-align: center;">' . $firetypes[$firesale->fire_type] . '</td>';
					echo '<td style="text-align: center;"><a href="#showtiers" class="ym_showtiers" id="tiers_' . $firesale->fire_id . '">' . $firesale->tiers . '</a></td>';
					echo '<td style="text-align: center;"><a href="' . YM_PPP_ADMIN_URL . '&firesale=toggle&fire_id=' . $firesale->fire_id . '">' . ($firesale->fire_enable ? 'Yes' : 'No') . '</a></td>';
//					echo '<td style="text-align: center;"><a href="admin.php?page=' . YM_ADMIN_DIR . 'ym-index.php&ym_page=ym-other&action=ym_app&firesale=edit&fire_id=' . $firesale->fire_id . '">E</a></td>';
					echo '<td style="text-align: center;"><a href="#addtier" class="ym_firesale_add_post_tier" id="firesale_' . $firesale->fire_id . '">Add Tier</a></td>';
					echo '<td style="text-align: center;"><a href="' . YM_PPP_ADMIN_URL . '&firesale=delete&fire_id=' . $firesale->fire_id . '">X</a></td>';
					echo '</tr>';
					
					foreach (ym_firesale_get_all_tiers($firesale->fire_id) as $tier) {
						echo '<tr style="display: none;" class="tiers_' . $firesale->fire_id . '">';
						echo '<td style="text-align: center;">ID: ' . $tier->fire_tier_id . ' Tier: ' . $tier->fire_order . '</td>';
						echo '<td style="text-align: center;">' . $saletypes[$tier->fire_limit_by] . '(' . ($tier->fire_limit_by == 1 ? date('r', $tier->fire_limit_var) : $tier->fire_limit_var) . ')</td>';
						echo '<td></td>';
//						echo '<td style="text-align: center;">' . ($tier->fire_tier_started ? 'Yes' : '<a href="admin.php?page=' . YM_ADMIN_DIR . 'ym-index.php&ym_page=ym-other&action=ym_app&firesale=enable&tier_id=' . $tier->fire_tier_id . '">No</a>') . '</td>';
						echo '<td style="text-align: center;">' . ($tier->fire_tier_started ? 'Yes' : 'No') . '</td>';
						echo '<td style="text-align: center;"><a href="' . YM_PPP_ADMIN_URL . '&firesale=edit&tier_id=' . $tier->fire_tier_id . '">E</a></td>';
						echo '<td style="text-align: center;"><a href="' . YM_PPP_ADMIN_URL . '&firesale=delete&tier_id=' . $tier->fire_tier_id . '">X</a></td>';
						echo '</tr>';
					}
				}
			}
			echo '</table>';

			echo ym_end_box();
			echo '</div>';

			echo '<div style="width: 55%; float: right;">';
			echo ym_start_box('New Pricing Model');

			echo '
<form action="" method="post">
	<fieldset>
		<legend>Create a new Pricing Model</legend>
		<input type="hidden" name="firesale" value="new" />
		<table class="form-table">
		';
		
			$ym_formgen->render_form_table_text_row('Pricing Model Name', 'ym_new_firesale_name', '', 'A handy name to Remember');
			$ym_formgen->render_combo_from_array_row('Pricing Model Type', 'ym_new_firesale_type', $firetypes, '', 'What type of Pricing Model');

			echo '
		</table>
		<p class="submit" style="text-align: right;">
			<input type="submit" value="' . __('Create Pricing Model','ym') . ' &raquo;" />
		</p>
	</fieldset>
</form>';

			echo ym_end_box();
			echo '</div>';
		} else if ($firesale == 'new') {
			echo ym_start_box('Pricing Model');
			echo '<form action="" method="post">
				<fieldset>
					<input type="hidden" name="firesale" value="create" />
			';

			$firesale = array(
				'fire_name'		=> ym_post('ym_new_firesale_name') ? ym_post('ym_new_firesale_name') : 'pricing_model_' . date('dMY_His', time()),
				'fire_type'		=> ym_post('ym_new_firesale_type'),
			);
			
			echo '<p>Creating a new Pricing Model: <strong>' . $firesale['fire_name'] . '</strong> of Type: <strong>' . $firetypes[$firesale['fire_type']] . '</strong></p>';
			
			echo '<input type="hidden" name="ym_new_firesale_name" value="' . $firesale['fire_name'] . '" />';
			echo '<input type="hidden" name="ym_new_firesale_type" value="' . $firesale['fire_type'] . '" />';
			
			echo '<table class="form-table">';
			
			switch($firesale['fire_type']) {
				case 2:
					// pppp pack
					$packs = array();
					foreach (ym_get_ppp_packs() as $pack) {
						$packs[$pack->id] = $pack->name . ' (' . number_format($pack->cost, 2) . ')';
					}
					$ym_formgen->render_combo_from_array_row('Post Pack', 'ym_new_firesale_target_id', $packs, '', 'Which post pack to apply this Model to?');
					$ym_formgen->render_form_table_radio_row('End Sale', 'ym_end_firesale_ppp', 0, 'Take Post Pack off Sale after Last Tier');
					break;
				case 1:
					// subsc
					$packs = array();
					foreach (ym_get_packs() as $pack) {
						$packs[$pack['id']] = $pack['account_type'] . ' (' . $pack['duration'] . ' ' . $pack['duration_type'] . ') ' . $pack['cost'];
					}
					$ym_formgen->render_combo_from_array_row('Pack', 'ym_new_firesale_target_id', $packs, '', 'Which pack to apply this Model to?');
					break;
				case 0:
				default:
					// get all ppp's
					$posts = array();
					foreach (ym_get_all_ppp_posts() as $post) {
						$posts[$post->ID] = $post->post_title;
					}
					$ym_formgen->render_combo_from_array_row('Post', 'ym_new_firesale_target_id', $posts, '', 'Which post to apply this Model to?');
					$ym_formgen->render_form_table_radio_row('End Sale', 'ym_end_firesale_ppp', 0, 'Take Post off Sale after Last Tier');

					break;
			}

			echo '<tr><td colspan="2"><p>Tiers</p></td></tr>';
			echo '<tr><td><a href="#addtier" id="ym_firesale_addtier">Click to Add Tier</a></td></tr>';
			
			echo '</table>';
			echo '<p class="submit" style="text-align: right;">
				<input type="submit" value="' . __('Create Pricing Model','ym') . ' &raquo;" />
			</p>';
			
			echo '</fieldset></form>';
			
			echo ym_end_box();
		} else if ($firesale == 'create') {
			echo ym_start_box('Pricing Models');
			$firesale = array(
				'fire_name'		=> ym_post('ym_new_firesale_name') ? ym_post('ym_new_firesale_name') : 'pricing_model_' . date('dMY_His', time()),
				'fire_type'		=> ym_post('ym_new_firesale_type'),
				
				'fire_type_id'	=> ym_post('ym_new_firesale_target_id'),
				
				'fire_end_action'	=> (ym_post('ym_end_firesale_ppp')) ? ym_post('ym_end_firesale_ppp') : 0
			);
			// get tiers
			$fire_tiers = array();

			$limit_bys = ym_post('ym_new_firesale_limit_by');
			$limit_sales = ym_post('ym_new_firesale_limit_sales');
			
			$limit_dates = ym_post('ym_date_ym_new_firesale_limit_time');
			$limit_months = ym_post('ym_month_ym_new_firesale_limit_time');
			$limit_years = ym_post('ym_year_ym_new_firesale_limit_time');
			$limit_hours = ym_post('ym_hour_ym_new_firesale_limit_time');
			$limit_mins = ym_post('ym_min_ym_new_firesale_limit_time');
			
			$limit_by_hours = ym_post('ym_new_firesale_limit_hours');
			
			foreach (ym_post('ym_new_firesale_price') as $key => $tier_price) {
				$fire_tiers[] = array(
					'tier_price'		=> $tier_price,
					'tier_limit_by'		=> $limit_bys[$key],
					'tier_limit_sales'	=> $limit_sales[$key],
					'tier_limit_time'	=> strtotime($limit_dates[$key] . ' ' . $months[$limit_months[$key]] . ' ' . $limit_years[$key] . ' ' . $limit_hours[$key] . ':' . $limit_mins[$key]),
					'tier_limit_hours'	=> $limit_by_hours[$key],
				);
			}
			
			// DATABASE!
			$sql = 'INSERT INTO ' . $wpdb->ym_app_models . '(fire_name, fire_type, fire_type_id, fire_end_option) VALUES (\'' . $firesale['fire_name'] . '\', \'' . $firesale['fire_type'] . '\', \'' . $firesale['fire_type_id'] . '\', \'' . $firesale['fire_end_action'] . '\')';
			$wpdb->query($sql);
			if (FALSE !== $firesale_id = $wpdb->insert_id) {
				echo '<p>Added the Pricing Models ' . $firesale['fire_name'];
				// tiers
				foreach ($fire_tiers as $key => $tier) {
					$limit_var = 0;
					
					switch($tier['tier_limit_by']) {
						case 2:
							// hours
							$limit_by = 2;
							$limit_var = $tier['tier_limit_hours'];
							break;
						case 1:
							// time
							$limit_by = 1;
							$limit_var = $tier['tier_limit_time'];
							break;
						case 0:
						default:
							//sales
							$limit_by = 0;
							$limit_var = $tier['tier_limit_sales'];
					}

					$sql = 'INSERT INTO ' . $wpdb->ym_app_models_tiers . '(fire_id, fire_price, fire_limit_by, fire_limit_var, fire_order)
						VALUES (
							' . $firesale_id . ',
							\'' . $tier['tier_price'] . '\',
							' . $limit_by . ',
							' . $limit_var . ',
							' . $key . '
						)';
						$wpdb->query($sql);

						if ($wpdb->insert_id) {
							echo '<br />Tier Added';
						}
				}
				echo '</p>';
				echo '<meta http-equiv="refresh" content="3;' . YM_PPP_ADMIN_URL . '" />';
			} else {
				echo '<p>There was a problem adding the Pricing Model: ' . $firesale['fire_name'] . '</p>';
			}
			echo ym_end_box();
		} else {
			// bad firesale function
		}
				
		echo '
		<div id="ym_firesale_add_post_tiers_form" style="display: none; clear: both;">';
		echo ym_start_box('Adding a Tier');
		echo '
		<form method="post" action="">
			<fieldset>
				<legend>Add a Tier to the selected Pricing Model</legend>
				<input type="hidden" name="ym_firesale_add_post_tiers_fire_id" id="ym_firesale_add_post_tiers_fire_id" />
				<input type="hidden" name="firesale" value="newtier" />
				
				<table id="ym_firesale_add_post_tiers">
				</table>
				
				<p class="submit" style="text-align: right;">
					<input type="submit" value="' . __('Add New Pricing Model Tier','ym') . ' &raquo;" />
				</p>
			</fieldset>
		</form>
		';
		echo ym_end_box();
		echo '
		</div>';
		
		echo '</div>';
		
		echo '<table id="ym_firesale_tier_source">';
		ym_fire_tier_form();
		echo '</table>';
}

function ym_fire_tier_form() {
	global $ym_formgen, $saletypes;
	
	$ym_formgen->render_form_table_text_row('Price', 'ym_new_firesale_price[]', '5.00', 'Price for this Tier');
	$ym_formgen->render_combo_from_array_row('Limit By', 'ym_new_firesale_limit_by[]', $saletypes, 'sales', 'What kind of Tier');
	$ym_formgen->render_form_table_text_row('Limit By Sales', 'ym_new_firesale_limit_sales[]', 10, 'Tier ends after this many sales of this Tier');
	$ym_formgen->render_form_table_datetime_picker_row('Limit By Time', 'ym_new_firesale_limit_time[]', '', 'Tier ends at this date');
	$ym_formgen->render_form_table_text_row('Hours', 'ym_new_firesale_limit_hours[]', '12', 'Expire Tier this many hours after it starts');
	echo '<tr><td colspan="5" style="border-top: 1px solid grey;">&nbsp;</td></tr>';
}
