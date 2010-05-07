<?php

/*
=====================================================
Navigator Module for ExpressionEngine
-----------------------------------------------------
Build: 20090605
-----------------------------------------------------
Copyright (c) 2005 - 2009 Elwin Zuiderveld
=====================================================
THIS MODULE IS PROVIDED "AS IS" WITHOUT WARRANTY OF
ANY KIND OR NATURE, EITHER EXPRESSED OR IMPLIED,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE,
OR NON-INFRINGEMENT.
=====================================================
File: mcp.navigator.php
-----------------------------------------------------
Purpose: Navigator module - CP
=====================================================
*/

class Navigator_CP {

	var $version = '1.3';
	
	// -------------------------
	//	Constructor
	// -------------------------
	
	function Navigator_CP($switch = TRUE)
	{
		global $IN, $DB;
		
		// Is Module installed?
		if ($IN->GBL('M') == 'INST')
		{
			return;
		}
		
		// Check installed version
		$query = $DB->query("SELECT module_version FROM exp_modules WHERE module_name = 'Navigator'");
		
		if ($query->num_rows == 0)
		{
			return;
		}
		
		// update database if neccesary
		if ($query->row['module_version'] < 1.2)
		{
			$DB->query("ALTER TABLE exp_navigator_data ADD COLUMN pages_uri VARCHAR(1) NOT NULL default 'n'");
		}
		
		// update database if neccesary
		if ($query->row['module_version'] < 1.3)
		{
			$DB->query("ALTER TABLE exp_navigator_groups ADD COLUMN site_id INT(4) NOT NULL default 1");
			$DB->query("ALTER TABLE exp_navigator_data ADD COLUMN site_id INT(4) NOT NULL default 1");
		}
		
		// update version number
		if ($query->row['module_version'] < $this->version)
		{
			$DB->query("UPDATE exp_modules SET module_version = '{$this->version}' WHERE module_name = 'Navigator'");
		}
		// end update
		
		if ($switch)
		{
			switch($IN->GBL('P'))
			{
				case 'list_items'			:	$this->list_items();
					break;
				case 'delete_group'			:	$this->delete_group();
					break;
				case 'delete_group_confirm'	:	$this->delete_group_confirm();
					break;
				case 'update_group'			:	$this->update_group();
					break;
				case 'update_item'			:	$this->update_item();
					break;
				case 'edit_group'			:	$this->edit_group();
					break;
				case 'edit_item'			:	$this->edit_item();
					break;
				case 'delete_item'			:	$this->delete_item();
					break;
				case 'delete_item_confirm'	:	$this->delete_item_confirm();
					break;
				case 'preferences'			:	$this->preferences();
					break;
				case 'documentation'		:	$this->documentation();
					break;
				case 'change_nav_order'		:	$this->change_nav_order();
					break;
				case 'ajax_entry_search'	:	$this->ajax_entry_search();
					break;
				default						:	$this->navigator_home();
					break;
			}
		}
	}
	// END
	
	
	// ----------------------------------------
	//	Module Homepage
	// ----------------------------------------
	
