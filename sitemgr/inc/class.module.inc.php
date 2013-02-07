<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.module.inc.php 38591 2012-03-24 13:20:23Z ralfbecker $ */

class Transformer
{
	var $arguments;

	function Transformer($arguments=array())
	{
		$this->arguments = $arguments;
	}

	function apply_transform($title,$content)
	{
		return $content;
	}
}


class Module
{
	var $i18n; //flag a module must use if it wants its content to be translatable
	var $validation_error;
	var $transformer_chain;
	var $arguments;
	var $properties;
	var $block;

	function Module()
	{

		$this->arguments = array();
		$this->properties = array();
		$this->transformer_chain = array();
		$this->title = "Standard module";
		$this->description = "Parent class that all modules should extend";
	}

	function add_transformer(&$transformer)
	{
		$this->transformer_chain[] =& $transformer;
	}

	//before calling the functions get_user_interface, get_output,
	//the function set_block is used, so that we know in what scope we are, know the arguments,
	//and can retrieve the properties
	//this function can be overriden (but do not forget to call parent::set_block) in order to do some configuration
	//that depends on the blocks arguments
	//the produce argument is set when content is generated, so we can do some stuff we do not need when editing the block
	function set_block(&$block,$produce=False)
	{
		if ($produce)
		{
			if (is_array($this->session) && count($this->session))
			{
				$sessionvars = $GLOBALS['egw']->session->appsession('block[' . $block->id . ']', 'sitemgr-site');
			}
			foreach(array(
				'session' => $sessionvars,
				'get'     => $_GET['block'][$block->id],
				'post'    => $_POST['block'][$block->id],
				'cookie'  => $_COOKIE['block'][$block->id]
				) as $where => $values)
			{
				if (is_array($this->$where))
				{
					foreach($this->$where as $key => $argument)
					{
						//contrary to $this->get, cookie and session, the argument name is the key in $this->post,
						//because this array also defines the form element
						if ($where == 'post') $argument = $key;

						if (isset($values[$argument]) && !$block->addcontents)
						{
							$block->arguments[$argument] = $values[$argument];
						}
					}
				}
			}
		}
		$this->block =& $block;
	}

	function link($modulevars=array(),$extravars=array(),$addcontent='')
	{
		if (is_array($modulevars))
		{
			foreach($modulevars as $key => $value)
			{
				$extravars['block['. $this->block->id  .'][' . $key . ']'] = $value;
			}
		}
		if ($GLOBALS['page']->name)
		{
			$extravars['page_name'] = $extravars['page_name'] ? $extravars['page_name'] : $GLOBALS['page']->name;

		}
		elseif ($GLOBALS['page']->cat_id)
		{
			$extravars['category_id'] = $GLOBALS['page']->cat_id;
		}
		elseif ($GLOBALS['page']->toc)
		{
			$extravars['toc'] = 1;
		}
		elseif ($GLOBALS['page']->index)
		{
			$extravars['index'] = 1;
		}
		if (is_array($addcontent))
		{
			$add_cont = $GLOBALS['egw']->session->appsession('addcontent','sitemgr');
			$add_counter = is_array($add_cont) ? count($add_cont) : 0;
			$new_add = array_pop($addcontent);
			$extravars['addcontent'] = '';
			while($new_add)
			{
				$extravars['addcontent'] .= $add_counter;
				$add_cont[$add_counter] = $new_add;
				if($new_add['page']) $extravars['page_name'] = $new_add['page'];

				$new_add = array_pop($addcontent);
				if($new_add)
				{
					$extravars['addcontent'] .= ',';
					$add_counter = $add_counter + 1;
				}
			}
			$GLOBALS['egw']->session->appsession('addcontent','sitemgr',$add_cont);
		}
		return sitemgr_link($extravars);
	}

	function find_template_dir()
	{
		$templaterootformat = $GLOBALS['sitemgr_info']['site_dir']. SEP . 'templates' . SEP . '%s' . SEP . 'modules' . SEP . $this->block->module_name;
		$themetemplatedir = sprintf($templaterootformat,$GLOBALS['sitemgr_info']['themesel']);
		if (is_dir($themetemplatedir))
		{
			return $themetemplatedir;
		}
		else
		{
			return sprintf($templaterootformat,'default');
		}
	}

