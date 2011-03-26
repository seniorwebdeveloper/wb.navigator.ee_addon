<?php if ( ! defined('EXT')) exit('Invalid file request');

/**
 * Navigator Module for ExpressionEngine.
 *
 * This module is provided "as is" without warranty of
 * any kind or nature, either expressed or implied, including,
 * but not limited to, the implied warranties of merchantability,
 * fitness for a particular purpose, or non-infringement.
 *
 * @author		Elwin Zuiderveld
 * @author		Wes Baker (http://github.com/wesbaker)
 * @author		Stephen Lewis (http://github.com/experience)
 * @copyright	Elwin Zuiderveld (2005-2009)
 * @package		Navigator
 */

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
			
			$site_id = $PREFS->ini('site_id');
			$pages	= $PREFS->ini('site_pages');
			$uris	= $pages[$site_id]['uris'];
			
			$page_uri = '';


			/**
			 * Prepare all the single variables up-front, so they can be used in the conditionals.
			 *
			 * @since		1.5.0
			 * @author		Stephen Lewis (http://github.com/experience)
			 */

			/**
			 * {nav_url}
			 *
			 * Not sure this is how I would have done things, but it works and a rewrite is overkill.
			 * Many of the 'nav_url' options are mutually exclusive. If (for example) a custom URL and
			 * a template_group/template have both been specified, the template_group/template wins,
			 * because it appears later in the code.
			 *
			 * Not at all intuitive in terms of the creation and management of navigation URLs, but that's
			 * probably more of a UI issue.
			 */

			$nav_url = '';

			// Custom URL.
			if ($row['nav_custom_url'])
			{
				$nav_url = $row['nav_custom_url'];
			}

			// Pages URL.
			if ($row['pages_uri'] == 'y' && array_key_exists($row['nav_entry_id'], $uris))
			{
				$nav_url = $FNS->create_url($uris[$row['nav_entry_id']], TRUE, FALSE);
			}

			// Template Group / Template.
			if ($row['template_id'] && array_key_exists($row['template_id'], $templates))
			{
				$nav_url = $templates[$row['template_id']] .'/';

				// Category URL and Entry Title are mutually exclusive.
				if ($row['nav_enty_id'] && array_key_exists($row['nav_entry_id'], $titles))
				{
					// Entry Title.
					$nav_url .= $titles[$row['nav_entry_id']] .'/';

					// Additional URL segments.
					if ($row['nav_url_segments'])
					{
						$nav_url .= ($row['nav_url_segments']{0} == '/')
							? substr($row['nav_url_segments'], 1)
							: $row['nav_url_segments'];
					}
				}
				elseif ($row['category_id'])
				{
					// Category.
					$t_url = explode('.', $row['category_id']);
					$cat_name = '';
					$cat_url_title = '';
					
					if (array_key_exists($cat_id['1'], $categories))
					{
						$cat_name = $categories[$cat_id['1']];
						$cond['nav_category'] = ($cat_name == '0') ? 'FALSE' : $cat_name;
						$cat_url_title = $cat_url_titles[$cat_id['1']];
						$cond['nav_cat_url_title'] = ($cat_url_title == '0') ? 'FALSE' : $cat_url_title;
					}

					$nav_url = ($PREFS->ini('use_category_name') == 'y')
						? $PREFS->ini('reserved_category_word').'/'.$cat_url_title
						: 'C' .$t_url['1'];
				}

				$nav_url = $FNS->create_url($nav_url);
			}

			// {nav_is_active}
			$nav_is_active = $FNS->fetch_current_uri() == $nav_url ? 'y' : 'n';

			// {nav_url_title}
			$nav_url_title = ($row['nav_entry_id'] && array_key_exists($row['nav_entry_id'], $titles))
				? $titles[$row['nav_entry_id']]
				: '';

			// {nav_url_segments}
			$nav_url_segments = $row['nav_url_segments'];
			
			// {nav_template}
			$nav_template = ($row['template_id'] && array_key_exists($row['template_id'], $templates))
				? $templates[$row['template_id']]
				: '';

			// {nav_category} / {nav_category_id}
			$nav_category		= '';
			$nav_category_id	= '';

			if ($row['category_id'])
			{
				$exploded = explode('.', $row['category_id']);
			
				if (array_key_exists($exploded['1'], $categories))
				{
					$nav_category		= $categories[$exploded['1']];
					$nav_category_id	= $exploded['1'];
				}
			}

			// {nav_group_id}
			$nav_group_id = $row['group_id'] ? $row['group_id'] : '';
			

			// Conditionals.
			$cond = array(
				'count'				=> $i,
				'nav_category'		=> $nav_category,
				'nav_category_id'	=> $nav_category_id,
				'nav_category_word'	=> $nav_category_word,
				'nav_description'	=> $row['nav_description'],
				'nav_entry_id'		=> $row['nav_entry_id'],
				'nav_group_id'		=> $nav_group_id,
				'nav_id'			=> $row['nav_id'],
				'nav_is_active'		=> $nav_is_active,
				'nav_order'			=> $row['nav_order'],
				'nav_properties'	=> $row['nav_properties'],
				'nav_template'		=> $nav_template,
				'nav_title'			=> $row['nav_title'],
				'nav_title_length'	=> $row['nav_title_length'],		// No idea what this is. Doesn't appear to be strlen($nav_title).
				'nav_url'			=> $nav_url,
				'nav_url_segments'	=> $nav_url_segments,
				'nav_url_title'		=> $nav_url_title,
				'total_results'		=> $query->num_rows
			);
			
			$tagdata = $FNS->prep_conditionals($tagdata, $cond);
			
			// Single Variables.
			foreach ($TMPL->var_single as $key)
			{
				switch ($key)
				{
					/**
					 * @todo Move this out of the loop, and do the prep work once.
					 */

					case 'switch':
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
					
					case 'nav_url':
						$tagdata = $TMPL->swap_var_single($key, $nav_url, $tagdata);
						break;

					case 'nav_url_title':
						$tagdata = $TMPL->swap_var_single($key, $nav_url_title, $tagdata);
						break;
					
					case 'nav_url_segments':
						$tagdata = $TMPL->swap_var_single($key, $nav_url_segments, $tagdata);
						break;
						
					case 'nav_template':
						$tagdata = $TMPL->swap_var_single($key, $nav_template, $tagdata);
						break;
						
					case 'nav_category':
						$tagdata = $TMPL->swap_var_single($key, $nav_category, $tagdata);
						break;
						
					case 'nav_category_id':
						$tagdata = $TMPL->swap_var_single($key, $nav_category_id, $tagdata);
						break;
						
					case 'nav_category_word':
						$tagdata = $TMPL->swap_var_single($key, $nav_category_word, $tagdata);
						break;
						
					case 'nav_group_id':
						$tagdata = $TMPL->swap_var_single($key, $nav_group_id, $tagdata);
						break;
						
				}
				
				// make sure we only swap certain fields
				foreach($fields as $value)
				{
					if ($key == $value) {
						$tagdata = $TMPL->swap_var_single($key, $row[$key], $tagdata);
					}
				}
			}
			
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


/* End of file			mod.navigator.php */
/* File location		system/modules/navigator/mod.navigator.php */