	function navigator_home($msg='')
	{
		global $DB, $DSP, $FNS, $LANG, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// -------------------------------------------------------
		//	Select All Navigator Groups from Database
		// -------------------------------------------------------
		
		$query = $DB->query("SELECT * FROM exp_navigator_groups WHERE site_id = $site_id");
		
		// if ($query->num_rows == 0)
		// {
		// 	$DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_groups'));
		// 	return;
		// }
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title = $LANG->line('ttl_navigator');
		
		$DSP->crumb = $LANG->line('crumb_navigator');
		
		$DSP->right_crumb($LANG->line('crumb_new_group'), BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=edit_group');
		
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('head_group_management'));
		
		// -------------------------------------------------------
		//	Message, if any
		// -------------------------------------------------------
		
		if ($msg != '')
		{
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
		}
		
		// -------------------------------------------------------
		//	Table and Table Headers
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		
		$DSP->body .= $DSP->table_row(array(
											array(
												  'text'	=> $LANG->line('head_id'),
												  'class' => 'tableHeadingAlt',
												  'width' => '5%'
												  ),
											array(
												  'text'	=> $LANG->line('head_group_name'),
												  'class' => 'tableHeadingAlt',
												  'width' => '35%'
												  ),
											array(
												  'text'	=> '&nbsp;',
												  'class' => 'tableHeadingAlt'
												  ),
											array(
												  'text'	=> '&nbsp;',
												  'class' => 'tableHeadingAlt'
												  ),
											array(
												  'text'	=> '&nbsp;',
												  'class' => 'tableHeadingAlt'
												  )
											)
									  );
		
		// -------------------------------------------------------
		//	Display Rows of Navigator Groups
		// -------------------------------------------------------
		
		$query = $DB->query("SELECT * FROM exp_navigator_groups WHERE site_id = $site_id ORDER BY group_id");
		
		$i = 0;
		
		foreach ($query->result as $row)
		{
		
			$items = $DB->query("SELECT COUNT(*) AS count 
								 FROM exp_navigator_data 
								 WHERE site_id = $site_id 
								 AND group_id = '".$row['group_id']."'");
			
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$DSP->body .= $DSP->table_row(array(
												array(
													  'text'	=> $DSP->qspan('defaultBold', $row['group_id']),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('defaultBold', $row['group_name']),
													  'class' => $style
													  ),
												array(
													  'text'	=> '('.$items->row['count'].')'.$DSP->qspan('default', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=list_items'.AMP.'group_id='.$row['group_id'], $LANG->line('link_add_edit_items'))),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('default', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=edit_group'.AMP.'group_id='.$row['group_id'], $LANG->line('link_edit_group'))),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('default', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=delete_group'.AMP.'group_id='.$row['group_id'], $LANG->line('link_delete_group'))),
													  'class' => $style
													  )
												)
										  );
		
		}
		
		// -------------------------------------------------------
		//	Close Table and Output to $DSP->body
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_close();
		
		$DSP->body .= $docs = $DSP->qdiv('box defaultBold', $DSP->anchorpop(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=documentation', $LANG->line('link_documentation'), '750', ''));
		
	}
	// END
	
	// -------------------------
	// Edit Group
	// -------------------------
	
	function edit_group()
	{
		global $DSP, $LANG, $DB, $IN, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// -------------------------------------------------------
		//	Fetch Existing Group Name
		// -------------------------------------------------------
		
		if ( ! $IN->GBL('group_id'))
		{
			$group_name = '';
		}
		else
		{
			$query = $DB->query("SELECT group_name 
								 FROM exp_navigator_groups 
								 WHERE site_id = $site_id 
								 AND group_id = '".$IN->GBL('group_id')."'");
			
			// if ($query->num_rows == 0)
			// {
			// 	$DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_groups'));
			// 	return;
			// }
			
			$group_name = $query->row['group_name'];
		}
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title = $LANG->line('ttl_navigator');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
		if ($group_name != '')
		{
			$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_edit_group'));
		}
		else
		{
			$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_new_group'));
		}
		
		// -------------------------------------------------------
		// Declare Form
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->form('C=modules'.AMP.'M=navigator'.AMP.'P=update_group', 'target');
		
		// -------------------------------------------------------
		//	Modify Group
		// -------------------------------------------------------
		
		if ($group_name != '')
		{
			$DSP->body .= $DSP->input_hidden('group_id', $IN->GBL('group_id'));
			$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('head_edit_group'));
		}
		else
		{
			$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('head_new_group'));
		}
		
		$DSP->body .= $DSP->div('box');
		
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for=\"group_name\">' . $LANG->line('lbl_group_name') . '</label>');
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_text('group_name', $group_name, '20', '60', 'input', '300px', '', TRUE));
		if ($group_name != '')
		{
			$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('bttn_update_group')));
		}
		else
		{
			$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('bttn_add_group')));
		}
		
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->form_c();
		
		$DSP->body .= '<div style="margin-top:3px;">';
		$DSP->body .= $docs = $DSP->qdiv('box defaultBold', $DSP->anchorpop(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=documentation', $LANG->line('link_documentation'), '750', ''));
		$DSP->body .= '</div>';
		
	}
	// END
	
	// -------------------------
	// Create / Update
	// -------------------------
	
	function update_group()
	{
		global $LANG, $DB, $IN, $DSP, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// -------------------------------------------------------
		//	Valid Group Name?
		// -------------------------------------------------------
		
		$group_name = (! isset($_POST['group_name'])) ? '' : trim($_POST['group_name']);
		
		if ($group_name == '')
		{
			$DSP->body .= $DSP->error_message($LANG->line('msg_invalid_group_name'));
			return;
		} 
		
		// -------------------------------------------------------
		//	Insert or Update Depending on ID
		// -------------------------------------------------------
		
		if (! isset($_POST['group_id']))
		{
			$data = array('group_id'	=> '', 
						  'site_id'	=> $site_id, 
						  'group_name'	=> trim($group_name));
			
			$DB->query($DB->insert_string('exp_navigator_groups', $data));
			
			// return message
			return $this->navigator_home($LANG->line('group_added'));
		}
		else
		{
			$data = array('group_name'	=> trim($group_name));
			
			$DB->query($DB->update_string('exp_navigator_groups', $data, "group_id ='".$_POST['group_id']."'"));
			
			// return message
			return $this->navigator_home($LANG->line('msg_group_updated'));
		}
		
		$DSP->body .= $docs = $DSP->qdiv('box defaultBold', $DSP->anchorpop(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=documentation', $LANG->line('link_documentation'), '750', ''));
		
	}
	// END
	
	// -------------------------
	// Create / Update
	// -------------------------
	
	function update_item()
	{
		global $LANG, $DB, $IN, $DSP, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// -------------------------------------------------------
		//	Valid Group Name?
		// -------------------------------------------------------
		
		$nav_title = (! isset($_POST['nav_title'])) ? '' : trim($_POST['nav_title']);
		$nav_url_segments = (! isset($_POST['nav_url_segments'])) ? '' : trim($_POST['nav_url_segments']);
		$nav_custom_url = (! isset($_POST['nav_custom_url'])) ? '' : trim($_POST['nav_custom_url']);
		$nav_entry_id = (! isset($_POST['nav_entry_id'])) ? '' : trim($_POST['nav_entry_id']);
		$category_id = (! isset($_POST['category_id'])) ? '' : trim($_POST['category_id']);
		$template_id = (! isset($_POST['template_id'])) ? '' : trim($_POST['template_id']);
		$nav_properties = (! isset($_POST['nav_properties'])) ? '' : trim($_POST['nav_properties']);
		$nav_description = (! isset($_POST['nav_description'])) ? '' : trim($_POST['nav_description']);
		$pages_uri = (! isset($_POST['pages_uri'])) ? '' : trim($_POST['pages_uri']);
		
		// -------------------------------------------------------
		//	Insert or Update Depending on ID
		// -------------------------------------------------------
		
		if (! isset($_POST['nav_id']))
		{
			$query = $DB->query("SELECT nav_order 
								 FROM exp_navigator_data 
								 WHERE site_id = $site_id 
								 AND group_id = '".$IN->GBL('group_id')."' 
								 ORDER BY nav_order desc LIMIT 1");
			
			if ($query->num_rows == 0)
			{
				$order = '1';
			}
			else
			{
				$order = $query->row['nav_order']+1;
			}
			
			$data = array('nav_id'	=> '', 
						  'site_id'	=> $site_id, 
						  'group_id'	=> $IN->GBL('group_id'),
						  'nav_title'	=> trim($nav_title),
						  'nav_title_length'	=> strlen(trim($nav_title)),
						  'category_id'	=> $category_id,
						  'template_id'	=> $template_id,
						  'nav_entry_id'	=> $nav_entry_id,
						  'nav_url_segments'	=> trim($nav_url_segments),
						  'nav_custom_url'	=> trim($nav_custom_url),
						  'nav_properties'	=> $nav_properties,
						  'nav_description'	=> $nav_description,
						  'nav_order'	=> $order,
						  'pages_uri'	=> $pages_uri
						  );
			
			$DB->query($DB->insert_string('exp_navigator_data', $data));
			
			// return message
			return $this->list_items($LANG->line('item_added'));
		}
		else
		{
			$data = array('nav_title'	=> trim($nav_title),
						  'nav_title_length'	=> strlen(trim($nav_title)),
						  'group_id'	=> $IN->GBL('group_id'),
						  'category_id'	=> $category_id,
						  'template_id'	=> $template_id,
						  'nav_entry_id'	=> $nav_entry_id,
						  'nav_url_segments'	=> trim($nav_url_segments),
						  'nav_custom_url'	=> trim($nav_custom_url),
						  'nav_properties'	=> $nav_properties,
						  'nav_description'	=> $nav_description,
						  'pages_uri'	=> $pages_uri
						  );
			
			$DB->query($DB->update_string('exp_navigator_data', $data, "nav_id ='".$_POST['nav_id']."'"));
			
			// return message
			return $this->list_items($LANG->line('msg_item_updated'));
		}
	
		$DSP->body .= $docs = $DSP->qdiv('box defaultBold', $DSP->anchorpop(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=documentation', $LANG->line('link_documentation'), '750', ''));
	
	}
	// END
	
	// -------------------------
	// Delete Group
	// -------------------------
	
	function delete_group()
	{
		global $LANG, $DB, $IN, $DSP, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		$query = $DB->query("SELECT group_name 
							 FROM exp_navigator_groups 
							 WHERE site_id = $site_id 
							 AND group_id = '".$IN->GBL('group_id')."'");
		
		if ($query->num_rows == 0) {
			$DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_groups'));
			return;
		}
		
		$DSP->title .= $LANG->line('ttl_navigator');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_delete_group'));
		
		$group_name = $query->row['group_name'];
		
		$DSP->body .= $DSP->form('C=modules'.AMP.'M=navigator'.AMP.'P=delete_group_confirm', 'target');
		$DSP->body .= $DSP->input_hidden('group_id', $IN->GBL('group_id'));
		
		$DSP->body .= $DSP->qdiv('alertHeading', $LANG->line('head_delete_group') . ' "' . $group_name . '"'); 
		
		$DSP->body .= $DSP->div('box');
		
		$DSP->body .= $DSP->qdiv('itemWrapper', '<b>' . $LANG->line('msg_delete_group_confirm') . '</b>');
		
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('highlight_alt', $group_name);
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('alert', $LANG->line('msg_delete_ALERT'));
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('bttn_delete_group')));
		
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->form_c();
	
	}
	// END
	
	// -------------------------
	// Delete Item
	// -------------------------
	
	function delete_item()
	{
		global $LANG, $DB, $IN, $DSP, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		$query = $DB->query("SELECT nav_title 
							 FROM exp_navigator_data 
							 WHERE site_id = $site_id 
							 AND nav_id = '".$IN->GBL('nav_id')."'");
		
		if ($query->num_rows == 0) {
			$DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_items'));
			return;
		}
		
		$DSP->title .= $LANG->line('ttl_navigator');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
		$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_delete_item'));
		
		$nav_title = $query->row['nav_title'];
		
		$DSP->body .= $DSP->form('C=modules'.AMP.'M=navigator'.AMP.'P=delete_item_confirm'.AMP.'group_id='.$IN->GBL('group_id').AMP.'nav_id='.$IN->GBL('nav_id'), 'target');
		
		$DSP->body .= $DSP->qdiv('alertHeading', $LANG->line('head_delete_item') . ' "' . $nav_title . '"');
		
		$DSP->body .= $DSP->div('box');
		
		$DSP->body .= $DSP->qdiv('itemWrapper', '<b>' . $LANG->line('msg_delete_item_confirm') . '</b>');
		
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('highlight_alt', $nav_title);
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('alert', $LANG->line('msg_delete_ALERT'));
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('bttn_delete_item')));
		
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->form_c();
			
	}
	// END
	
	// -------------------------
	// Add/edit Items
	// -------------------------
	
	function list_items($msg='')
	{
		global $FNS, $IN, $DB, $DSP, $LANG, $PREFS;
		
		$seg = '';
		$pages	= $PREFS->ini('site_pages');
		$uris	= $pages['uris'];
		$page_uri = '';
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// -------------------------------------------------------
		//	Select All Navigator Groups from Database
		// -------------------------------------------------------
		
		$query = $DB->query("SELECT group_name 
							 FROM exp_navigator_groups 
							 WHERE site_id = $site_id 
							 AND group_id = '".$IN->GBL('group_id')."'");
		
		if ($query->num_rows == 0)
		{
			// $DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_groups'));
			// $DSP->body .= $DSP->error_message( $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator') );
			// $DSP->body .= $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
			
			$return = BASE.AMP.'C=modules'.AMP.'M=navigator';
			$FNS->redirect($return);
			
			return;
		}
		
		$group_name = $query->row['group_name'];
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title .= $LANG->line('ttl_navigator');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
		
		$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_item_management'));
		
		$DSP->right_crumb($LANG->line('crumb_create_new_item'), BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=edit_item'.AMP.'group_id='.$IN->GBL('group_id'));
		
		$DSP->body .= $DSP->qdiv('tableHeading', $group_name);
		
		// -------------------------------------------------------
		//	Message, if any
		// -------------------------------------------------------
		
		if ($msg != '')
		{
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
		}
		
		// -------------------------------------------------------
		//	Table and Table Headers
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		
		$DSP->body .= $DSP->table_row(array(
											array(
												  'text'	=> $LANG->line('head_id'),
												  'class' => 'tableHeadingAlt',
												  'width' => '2%'
												  ),
											array(
												  'text'	=> $LANG->line('head_order'),
												  'class' => 'tableHeadingAlt',
												  'width' => '8%'
												  ),
											array(
												  'text'	=> $LANG->line('head_item_title'),
												  'class' => 'tableHeadingAlt',
												  'width' => '20%'
												  ),
											array(
												  'text'	=> $LANG->line('head_url'),
												  'class' => 'tableHeadingAlt',
												  'width' => '60%'
												  ),
											array(
												  'text'	=> $LANG->line('head_edit'),
												  'class' => 'tableHeadingAlt',
												  'width' => '5%'
												  ),
											array(
												  'text'	=> $LANG->line('head_delete'),
												  'class' => 'tableHeadingAlt',
												  'width' => '5%'
												  )
											)
									  );
		
		// -------------------------------------------------------
		//	Display Rows of Navigator Groups
		// -------------------------------------------------------
		
		$query = $DB->query("SELECT * 
							 FROM exp_navigator_data 
							 WHERE site_id = $site_id 
							 AND group_id = '".$IN->GBL('group_id')."' 
							 ORDER BY nav_order");
		
		$i = 0;
		
		$templates_query = $DB->query("SELECT exp_template_groups.group_name,
									   exp_template_groups.group_id,
									   exp_templates.template_name,
									   exp_templates.template_id 
									   FROM exp_template_groups, exp_templates 
									   WHERE exp_template_groups.group_id = exp_templates.group_id 
									   AND exp_template_groups.is_user_blog = 'n' 
									   AND exp_template_groups.site_id = $site_id 
									   ORDER BY exp_template_groups.group_order,
									   exp_templates.template_name");
		
		$templates = array();
		foreach($templates_query->result as $row)
		{
			$templates[$row['group_id'].'.'.$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
		}
		
		// -------------------------------------------------------
		//	Fetch Categories
		// -------------------------------------------------------
		
		$c = '';
		foreach($query->result as $row)
		{
			if($row['category_id'] != '0') {
				$c = 'y';
				// saves another 2 queries when not using Categories
				break;
			}
		}
		
		if($c == 'y') {
			// get templates
			$categories_query = $DB->query("SELECT cat_id, cat_url_title, cat_name 
											FROM exp_categories 
											WHERE site_id = $site_id ");
			
			$categories = array();
			$cat_url_titles = array();
			foreach($categories_query->result as $row)
			{
				$categories[$row['cat_id']] = $row['cat_name'];
				$cat_url_titles[$row['cat_id']] = $row['cat_url_title'];
			}
		}
		
		// -------------------------------------------------------
		//	Fetch url_titles
		// -------------------------------------------------------
		
		$entry_ids = '0,';
		foreach($query->result as $row)
		{
			if($row['nav_entry_id'] != '0') {
				$entry_ids .= $row['nav_entry_id'].',';
			}
		}
		$entry_ids = substr($entry_ids, 0, -1);
		
		if($entry_ids != '0') {
			$titles_query = $DB->query("SELECT entry_id, url_title 
										FROM exp_weblog_titles 
										WHERE site_id = $site_id 
										AND entry_id IN (".$entry_ids.")");
			
			$titles = array();
			foreach($titles_query->result as $row)
			{
				$titles[$row['entry_id']] = $row['url_title'];
			}
		}
		
		//end
		
		foreach ($query->result as $row)
		{
			// print_r($row);
			
			// Custom URL
			if($row['nav_custom_url'] != '') {
				// we're done, show custom URL
				$url = $row['nav_custom_url'];
			}
			else if ($row['pages_uri'] == 'y' && array_key_exists($row['nav_entry_id'],$uris))
			{
				$url = $FNS->create_url($uris[$row['nav_entry_id']], 1, 0);
			}
			else
			{
				// reset url
				$url = '';
				
				// Template_group / Template
				if ($row['template_id'] != '0') {
					if (array_key_exists($row['template_id'], $templates)) {
						$url = $templates[$row['template_id']].'/';
					}
				}
				
				// Catgory URL
				if ($row['category_id'] != '0') {
					$t_url = explode('.', $row['category_id']);
					$cat_name = '';
					if (array_key_exists($t_url['1'], $categories)) {
						$cat_name = $categories[$t_url['1']];
						$cat_url_title = $cat_url_titles[$t_url['1']];
						// $cond['nav_category'] = ($cat_name == '0') ? 'FALSE' : $cat_name;
					}
					
					// $cat_name = $categories[$t_url['1']];
					if ($PREFS->ini('use_category_name') == 'y') {
						// Cat Name
						$url .= $PREFS->ini('reserved_category_word').'/'.$cat_url_title;
					}
					else
					{
						$url .= 'C'.$t_url['1'];
					}
				
				}
				else
				{
					
					
					// Entry Title
					if ($row['nav_entry_id'] != '0') {
						if (array_key_exists($row['nav_entry_id'], $titles)) {
							$url .= '/'.$titles[$row['nav_entry_id']].'/';
						}
					}
					
					// URL Segments
					if ($row['nav_url_segments'] != '') {
						$url .= '/';
						if ($row['nav_url_segments']{0} == '/') {
							$seg = substr($row['nav_url_segments'], 1);
							//$seg = $row['nav_url_segments'];
						}
						else
						{
							$seg = $row['nav_url_segments'];
						}
					}
					else
					{
						$seg = '';
					}
					
				}
				
				if ($url != '') $url = $FNS->create_url($url).$seg;
				
			}
			
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$DSP->body .= $DSP->table_row(array(
												array(
													  'text'	=> $DSP->qspan('default', $row['nav_id']),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=change_nav_order'.AMP.'group_id='.$row['group_id'].AMP.'nav_id='.$row['nav_id'].AMP.'order=up', '<img src="'.PATH_CP_IMG.'arrow_up.gif" border="0"  width="16" height="16" alt="" title="" />').'&nbsp;'.$DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=change_nav_order'.AMP.'group_id='.$row['group_id'].AMP.'nav_id='.$row['nav_id'].AMP.'order=down', '<img src="'.PATH_CP_IMG.'arrow_down.gif" border="0"  width="16" height="16" alt="" title="" />')),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('defaultBold', $row['nav_title']),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('defaultBold',$DSP->anchor($url, $url, '', TRUE)),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('default', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=edit_item'.AMP.'group_id='.$row['group_id'].AMP.'nav_id='.$row['nav_id'], $LANG->line('link_edit_item'))),
													  'class' => $style
													  ),
												array(
													  'text'	=> $DSP->qspan('default', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=delete_item'.AMP.'group_id='.$row['group_id'].AMP.'nav_id='.$row['nav_id'], $LANG->line('link_delete_item'))),
													  'class' => $style
													  )
												)
											);
			
		}
		
		// -------------------------------------------------------
		//	Close Table and Output to $DSP->body
		// -------------------------------------------------------
		
		$DSP->body	.=	$DSP->table_close();
		
		$DSP->body .= $docs = $DSP->qdiv('box defaultBold', $DSP->anchorpop(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=documentation', $LANG->line('link_documentation'), '750', ''));
		
	}
	// END
	
	//--------------------------------------
	// Change Category Order
	//--------------------------------------
	
	function change_nav_order()
	{
		global $DB, $FNS, $DSP, $IN, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// Return Location
		$return = BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=list_items'.AMP.'group_id='.$IN->GBL('group_id');
		$query = $DB->query("SELECT nav_id 
							 FROM exp_navigator_data 
							 WHERE site_id = $site_id 
							 AND group_id = '".$IN->GBL('group_id')."' 
							 ORDER BY nav_order asc");
		
		if ($query->row['nav_id'] == $IN->GBL('nav_id') && $IN->GBL('order') != 'down')
		{
			return $this->list_items('');
			exit;
		}
		
		$flag	= '';
		$i		= 1;
		$items	= array();
		
		foreach ($query->result as $row)
		{
			if ($IN->GBL('nav_id') == $row['nav_id'])
			{
				$flag = ($IN->GBL('order') == 'down') ? $i+1 : $i-1;
			}
			else
			{
				$items[] = $row['nav_id'];
			}
			$i++;
		}
		
		array_splice($items, ($flag -1), 0, $IN->GBL('nav_id'));
		
		// Update order
		
		$i = 1;
		
		foreach ($items as $val)
		{
			$DB->query("UPDATE exp_navigator_data SET nav_order = '$i' WHERE nav_id = '$val'");
			$i++;
		}
		
		$FNS->redirect($return);
		exit;
	}
	// END
	
	// -------------------------
	// Edit Item
	// -------------------------
	
	function edit_item()
	{
		global $DSP, $LANG, $DB, $IN, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		// -------------------------------------------------------
		//	If Modfying Existing , Retrieve Text
		// -------------------------------------------------------
		
		if ( ! $IN->GBL('nav_id'))
		{
			$nav_id = '';
			$group_id = $IN->GBL('nav_id');
			$nav_title = '';
			$nav_custom_url = '';
			$category_id = '';
			$template_id = '';
			$nav_entry_id = '';
			$nav_url_segments = '';
			$nav_properties = '';
			$nav_description = '';
			$nav_pages_uri = 'n';
		}
		else
		{
			$query = $DB->query("SELECT *
								 FROM exp_navigator_data 
								 WHERE site_id = $site_id 
								 AND group_id = '".$IN->GBL('group_id')."'
								 AND nav_id = '".$IN->GBL('nav_id')."'");
			
			if ($query->num_rows == 0)
			{
				$DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_items'));
				return;
			}
			
			$nav_id = $query->row['nav_id'];
			$group_id = $query->row['group_id'];
			
			$nav_title = $query->row['nav_title'];
			
			$category_id = $query->row['category_id'];
			$template_id = $query->row['template_id'];
			
			$nav_entry_id = $query->row['nav_entry_id'];
			
			$nav_url_segments = $query->row['nav_url_segments'];
			
			$nav_custom_url = $query->row['nav_custom_url'];
			
			$nav_properties = $query->row['nav_properties'];
			$nav_description = $query->row['nav_description'];
			
			$nav_order = $query->row['nav_order'];
			
			$nav_pages_uri = $query->row['pages_uri'];
		}
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title = $LANG->line('navigator_module_name');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
		if ($nav_id != '')
		{
			$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_edit_item'));
		}
		else
		{
			$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_create_new_item'));
		}
		
		// -------------------------------------------------------
		// Declare Form
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->form('C=modules'.AMP.'M=navigator'.AMP.'P=update_item'.AMP.'group_id='.$IN->GBL('group_id'), 'target');
		
		// -------------------------------------------------------
		//	Modifying Existing Item
		// -------------------------------------------------------
		
		if ($nav_id != '')
		{
			$DSP->body .= $DSP->input_hidden('nav_id', $IN->GBL('nav_id')); 
			$DSP->body .= $DSP->input_hidden('group_id', $IN->GBL('group_id')); 
			
			$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('head_edit_item')); 
		}
		else
		{
			$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('head_create_new_item')); 
		}
		
		$DSP->body .= $DSP->div('box');
		
		//field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="nav_title">' . $LANG->line('lbl_item_title') . '</label>');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_text('nav_title', $nav_title, '20', '60', 'input', '400px', '', TRUE));
		//field end
		
		// templates
		$sql = "SELECT exp_template_groups.group_name, 
				exp_template_groups.group_id, 
				exp_templates.template_name, 
				exp_templates.template_id 
				FROM exp_template_groups, exp_templates 
				WHERE exp_template_groups.group_id = exp_templates.group_id 
				AND exp_template_groups.site_id = $site_id ";
		
		/*
		 if ($user_blog == TRUE)
		 {
			 $sql .= " AND exp_template_groups.group_id = '".$SESS->userdata['tmpl_group_id']."'";
		 }
		 else
		 {
			 $sql .= " AND exp_template_groups.is_user_blog = 'n'";
		 }
		 */
		
		$sql .= " AND exp_template_groups.is_user_blog = 'n'";
		$sql .= " ORDER BY exp_template_groups.group_order, exp_templates.template_name";
		
		$query = $DB->query($sql);
		
		// Template field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="template_id">' . $LANG->line('lbl_template') . '</label>');
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->div('itemWrapper');
		//$DSP->body .= $DSP->input_select_header('template_id');
		$DSP->body .= '<select name="template_id" class="select" style="width:200px;">';
		
		$DSP->body .= $DSP->input_select_option('0', '');
		$DSP->body .= '<optgroup label="'.$LANG->line('lbl_templates').'">'.NL;
		foreach ($query->result as $row)
		{
			$t = $row['group_name'].'/'.$row['template_name'];
			$t_opt = $row['group_id'].'.'.$row['template_id'];
			
			if ($t_opt === $template_id) {
				$DSP->body .= $DSP->input_select_option($t_opt, $t, 'y');
			}
			else
			{
				$DSP->body .= $DSP->input_select_option($t_opt, $t);
			}
		}
		
		$DSP->body .= '</optgroup>'.NL;
		$DSP->body .= $DSP->input_select_footer();
		$DSP->body .= $DSP->div_c();
		//field end
		
		// categories
		$cat_sql = "SELECT exp_categories.group_id, 
					exp_categories.cat_id, 
					exp_category_groups.group_name, 
					exp_categories.cat_name, 
					exp_categories.cat_url_title, 
					exp_categories.cat_order 
					FROM exp_categories, exp_category_groups
					WHERE exp_category_groups.group_id = exp_categories.group_id 
					AND exp_category_groups.is_user_blog = 'n' 
					AND exp_category_groups.site_id = $site_id 
					ORDER BY group_name DESC";
		
		$cat_query = $DB->query($cat_sql);
		
		//field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="category_id">' . $LANG->line('lbl_category') . '</label>');
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->div('itemWrapper');
		//$DSP->body .= $DSP->input_select_header('category_id');
		$DSP->body .= '<select name="category_id" class="select" style="width:200px;">';

		$DSP->body .= $DSP->input_select_option('0', '');
		
		$new_group_id = '';
		$old_group_id = '';
		$i = 0;
		
		foreach ($cat_query->result as $row)
		{
			$new_group_id = $row['group_id'];
			if($new_group_id != $old_group_id) {
				if ($i > 0) $DSP->body .= '</optgroup>'.NL;
				$DSP->body .= '<optgroup label="'.$row['group_name'].'">'.NL;
				$old_group_id = $new_group_id;
				$i++;
			}
			
			$t = $row['cat_name'];
			$t_opt = $row['group_id'].'.'.$row['cat_id'];
			if ($t_opt == $category_id) {
				$DSP->body .= $DSP->input_select_option($t_opt, $t, 'y');
			}
			else
			{
				$DSP->body .= $DSP->input_select_option($t_opt, $t);
			}
			
		}
		
		$DSP->body .= '</optgroup>'.NL;
		$DSP->body .= $DSP->input_select_footer();
		$DSP->body .= $DSP->div_c();
		//categories end
		
		// ENTRIES
		
		$sql = "SELECT exp_weblog_titles.url_title, 
				exp_weblog_titles.entry_id, 
				exp_weblog_titles.title, 
				exp_weblog_titles.weblog_id,
				exp_weblog_titles.status,
				exp_weblogs.blog_title 
				FROM exp_weblog_titles, exp_weblogs 
				WHERE exp_weblog_titles.weblog_id = exp_weblogs.weblog_id 
				AND exp_weblogs.site_id = $site_id 
				ORDER BY exp_weblogs.blog_title, exp_weblog_titles.title";
		// add LIMIT as user pref?
		$query = $DB->query($sql);
		
		$search_link = BASE.'&C=modules&M=navigator&P=ajax_entry_search';
		
		// messages for javascript
		$head_search_keywords = $LANG->line('head_search_keywords');
		$msg_browser_not_supported = $LANG->line('msg_browser_not_supported');
		$msg_error_encountered = $LANG->line('msg_error_encountered');
		$msg_searching = $LANG->line('msg_searching');
		
		$javascript = <<<EOT

<script type="text/javascript">

function search_entries()
{
	var keywords = document.forms['target'].keyword.value;
	var status = document.forms['target'].nav_status_filter.value;
	
	//if ( ! keywords || keywords == null)
	//	return; 
	
	// So begins the AJAX
	// Wheee!
	
	try
	{
		XMLHttp = new ActiveXObject("Msxml2.XMLHTTP");
	}
	catch (e)
	{
		try
		{
			XMLHttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		catch (e2)
		{
			XMLHttp = null;
		}
	}
	
	if( ! XMLHttp && typeof XMLHttpRequest != "undefined")
	{
		XMLHttp = new XMLHttpRequest();
	}
	
	if ( ! XMLHttp)
	{
		alert('$msg_browser_not_supported'); return false;
	}
	
	var data =  "keywords=" + encodeURIComponent(keywords) + 
				"&status=" + encodeURIComponent(status) + 
				"&XID={XID_SECURE_HASH}";
	
	XMLHttp.onreadystatechange = function () { entry_search_results(); }
	XMLHttp.open("POST", "{$search_link}", true);
	XMLHttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	XMLHttp.setRequestHeader("Connection", "close");
	//alert(data);
	XMLHttp.send(data);
}

function entry_search_results()
{
	if (XMLHttp.readyState == 1)
	{
		document.getElementById("progress").style.display = "inline";
		document.getElementById('feedback').innerHTML = "$msg_searching";
	}
	if (XMLHttp.readyState == 4)
	{
		document.getElementById("progress").style.display = "none";
		document.getElementById('feedback').innerHTML = "";
		
		document.getElementById('nav_entry_id_block').innerHTML = (XMLHttp.status == 200) ? XMLHttp.responseText : "$msg_error_encountered" + XMLHttp.status;
		
		document.forms["target"].keyword.focus();
		document.forms["target"].keyword.select();
		//document.getElementById("searchBox").style.display = "none";
	}
}

</script>
	
EOT;
		
		// Entry Title field start
		$DSP->body .= $javascript.$DSP->div('itemWrapper');
		
		$search_link = $DSP->anchor('#', $LANG->line('link_filter_entries'), 'onclick="document.getElementById(\'searchBox\').style.display=\'block\';document.forms[\'target\'].keyword.focus();document.forms[\'target\'].keyword.select();return false;"');
		
		$progress_img = '<img id="progress" align="absmiddle" style="display:none;" src="modules/navigator/images/progress.gif" />';
		
		if (! file_exists('modules/')) {
			$progress_img = '<span id="progress"></span>';
		}
		
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="nav_entry_id">' . $LANG->line('lbl_entry_title') . '</label>'.NBS.' ['.$search_link.']'.NBS.NBS.$progress_img.NBS.'<span id="feedback" style="font-weight:normal;"></span>');
		$DSP->body .= $DSP->div_c();
		
		$status_sql = "SELECT es.status, es.group_id, eg.group_name 
					   FROM exp_statuses es, exp_status_groups eg 
					   WHERE es.site_id = $site_id 
					   AND eg.site_id = $site_id 
					   AND es.group_id = eg.group_id 
					   ORDER BY eg.group_id, es.status_order";
		
		$status_query = $DB->query($status_sql);
		
		$DSP->body .= '<div class="profileMenuInner defaultBold" id="searchBox" style="display:none;border:1px solid #B1B6D2; width: 378px;padding:10px;margin:7px 0px;"><table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>';
		$DSP->body .= $DSP->input_text('keyword', '', '20', '255', 'input', '178px', '', TRUE);
		$DSP->body .= '</td><td align="center"><select name="nav_status_filter" class="select" style="width: 130px;">';
		$DSP->body .= $DSP->input_select_option('all', 'Filter by Status');
		$new_group = '';
		$old_group = '';
		$g = 0;
		foreach ($status_query->result as $row)
		{
			$new_group = $row['group_name'];
			if($new_group != $old_group) {
				if ($g > 0) $DSP->body .= '</optgroup>'.NL;
				$DSP->body .= '<optgroup label="'.$row['group_name'].'">'.NL;
				$old_group = $new_group;
				$g++;
			}
			$DSP->body .= $DSP->input_select_option($row['status'], $row['status']);
		}
		$DSP->body .= '</optgroup>'.NL;
		$DSP->body .= $DSP->input_select_footer();
		$DSP->body .= '&nbsp;</td><td align="right">';
		$DSP->body .= $DSP->anchor('javascript:void(0);', $LANG->line('link_search_entries'), 'onclick="search_entries();return false;"');
		$DSP->body .= '</td></tr></table>';
		$DSP->body .= $DSP->div_c();
				
		$DSP->body .= $DSP->div('itemWrapper','','nav_entry_id_block');
		//$DSP->body .= $DSP->input_select_header('nav_entry_id');
		$DSP->body .= '<select name="nav_entry_id" class="select" style="width:200px;">';
		
		$DSP->body .= $DSP->input_select_option('', '');
		
		$new_weblog = '';
		$old_weblog = '';
		$n = 0;
		
		foreach ($query->result as $row)
		{
			$new_weblog = $row['blog_title'];
			if($new_weblog != $old_weblog) {
				if ($n > 0) $DSP->body .= '</optgroup>'.NL;
				$DSP->body .= '<optgroup label="'.$row['blog_title'].'">'.NL;
				$old_weblog = $new_weblog;
				$n++;
			}
			
			if ($row['entry_id'] == $nav_entry_id) {
				$DSP->body .= $DSP->input_select_option(''.$row['entry_id'].'', $row['title'], 'y');
			}
			else
			{
				$DSP->body .= $DSP->input_select_option(''.$row['entry_id'].'', $row['title']);
			}
			
		}
		
		$DSP->body .= '</optgroup>'.NL;
		
		$DSP->body .= $DSP->input_select_footer();
		//$DSP->body .= NBS.NBS.'('.count($query->result).')';
		$DSP->body .= $DSP->div_c();
		//field end
		
		
		// Entry Title field start
		$DSP->body .= $javascript.$DSP->div('itemWrapper');
		$checked = 0;
		if ($nav_pages_uri == 'y') $checked = 1;
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="pages_uri">' . $DSP->input_checkbox('pages_uri', 'y', $checked) . $LANG->line('lbl_pages_page') . '</label>');
		$DSP->body .= $DSP->div_c();
		//field end
		
		
		// URL Segments field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('default', '<label for="nav_url_segments">' . '<b>'.$LANG->line('lbl_url_segments').'</b> '.$LANG->line('msg_url_segments').'</label>');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_text('nav_url_segments', $nav_url_segments, '20', '255', 'input', '400px', '', TRUE));
		//field end
		
		// Custom URL field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('default', '<label for="nav_custom_url">' . '<b>'.$LANG->line('lbl_custom_url').'</b> '.$LANG->line('msg_custom_url').'</label>');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_text('nav_custom_url', $nav_custom_url, '20', '255', 'input', '400px', '', TRUE));
		//field end
		
		// Properties field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="nav_properties">' . $LANG->line('lbl_properties') . '</label>');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_text('nav_properties', $nav_properties, '20', '255', 'input', '400px', '', TRUE));
		//field end
		
		// Description field start
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= $DSP->qdiv('defaultBold', '<label for="nav_description">' . $LANG->line('lbl_description') . '</label>');
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->qdiv('itemWrapper', $DSP->input_textarea('nav_description', $nav_description, '4', 'textarea', '400px'));
		//field end
		
		if ($nav_title != '')
		{
			$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('bttn_update_item')));
		}
		else
		{
			$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit($LANG->line('bttn_add_item')));
		}
		
		$DSP->body .= $DSP->div_c();
		$DSP->body .= $DSP->form_c();
		
		$DSP->body .= '<div style="margin-top:3px;">';
		$DSP->body .= $docs = $DSP->qdiv('box defaultBold', $DSP->anchorpop(BASE.AMP.'C=modules'.AMP.'M=navigator'.AMP.'P=documentation', $LANG->line('link_documentation'), '750', ''));
		$DSP->body .= '</div>';
	}
	// END
	
	// ----------------------------------------
	//	Ajax Entry Search
	// ----------------------------------------
	
	function ajax_entry_search()
	{
		global $DB, $DSP, $REGX, $IN, $PREFS, $LANG;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		$keywords = ($IN->GBL('keywords', 'POST') !== FALSE) ? $REGX->keyword_clean($IN->GBL('keywords', 'POST')) : '';
		$filter_status = ($IN->GBL('status', 'POST') !== FALSE) ? $REGX->keyword_clean($IN->GBL('status', 'POST')) : '';
				
		if ($PREFS->ini('auto_convert_high_ascii') == 'y') {
			$keywords = $REGX->ascii_to_entities($keywords);
		}
		
		//if ($keywords == '') exit('<div class="highlight">'.$LANG->line('msg_no_results').'</div>');
		
		$sql = "SELECT et.entry_id, et.title, et.status, ew.blog_title
							 FROM exp_weblog_titles et, exp_weblogs ew 
							 WHERE et.weblog_id = ew.weblog_id 
							 AND ew.site_id = $site_id ";
		
		if ($keywords != '') {
			$sql .= "AND et.title LIKE '%".$DB->escape_str($keywords)."%' ";
		}
				
		if ($filter_status != 'all') {
			$sql .= "AND et.status = '$filter_status' ";
		}
		
		$sql .= "ORDER BY ew.blog_title, et.title";
		
		$query = $DB->query($sql);
		
		// fixes the IE Win -1072896658 error AND the Safari encoding bug! :-/
		@header("Content-type: text/html; charset=UTF-8");
		// Thank you Paul!
		
		//if ($query->num_rows == 0) exit('<div class="highlight">'.$LANG->line('msg_no_matches').' <b>"'.$REGX->ascii_to_entities($keywords).'"</b></div>');
		if ($query->num_rows == 0) exit('<div class="highlight">'.$LANG->line('msg_no_matches').' <b>"'.$keywords.'"</b></div>');
		
		//$r = $DSP->input_select_header('nav_entry_id');
		$r = '<select name="nav_entry_id" class="select" style="width:200px;">';
		
		$i = 0;
		$new_blog = '';
		$old_blog = '';
		
		foreach($query->result as $row)
		{
			$new_blog = $row['blog_title'];
			if($new_blog != $old_blog) {
				if ($i > 0) $r .= '</optgroup>'.NL;
				$r .= '<optgroup label="'.$row['blog_title'].'">'.NL;
				$old_blog = $new_blog;
				$i++;
			}
			$r .= $DSP->input_select_option($row['entry_id'], $row['title']);
		}
		
		$r .= $DSP->input_select_footer();
		$r .= NBS.NBS.'('.count($query->result).')';
		
		exit($r);
	}
	// END
		
	// -------------------------
	// Delete Item Confirm
	// -------------------------
	
	function delete_item_confirm()
	{
		global $LANG, $DB, $IN, $DSP;
		
		// -------------------------------------------------------
		//	 ID is required
		// -------------------------------------------------------
		
		if ( ! $IN->GBL('nav_id'))
		{
			return;
		}
		else
		{
			$query = $DB->query("DELETE FROM exp_navigator_data
								 WHERE nav_id = '".$IN->GBL('nav_id')."'");
			
			if ($DB->affected_rows == 0)
			{
				$DSP->body .= $DSP->error_message($LANG->line('err_no_navigator_items'));
				return;
			}
			
		}
		
		// -------------------------------------------------------
		//	Return to View	 with Success Message
		// -------------------------------------------------------
		
		return $this->list_items($LANG->line('msg_item_deleted'));
		
	}
	// END
	
	// -------------------------
	// Delete Group Confirm
	// -------------------------
	
	function delete_group_confirm()
	{
		global $LANG, $DB, $IN, $DSP;
		
		// -------------------------------------------------------
		//	 ID is required
		// -------------------------------------------------------
		
		if ( ! $IN->GBL('group_id'))
		{
			return;
		}
		else
		{
			
			$sql[] = "DELETE FROM exp_navigator_groups
					  WHERE group_id = '".$IN->GBL('group_id')."'";
			
			$sql[] = "DELETE FROM exp_navigator_data 
					  WHERE group_id = '".$IN->GBL('group_id')."'";
			
			foreach ($sql as $query)
			{
				$DB->query($query);
			}
			
		}
		
		// -------------------------------------------------------
		//	Return to Navigator Home
		// -------------------------------------------------------
		
		return $this->navigator_home($LANG->line('msg_group_deleted'));
	}
	// END
	
	// -------------------------
	// Preferences
	// -------------------------
	
	function preferences()
	{
		global $DSP, $DB, $LANG;
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title = $LANG->line('ttl_navigator');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=navigator',$LANG->line('crumb_navigator'));
		
		$DSP->crumb .= $DSP->crumb_item($LANG->line('crumb_preferences'));
		
		$DSP->body .= $DSP->div('box');
		
		$DSP->body .= $DSP->div('itemWrapper');
		$DSP->body .= '<b>Preferences</b> <span style="color:red">(not yet implemented)</span>'.BR.BR;
		$DSP->body .= '- Setting to limit number of entries in Entry Title drop-down, sort by entry_date'.BR;
		$DSP->body .= $DSP->div_c();
		
		$DSP->body .= $DSP->div_c();
		
	}
	// END
	
	// -------------------------
	// Documentation page
	// -------------------------
	
	function documentation()
	{
		global $DSP, $PREFS, $FNS, $LANG;
		
		$path = 'modules/navigator/documentation/index.html';
		
		if (! file_exists($path)) {
			// we might be using 'admin.php' to access system folder
			$rel_path = '../'.$PREFS->ini('system_folder') . '/' . $path;
			
			// check relative path to docs
			if (file_exists($rel_path)) {
				$docs = fopen($rel_path, 'r');
				
				while(!feof($docs))
				{
					$output = fgets($docs, 4096);
					echo $output;
				}
				
				fclose($docs);
				exit;
			}
			else
			{
				// couldn't find docs, sorry
				exit ($DSP->initial_body = '<p>' . $LANG->line('err_loading_docs') . '</p>'); 
			}
		}
		else
		{
			// all ok
			$docs = fopen($path, 'r');
			
			while(!feof($docs))
			{
				$output = fgets($docs, 4096);
				echo $output;
			}
			
			fclose($docs);
			exit;
		}
	
	}
	// END
	
	// ----------------------------------------
	//	Module installer
	// ----------------------------------------
	
	function navigator_module_install()
	{
		global $DB;
		
		$sql[] = "INSERT INTO exp_modules (module_id, 
										   module_name, 
										   module_version, 
										   has_cp_backend) 
										  VALUES 
										  ('', 
										   'Navigator', 
										   '$this->version', 
										   'y')";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_navigator_groups` (`group_id` INT(6) UNSIGNED NOT NULL AUTO_INCREMENT,
																	 `site_id` int(4) unsigned NOT NULL default 1,
																	 `group_name` VARCHAR(50) NOT NULL ,
																	 PRIMARY KEY (`group_id`));";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_navigator_data` (`nav_id` INT(4) UNSIGNED NOT NULL AUTO_INCREMENT,
																	`group_id` INT(4) UNSIGNED NOT NULL,
																	`site_id` int(4) unsigned NOT NULL default 1,
																	`nav_title` VARCHAR(255) NOT NULL,
																	`nav_title_length` INT(4) NOT NULL,
																	`category_id` VARCHAR(10) NOT NULL,
																	`template_id` VARCHAR(10) NOT NULL,
																	`nav_entry_id` INT(10) UNSIGNED NOT NULL,
																	`nav_url_segments` VARCHAR(255) NOT NULL,
																	`nav_custom_url` VARCHAR(255) NOT NULL,
																	`nav_properties` VARCHAR(255) NOT NULL,
																	`nav_description` TEXT NOT NULL,
																	`nav_order` INT(4) UNSIGNED NOT NULL,
																	`pages_uri` VARCHAR(1) NOT NULL default 'n',
																	PRIMARY KEY (`nav_id`));";
		
		// Add default group to exp_navigator_groups
		$sql[] = "INSERT INTO exp_navigator_groups (group_id, 
													site_id, 
													group_name) 
													VALUES 
													('', 
													 1, 
													'Default Navigator Group')";
		
		// Add temp nav item to exp_navigator_data
		$sql[] = "INSERT INTO exp_navigator_data (nav_id, 
												site_id,
												group_id,
												nav_title,
												nav_title_length,
												category_id,
												template_id,
												nav_custom_url,
												nav_properties,
												nav_description,
												nav_order) 
												VALUES 
												('',
												 1,
												'1',
												'ExpressionEngine',
												'8',
												'0',
												'0',
												'http://expressionengine.com',
												'target=\"_blank\"',
												'Publish Your Universe!',
												'1')";
		
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
	
	// ----------------------------------------
	//	Module de-installer
	// ----------------------------------------
	
	function navigator_module_deinstall()
	{
		global $DB;
		
		$query = $DB->query("SELECT module_id
							 FROM exp_modules 
							 WHERE module_name = 'Navigator'"); 
		
		$sql[] = "DELETE FROM exp_module_member_groups 
				  WHERE module_id = '".$query->row['module_id']."'";
		
		$sql[] = "DELETE FROM exp_modules 
				  WHERE module_name = 'Navigator'";
		
		$sql[] = "DELETE FROM exp_actions 
				  WHERE class = 'Navigator'";
		
		$sql[] = "DELETE FROM exp_actions 
				  WHERE class = 'Navigator_CP'";
		
		$sql[] = "DROP TABLE IF EXISTS exp_navigator_groups";
		
		$sql[] = "DROP TABLE IF EXISTS exp_navigator_data";
		
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
	
}
// END CLASS
?>