	function get_properties($cascading=True)
	{
		if ($this->properties)
		{
			if ($cascading)
			{
				return $GLOBALS['Common_BO']->modules->getcascadingmoduleproperties(
					$this->block->module_id,
					$this->block->area,
					$this->block->cat_id,
					$this->block->module_name
				);
			}
			else
			{
				return $GLOBALS['Common_BO']->modules->getmoduleproperties(
					$this->block->module_id,
					$this->block->area,
					$this->block->cat_id
				);
			}
		}
		else
		{
			return False;
		}
	}

	function get_user_interface()
	{
		//if you override this function you can fetch properties and adapt the interface accordingly
		//$properties = $this->get_properties();
		$interface = array();
		reset($this->arguments);
		while (list($key,$input) = @each($this->arguments))
		{
			$elementname = 'element[' . $this->block->version . ']';
			$elementname .= ($input['i18n'] ? ('[i18n][' .$key . ']') : ('[' .$key . ']'));
			//arrays of input elements are only implemented for the user interface
			if ($input['type'] == 'array')
			{
				$i = 0;
				while (isset($input[$i]))
				{
					$element['label'] = $input[$i]['label'];
					$element['form'] = $this->build_input_element($input[$i],$this->block->arguments[$key][$i],$elementname.'[]');
					$interface[] = $element;
					$i++;
				}
			}
			else
			{
				$element['label'] = $input['label'];
				$element['large'] = $input['large'];	// show label above instead beside content
				$element['form'] = $this->build_input_element($input,$this->block->arguments[$key],$elementname);
				$interface[] = $element;
			}
		}
		return $interface;
	}


	function get_translation_interface($fromblock,$toblock)
	{
		//if you override this function you can fetch properties and adapt the interface accordingly
		//$properties = $this->get_properties();
		$interface = array();
		reset($this->arguments);
		while (list($key,$input) = @each($this->arguments))
		{
			if ($input['i18n'])
			{
				$elementname = 'element[' . $this->block->version . '][i18n][' .$key . ']';
				//arrays of input elements are only implemented for the user interface
				if ($input['type'] == 'array')
				{
					$i = 0;
					while (isset($input[$i]))
					{
						$element['label'] = $input[$i]['label'];
						$element['form'] = $this->build_input_element($input[$i],$toblock->arguments[$key][$i],$elementname.'[]');
						$element['value'] = $fromblock->arguments[$key][$i];
						$interface[] = $element;
						$i++;
					}
				}
				else
				{
					$element['label'] = $input['label'];
					$element['form'] = $this->build_input_element($input,$toblock->arguments[$key],$elementname);
					$element['value'] = $fromblock->arguments[$key];
					$interface[] = $element;
				}
			}
		}
		return $interface;
	}


	function get_admin_interface()
	{
		$properties = $this->get_properties(False);
		$interface = array();
		while (list($key,$input) = @each($this->properties))
		{
			$elementname = 'element[' .$key . ']';
			$element['label'] = $input['label'];
			$element['form'] = $this->build_input_element($input,$properties[$key],$elementname);
			$interface[$key] = $element;
		}
		return $interface;
	}

	function build_post_element($key,$default=False)
	{
		return $this->build_input_element(
			$this->post[$key],
			($default !== False) ? $default : $this->block->arguments[$key],
			('block[' . $this->block->id  . '][' . $key . ']')
		);
	}

	//this function strips html and curly braces from the default values of the input elements
	//the former is necessary for valid input forms, the latter would hurt phpgw's template
	function escape_default(&$default)
	{
		$trans = array('{' => '&#123;', '}' => '&#125;');
		if (is_array($default))
		{
			reset($default);
			while (list($key,$val) = each($default))
			{
				$this->escape_default($data[$key]);
			}
		}
		else
		{
			$default = strtr(htmlspecialchars($default),$trans);
		}
	}

