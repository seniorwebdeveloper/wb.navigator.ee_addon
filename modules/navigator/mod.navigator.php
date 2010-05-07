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
File: mod.navigator.php
-----------------------------------------------------
Purpose: Navigator module
=====================================================
*/

if ( ! defined('EXT'))
{
	exit('Invalid file request');
}

class Navigator {

	var $return_data = '';
	
	// -------------------------------------
	//	Constructor
	// -------------------------------------
	
	function Navigator()
	{
		global $SESS, $DB, $TMPL, $FNS, $PREFS;
		
		$site_id = $DB->escape_str($PREFS->ini('site_id'));
		
		$nav_category_word = $PREFS->ini('reserved_category_word');
		$count = '1';
		
		// -------------------------------------------------------
		//	Navigator Tag Parameters
		// -------------------------------------------------------
		
		$group_id	= ($TMPL->fetch_param('group'))		? $TMPL->fetch_param('group')		: '1';
		$orderby	= ($TMPL->fetch_param('orderby'))	? $TMPL->fetch_param('orderby')		: 'nav_order';
		$sort		= ($TMPL->fetch_param('sort'))		? $TMPL->fetch_param('sort')		: 'asc';
		$style		= ($TMPL->fetch_param('style'))		? $TMPL->fetch_param('style')		: 'linear';
		
		if ($sort != 'asc' && $sort != 'desc') $sort = 'asc';
		
		// make array of all fields and loop thru to check set value.
		$fields = array('nav_id',
						'nav_title',
						'nav_title_length',
						'template_id',
						'nav_entry_id',
						'nav_custom_url',
						'nav_properties',
						'nav_description',
						'nav_order',
						'pages_uri');
		
		if (! in_array($orderby, $fields) && $orderby != 'nav_template') {
			$orderby = 'nav_order';
		}
		
		if ($orderby == 'nav_template') {
			$orderby = 'template_id';
		}
		
		// -------------------------------------------------------
		//	Fetch all data from Navigator Group
		// -------------------------------------------------------
		
		// ADD dreamscape
		if (is_numeric($group_id)) {
			$group_id = (int)$group_id;
			$sql = "SELECT * FROM exp_navigator_data 
								 WHERE site_id = $site_id 
								 AND group_id = $group_id 
								 ORDER BY $orderby $sort";
			$query = $DB->query($sql);
		} else {
			$group_title = $DB->escape_str($group_id);
			$query = $DB->query("SELECT nd.* FROM 
		 						 exp_navigator_data nd, 
		 						 exp_navigator_groups ng 
								 WHERE nd.site_id = $site_id 
								 AND ng.site_id = $site_id 
								 AND ng.group_name = '$group_title' 
								 AND ng.group_id = nd.group_id 
								 ORDER BY $orderby $sort");
		}
		// END
		
		if ($query->num_rows == 0)
		{
			return;
		}
		
		// -------------------------------------------------------
		//	Check too see what queries to run
		// -------------------------------------------------------
		
		$c = '';
		$t = '';
		$entry_ids = array();
		
		foreach($query->result as $row)
		{
			// saves a query when not using Categories
			if($row['category_id'] != '0') {
				$c = 'y';
			}
			
			// saves another query when not using templates
			if($row['template_id'] != '0') {
				$t = 'y';
			}
			
			// saves another query when not using titles
			if($row['nav_entry_id'] != '0') {
				$entry_ids[] = $row['nav_entry_id'];
			}
		}
		
		// -------------------------------------------------------
		//	Fetch Categories
		// -------------------------------------------------------
		
		if($c == 'y') {
			$categories_query = $DB->query("SELECT cat_id, cat_url_title, cat_name 
											FROM exp_categories");
			
			$categories = array();
			$cat_url_titles = array();
			foreach($categories_query->result as $row)
			{
				$categories[$row['cat_id']] = $row['cat_name'];
				$cat_url_titles[$row['cat_id']] = $row['cat_url_title'];
			}
		}
		
		// -------------------------------------------------------
		//	Fetch Templates
		// -------------------------------------------------------
		
		if($t == 'y') {
			$templates_query = $DB->query("SELECT exp_template_groups.group_name, 
										   exp_template_groups.group_id, 
										   exp_templates.template_name, 
										   exp_templates.template_id 
										   FROM exp_template_groups, exp_templates 
										   WHERE exp_template_groups.site_id = $site_id 
										   AND exp_templates.site_id = $site_id 
										   AND exp_template_groups.group_id = exp_templates.group_id 
										   AND exp_template_groups.is_user_blog = 'n' 
										   ORDER BY exp_template_groups.group_order, 
										   exp_templates.template_name");
			
			$templates = array();
			foreach($templates_query->result as $row)
			{
				$templates[$row['group_id'].'.'.$row['template_id']] = $row['group_name'].'/'.$row['template_name'];
			}
		}
		
		// -------------------------------------------------------
		//	Fetch url_titles
		// -------------------------------------------------------
		
		if(count($entry_ids) > 0) {
			$titles_query = $DB->query("SELECT entry_id, url_title 
										FROM exp_weblog_titles 
										WHERE site_id = $site_id 
										AND entry_id 
										IN ('" . implode("','", $entry_ids) . "')");
						
			$titles = array();
			foreach($titles_query->result as $row)
			{
				$titles[$row['entry_id']] = $row['url_title'];
			}
		}
		
		// -------------------------------------------------------
		//	Find and Replace Variables in Tag Data
		// -------------------------------------------------------
		
		$i = 1;
		foreach($query->result as $row)
		{
			
			$url = '';
			$seg = '';
			$tagdata = $TMPL->tagdata;
			
			$pages	= $PREFS->ini('site_pages');
			$uris	= $pages['uris'];
			$page_uri = '';
			
			// -------------------------------------------------------
			//	Prep Conditionals
			// -------------------------------------------------------
			
			// add dreamscape
			$cond['count'] = $i;
			// END
			
			$cond['nav_title'] = ($row['nav_title'] == '0') ? 'FALSE' : $row['nav_title'];
			// Fur the nav_url we can only use the {if nav_url} conditional, {if nav_url == "bla"} won't work.
			$cond['nav_url'] = ($row['nav_custom_url'] == '' && $row['template_id'] == '0' && $row['category_id'] == '0' && $row['nav_entry_id'] == '0' && $row['nav_url_segments'] == '') ? 'FALSE' : 'TRUE';
			$cond['nav_properties'] = ($row['nav_properties'] == '') ? 'FALSE' : $row['nav_properties'];
			$cond['nav_description'] = ($row['nav_description'] == '') ? 'FALSE' : $row['nav_description'];
			
			$cond['nav_id'] = ($row['nav_id'] == '0') ? 'FALSE' : $row['nav_id'];
			$cond['nav_url_segments'] = ($row['nav_url_segments'] == '') ? 'FALSE' : $row['nav_url_segments'];
			
			if ($row['template_id'] != '0') {
				if (array_key_exists($row['template_id'], $templates)) {
					$cond['nav_template'] = ($templates[$row['template_id']] == '0') ? 'FALSE' : $templates[$row['template_id']];
				}
			}
			else
			{
				$cond['nav_template'] = 'FALSE';
			}
			if ($row['category_id'] != '0') {
				$cat_id = explode('.', $row['category_id']);
				
				if (array_key_exists($cat_id['1'], $categories)) {
					$cat_name = $categories[$cat_id['1']];
					$cond['nav_category'] = ($cat_name == '0') ? 'FALSE' : $cat_name;
					$cat_url_title = $categories[$cat_id['1']];
					$cond['nav_cat_url_title'] = ($cat_url_title == '0') ? 'FALSE' : $cat_url_title;
				}
			}
			else
			{
				$cond['nav_category'] = 'FALSE';
				$cond['nav_cat_url_title'] = 'FALSE';
			}
			$cond['nav_category_id'] = ($row['category_id'] == '0') ? 'FALSE' : $row['category_id'];
			
			if ($row['nav_entry_id'] != '0') {
				if (array_key_exists($row['nav_entry_id'], $titles)) {
					$cond['nav_url_title'] = ($titles[$row['nav_entry_id']] == '0') ? 'FALSE' : $titles[$row['nav_entry_id']];
				}
			}
			else
			{
				$cond['nav_url_title'] = 'FALSE';
			}
			$cond['nav_entry_id'] = ($row['nav_entry_id'] == '0') ? 'FALSE' : $row['nav_entry_id'];
			// 0 ?
			$cond['nav_title_length'] = ($row['nav_title_length'] == '0') ? 'FALSE' : $row['nav_title_length'];
			$cond['nav_order'] = ($row['nav_order'] == '0') ? 'FALSE' : $row['nav_order'];
			
			// not sure this one is needed, taken from example in docs
			$tagdata = $FNS->prep_conditionals($tagdata, $cond);
			// end conditionals
			
			foreach ($TMPL->var_single as $key)
			{
			
				switch ($key)
				{
					case 'switch':
					
					// ----------------------------------------
					//	 parse {switch} variable
					// ----------------------------------------
					
					$param = $TMPL->fetch_param('switch');
					$sw = '';
					
					if (isset($param['switch']))
					{
						$sopt = explode("|", $param);
						if (count($sopt) == 2)
						{
							if (isset($switch[$param['switch']]) AND $switch[$param['switch']] == $sopt['0'])
							{
								$switch[$param['switch']] = $sopt['1'];
								$sw = $sopt['1'];
							}
							else
							{
								$switch[$param['switch']] = $sopt['0'];
								$sw = $sopt['0'];
							}
						}
					}
					
					$tagdata = $TMPL->swap_var_single($key, $sw, $tagdata);
					break;
					
					// ----------------------------------------
					//	 parse other variables
					// ----------------------------------------
					
					case 'nav_url':
						
						
						
						// Custom URL
						if ($row['nav_custom_url'] != '') {
							$url = $row['nav_custom_url'];
							$tagdata = $TMPL->swap_var_single($key, $url, $tagdata);
							// nav_custom_url, Template and Entry Title will be ignored, break
							break;
						}
						
						// Pages URL
						if ($row['pages_uri'] == 'y' && array_key_exists($row['nav_entry_id'],$uris)) {
							$url = $FNS->create_url($uris[$row['nav_entry_id']], 1, 0);
							$tagdata = $TMPL->swap_var_single($key, $url, $tagdata);
							break;
						}
						
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
							$cat_url_title = '';
							
							if (array_key_exists($cat_id['1'], $categories)) {
								$cat_name = $categories[$cat_id['1']];
								$cond['nav_category'] = ($cat_name == '0') ? 'FALSE' : $cat_name;
								$cat_url_title = $cat_url_titles[$cat_id['1']];
								$cond['nav_cat_url_title'] = ($cat_url_title == '0') ? 'FALSE' : $cat_url_title;
							}
							
							if ($PREFS->ini('use_category_name') == 'y') {
								// Cat Name
								$url .= $PREFS->ini('reserved_category_word').'/'.$cat_url_title;
							}
							else
							{
								$url .= 'C'.$t_url['1'];
							}
							$url = $FNS->create_url($url);
							$tagdata = $TMPL->swap_var_single($key, $url, $tagdata);
							break;
						}
						
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
							}
							else
							{
								$seg = $row['nav_url_segments'];
							}
						}
						
						if ($url != '') $url = $FNS->create_url($url).$seg;
						$tagdata = $TMPL->swap_var_single($key, $url, $tagdata);
						break;
						
					case 'nav_url_title':
						// url_title
						if ($row['nav_entry_id'] != '0') {
							$url = $titles[$row['nav_entry_id']];
							$tagdata = $TMPL->swap_var_single($key, $url, $tagdata);
							$url = '';
						}
						else
						{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
						break;
					
					case 'nav_url_segments':
						// url_title
						if ($row['nav_url_segments'] != '') {
							$tagdata = $TMPL->swap_var_single($key, $row['nav_url_segments'], $tagdata);
						}
						else
						{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
						break;
						
					case 'nav_template':
						// url_title
						if ($row['template_id'] != '0') {
							$tmpl = $templates[$row['template_id']];
							$tagdata = $TMPL->swap_var_single($key, $tmpl, $tagdata);
						}
						else
						{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
						break;
						
					case 'nav_category':
						// nav_category
						if ($row['category_id'] != '0') {
							$t_url = explode('.', $row['category_id']);
							$cat_name = $categories[$t_url['1']];
							$tagdata = $TMPL->swap_var_single($key, $cat_name, $tagdata);
						}
						else
						{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
						break;
						
					case 'nav_category_id':
						// nav_category_id
						if ($row['category_id'] != '0') {
							$t_url = explode('.', $row['category_id']);
							$cat_id = $t_url['1'];
							$tagdata = $TMPL->swap_var_single($key, $cat_id, $tagdata);
						}
						else
						{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
						break;
						
					case 'nav_category_word':
						// nav_category_word
						$tagdata = $TMPL->swap_var_single($key, $nav_category_word, $tagdata);
						break;
						
					case 'nav_group_id':
						if ($row['group_id'] != '0') {
							$tagdata = $TMPL->swap_var_single($key, $row['group_id'], $tagdata);
						}
						else
						{
							$tagdata = $TMPL->swap_var_single($key, '', $tagdata);
						}
						break;
						
				} // switch
				
				// make sure we only swap certain fields
				foreach($fields as $value)
				{
					if ($key == $value) {
						$tagdata = $TMPL->swap_var_single($key, $row[$key], $tagdata);
					}
				}
				// foreach
			}
			// foreach
			
			if ($count >= count($query->result)) {
				if (is_numeric($TMPL->fetch_param('backspace')))
				{
					$tagdata = rtrim(str_replace("&#47;", "/", $tagdata));
					$tagdata = substr($tagdata, 0, - $TMPL->fetch_param('backspace'));
					$tagdata = str_replace("/", "&#47;", $tagdata);
				}
			}
			$count++;
			$i++;
			
			$this->return_data .= $tagdata;
		}
		// foreach
	}
	// Constructor
	
}
// END CLASS
?>