	function build_input_element($input,$default,$elementname)
	{
		//echo "<p>module::build_input_element(".print_r($input,True).",'$default','$elementname')</p>";
		if (empty($default) && isset($input['default']))
		{
			$default = $input['default'];
		}
		$params = (array)$input['params'];
		$params['name'] = $elementname . ($input['multiple'] ? '[]' : '');
		$params['id'] = str_replace(array('[',']'),array('_',''),$elementname);

		// hide / show certain input elements depending on their name
		if (isset($input['*switch-hide*']))
		{
_debug_array($input);
echo json_encode($input['*switch-hide*']);
			$params['onchange'] = "switch_hide('$params[id]',".json_encode($input['*switch-hide*']).");";
			$GLOBALS['egw']->js->set_onload($params['onchange']);
			$js = '<script type="text/javascript">
function switch_hide(id,params)
{
	var select = document.getElementById(id);
	var hide = params["*default*"];
	for(var pname in params)
	{
		if (pname == select.value) { hide = params[pname]; break; }
	}
	var name_start = id.lastIndexOf("_")+1;
	var prefix = id.substr(0,name_start);
	for(var n in select.form.elements)
	{
		var elem = select.form.elements[n];
		if (!elem || !elem.id || elem.id == id || elem.id.substr(0,name_start) != prefix) continue;
		var ename = elem.id.substr(name_start);
		var display = "";
		for (var i in hide) if (hide[i] == ename) { display = "none"; break; }
		elem.parentNode.parentNode.style.display = display;
	}
}
</script>
';
		}

		$inputdef = '';
		foreach($params as $param => $value)
		{
			$inputdef .= ' '.$param . '="' . htmlspecialchars($value) . '" ';
			unset($params[$param]);
		}
		switch($input['type'])
		{
			case 'htmlarea':
				return html::fckEditor($elementname,$default,
					$GLOBALS['egw_info']['user']['preferences']['sitemgr']['rtfEditorFeatures'],	// default is extended
					null,'300px','100%',	// 300px to fit in the popup, one can use fitWindow to expand it
					$GLOBALS['Common_BO']->sites->current_site['upload_dir'],
					!$GLOBALS['Common_BO']->acl->is_admin());	// run htmlpurifier only for regular users (not site-admins)
			case 'textarea':
				return '<textarea ' . $inputdef . '>' . $default . '</textarea>';
			case 'textfield':
				$params['type'] = 'text';
				$params['value'] = $default;
				break;
			case 'checkbox':
				$params['type'] = 'checkbox';
				if ($default) $params['checked'] = 1;
				break;
			case 'select':
				$select = $js.'<select ' .($input['multiple'] ? 'multiple="1"'.($input['multiple'] > 1 ? ' size="'.$input['multiple'].'"' : '') : '') . $inputdef . '>';
				foreach ($input['options'] as $value => $display)
				{
					$title = '';
					if (is_array($display))
					{
						$title = @$display['title'] ? ' title="'.htmlspecialchars($display['titel']).'"' : '';
						$display = $display['name'];
					}
					// selecting first option, if none is selected, so switch-hide always has one selected
					if (!is_array($default) && (string)$default === '') $default = $value;
					$selected='';
					if ($input['multiple'] && is_array($default) && in_array($value,$default) ||
						!$input['multiple'] && $default == $value)
					{
						$selected = ' selected="1"';
					}
					$select .= '<option value="'. $value . '"' . $selected . $title . '>' . $display . '</option>';
				}
				$select .= '</select>';
				return $select;
			case 'submit':
				$params['type'] = 'submit';
				$params['value'] = $input['value'];
				break;
			case 'image':
				$params['type'] = 'image';
				$params['src']  = $input['src'];
				break;
		}
		foreach($params as $param => $value)
		{
			$inputdef .= ' '.$param . '="' . htmlspecialchars($value) . '" ';
		}
		return $js.'<input '.$inputdef.'/>';
	}

	function validate(&$data)
	{
		return true;
	}

	function validate_properties(&$data)
	{
		return true;
	}

	//never call get_content directly, get_output takes care of passing it the right arguments
	function get_content(&$arguments,$properties)
	{

	}

	function get_output($type='html')
	{
		$content= $this->get_content($this->block->arguments,$this->get_properties());
		if (!$content)
		{
			return '';
		}
		if ($type == 'raw')
		{
			return $content;
		}
		else
		{
			static $charset;
			if (is_null($charset)) $charset = translation::charset();

			$title = htmlspecialchars($this->block->title, ENT_NOQUOTES, $charset, false);
			for ( $i = 0; $i < count( $this->transformer_chain ); ++$i )
			{
				$content = $this->transformer_chain[$i]->apply_transform($title,$content,$this->block);
			}
			//store session variables
			if ($this->session)
			{
				reset($this->session);
				while (list(,$argument) = each($this->session))
				{
					if (isset($this->block->arguments[$argument]))
					{
						$sessionarguments[$argument] = $this->block->arguments[$argument];
					}
				}
				$GLOBALS['egw']->session->appsession('block[' . $this->block->id . ']','sitemgr-site',$sessionarguments);
			}
			return $content;
		}
	}
}
