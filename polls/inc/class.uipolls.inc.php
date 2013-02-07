<?php
/**
 * eGroupWare - Polls
 * http://www.egroupware.org 
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package polls
 * @copyright 1999 by Till Gerken <tig@skv.org>
 * @author Till Gerken <tig@skv.org>
 * @author Greg Haygood <shrykedude@bellsouth.net>
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.uipolls.inc.php 34714 2011-04-18 14:25:35Z nathangray $
 */

require_once(EGW_INCLUDE_ROOT.'/polls/inc/class.bopolls.inc.php');
require_once(EGW_API_INC.'/class.nextmatchs.inc.php');

class uipolls
{
	/**
	 * Referenz to global (phplib) template object
	 *
	 * @var Template
	 */
	var $t;
	/**
	 * Instance of our BO class
	 *
	 * @var bopolls
	 */
	var $bo;
	/**
	 * Instance of the nextmatchs class
	 *
	 * @var nextmatchs
	 */
	var $nextmatchs;

	var $debug = False;

	var $public_functions = array(
		'index' => True,
		'admin' => True,
		'vote'  => True,
		'view'  => True,
	);

	function uipolls($use_session=true)
	{
		$this->t = new Template($GLOBALS['egw']->common->get_tpl_dir('polls'));
		$this->bo = new bopolls($use_session);
		$this->nextmatchs = new nextmatchs;
	}

	function index($view=false)
	{
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$currentpoll = $GLOBALS['poll_settings']['currentpoll'] ? $GLOBALS['poll_settings']['currentpoll'] : $this->bo->get_latest_poll();
		if(!$view && $this->bo->user_can_vote($currentpoll))
		{
			echo $this->show_ballot($currentpoll);
		}
		else
		{
			echo $this->view_results($currentpoll,true,true);
		}
		$GLOBALS['egw']->common->egw_footer();
	}

	function view()
	{
		$this->index(true);
	}

	function vote()
	{
		if($_POST['vote'] && isset($_POST['poll_id']) && isset($_POST['poll_voteNr']))
		{
			$poll_id = (int)$_POST['poll_id'];
			$vote_id = (int)$_POST['poll_voteNr'];
			$this->bo->add_vote($poll_id,$vote_id);
			$GLOBALS['egw']->redirect_link(
				'/index.php',array(
					'menuaction' => 'polls.uipolls.vote',
					'show_results' => $poll_id
				)
			);
			$GLOBALS['egw']->common->egw_exit();
			return 0;
		}
		$showpoll = $_GET['show_results'];
		if(empty($showpoll))
		{
			$showpoll = $GLOBALS['poll_settings']['currentpoll'] ? $GLOBALS['poll_settings']['currentpoll'] : $this->bo->get_latest_poll();
		}
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();
		echo $this->view_results($showpoll);
		$GLOBALS['egw']->common->egw_footer();
	}

	function admin()
	{
		if(!$GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$GLOBALS['egw']->redirect_link('/index.php');
		}
		$action = get_var('action',array('GET','POST'));
		$type   = get_var('type',array('GET','POST'));
		if($_POST['cancel'])
		{
			if(!empty($type))
			{
				header('Location: '.$this->adminlink('show',$type));
			}
			else
			{
				$GLOBALS['egw']->redirect_link('/index.php',array('menuaction'=>'polls.uipolls.vote'));
			}
			$GLOBALS['egw']->common->egw_exit();
			return 0;
		}
		if(isset($_POST['delete']) && $action == 'edit')
		{
			$action = 'delete';
		}
		$func = $action.$type;
		if(method_exists($this,$func))
		{
			call_user_func(array($this,$func));
		}
		elseif(method_exists($this,$action))
		{
			call_user_func(array($this,$action));
		}
		else
		{
			$this->index();
		}
	}

	function button_bar($buttons)
	{
		if(isset($buttons) && is_array($buttons))
		{
			$this->t->set_var('buttons','');
			foreach($buttons as $name => $value)
			{
				$this->t->set_var('btn_name',$name);
				$this->t->set_var('btn_value',$value);
				$this->t->parse('buttons','button',True);
			}
		}
	}

	function action_button($action,$options)
	{
		$button = '';

		$img = '<img src="'
			. $GLOBALS['egw']->common->image('addressbook',$action)
			. '" border="0" title="'.lang($action).'">';
		if(empty($options) || !is_array($options))
		{
			$options = array();
		}
		if(!isset($options['action']))
		{
			$options['action'] = $action;
		}
		$button = '<a href="'.$GLOBALS['egw']->link('/index.php',$options).'">'.$img.'</a>';

		return $button;
	}

	function add_template_row($label,$value)
	{
		$this->t->set_var('tr_class',$cl=$this->nextmatchs->alternate_row_color('',true));
		$this->t->set_var('td_1',$label);
		$this->t->set_var('td_2',$value);

		$this->t->parse('rows','row',True);
	}

	function adminlink($action = 'show',$type = 'question',$extra = '')
	{
		$options = array('menuaction'=>'polls.uipolls.admin','action'=>$action,'type'=>$type);
		if(isset($extra) && is_array($extra))
		{
			$options += $extra;
		}
		return $GLOBALS['egw']->link('/index.php',$options);
	}

	function addanswer($poll_id=null)
	{
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('Add Answer to poll');
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		if (!$poll_id) $poll_id = $_REQUEST['poll_id'];

		$this->t->set_file(array('admin' => 'admin_form.tpl'));
		$this->t->set_block('admin','form','form');
		$this->t->set_block('admin','row','row');
		$this->t->set_block('admin','button','button');
		$this->t->set_block('admin','input','input');
		$this->t->set_var('hidden','');

		if($_POST['submit'] && $_POST['answer'])
		{
			$answer  = $_POST['answer'];
			if (get_magic_quotes_gpc()) $answer = stripslashes($answer);

			$this->bo->add_answer($poll_id,$answer);
			$this->t->set_var('message',lang('Answer has been added to poll'));
		}

		$this->t->set_var('header_message',lang('Add answer to poll'));
		$this->t->set_var('td_message','&nbsp;');
		$this->t->set_var('th_bg',$GLOBALS['egw_info']['theme']['th_bg']);
		$this->t->set_var('form_action',$this->adminlink('add','answer'));
		$this->button_bar(array(
			'submit' => lang('Add'),
			'cancel' => lang('Cancel')
		));

		$poll_select = $this->select_poll($poll_id);

		$this->add_template_row(lang('Which poll'),$poll_select);
		$this->t->set_var('input_name','answer');
		$this->t->set_var('input_value','');
		$this->add_template_row(lang('Answer'),$this->t->parse('td_2','input',True));

		$this->t->pfp('out','form');
	}

	function select_poll($preselected_poll, $show_select_tag=true)
	{
		if($show_select_tag)
		{
			$poll_select = '<select name="poll_id">';
		}
		$questions = $this->bo->get_list('question',true);

		foreach($questions as $key => $array)
		{
			$_poll_id = $array['poll_id'];
			$_vote_id = $array['vote_id'];

			$_poll_title = $array['poll_title'];
			$_option_text = $array['option_text'];

			$poll_select .= '<option value="'.$_poll_id.'"';
			if($preselected_poll == $_poll_id)
			{
				$poll_select .= ' selected="1"';
			}
			$poll_select .= '>'.$_poll_title.'</option>';
		}
		if($show_select_tag)
		{
			$poll_select .= '</select>';
		}

		return $poll_select;
	}

	function addquestion()
	{
		if($_POST['submit'] || $_POST['question'])
		{
			$question  = $_POST['question'];
			if (get_magic_quotes_gpc()) $question = stripslashes($question);
			
			$newid = $this->bo->add_question($question,$_POST['poll_visible'],$_POST['poll_votable']);
			
			$this->addanswer($newid);
			return;
		}
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('Add new poll question');
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$this->t->set_file(array('admin' => 'admin_form.tpl'));
		$this->t->set_block('admin','form','form');
		$this->t->set_block('admin','row','row');
		$this->t->set_block('admin','button','button');
		$this->t->set_block('admin','input','input');
		$this->t->set_var('hidden','');

		$this->t->set_var('message','');

		$this->t->set_var('header_message',lang('Add new poll question'));
		$this->t->set_var('td_message',"&nbsp;");
		$this->t->set_var('form_action', $this->adminlink('add','question'));
		$this->button_bar(array(
			'submit' => lang('Add'),
			'cancel' => lang('Cancel')
		));

		foreach(array(
			'poll_title' => lang('Poll question'),
			'poll_votable' => lang('Poll votable by'),
			'poll_visible' => lang('Result visible to'),
		) as $id => $label)
		{
			if ($id == 'poll_title')
			{
				$this->t->set_var('input_name','question');
				$this->t->set_var('input_value','');
				$input = $this->t->parse('td_2','input',True);
			}
			else
			{
				$input = $GLOBALS['egw']->html->select($id,0,$this->get_acl_values(),true);
			}
			$this->add_template_row($label,$input);
		}
		$this->t->pparse('out','form');
	}

	function deleteanswer()
	{
		$poll_id = (int)get_var('poll_id',array('GET','POST'));
		$vote_id = (int)get_var('vote_id',array('GET','POST'));
		$confirm = get_var('confirm',array('GET','POST'));
		if(!empty($confirm))
		{
			$this->bo->delete_answer($poll_id,$vote_id);
			header('Location: ' . $this->adminlink('show','answer'));
		}
		else
		{
			$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('delete') . ' ' . lang('answer');
			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();

			$poll_data = $this->bo->get_poll_data($poll_id,$vote_id);
			$poll_info = $poll_data[0]['text'] . ' ('.lang('total votes').' = '.$poll_data[0]['votes'].')';

			$this->t->set_file(array('admin' => 'admin_form.tpl'));
			$this->t->set_block('admin','form','form');
			$this->t->set_block('admin','row','row');
			$this->t->set_block('admin','button','button');
			$this->t->set_var('rows','');

			$this->t->set_var('hidden','<input type="hidden" name="vote_id" value="'.$vote_id.'">');
			$this->t->set_var('poll_id',$poll_id);
			$this->t->set_var('vote_id',$vote_id);
			$this->t->set_var('th_bg',$GLOBALS['egw_info']['theme']['th_bg']);
			$this->t->set_var('message',lang('Are you sure want to delete this answer ?'));
			$this->t->set_var('td_message', $this->bo->get_poll_title($poll_id) . ': ' . $poll_info);
			$this->t->set_var('form_action',$this->adminlink('delete','answer'));
			$this->button_bar(array(
				'cancel' => lang('No'),
				'confirm' => lang('Yes')
			));

			$this->t->pparse('out','form');
		}
	}

	function deletequestion()
	{
		$poll_id = (int)get_var('poll_id',array('GET','POST'));
		$confirm = get_var('confirm',array('GET','POST'));
		if(!empty($confirm))
		{
			$this->bo->delete_question($poll_id);
			header('Location: ' . $this->adminlink('show','question'));
		}
		else
		{
			$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('delete') . ' ' . lang('Poll Question');
			$GLOBALS['egw']->common->egw_header();
			echo parse_navbar();

			$this->t->set_file(array('admin' => 'admin_form.tpl'));
			$this->t->set_block('admin','form','form');
			$this->t->set_block('admin','row','row');
			$this->t->set_block('admin','button','button');
			$this->t->set_var('rows','');
			$this->t->set_var('hidden','');

			$this->t->set_var('poll_id',$poll_id);

			$this->t->set_var('th_bg',$GLOBALS['egw_info']['theme']['th_bg']);
			$this->t->set_var('message',lang('Are you sure want to delete this question ?'));
			$this->t->set_var('td_message', $this->bo->get_poll_title($poll_id));
			$this->t->set_var('form_action',$this->adminlink('delete','question'));
			$this->button_bar(array(
				'cancel' => lang('No'),
				'confirm' => lang('Yes')
			));

			$this->t->pparse('out','form');
		}
	}

	function editanswer()
	{
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('Edit answer');
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$this->t->set_file(array('admin' => 'admin_form.tpl'));
		$this->t->set_block('admin','form','form');
		$this->t->set_block('admin','row','row');
		$this->t->set_block('admin','button','button');
		$this->t->set_var('hidden','');

		$poll_id = (int)(get_var('poll_id',array('POST','GET')));
		$vote_id = (int)(get_var('vote_id',array('POST','GET')));
		$this->t->set_var('poll_id',$poll_id);

		if($_POST['submit'])
		{
			$answer = $_POST['answer'];
			if (get_magic_quotes_gpc()) $answer = stripslashes($answer);
			$this->bo->update_answer($poll_id,$vote_id,$answer);
			$this->t->set_var('message',lang('Answer has been updated'));
		}
		else
		{
			$this->t->set_var('message','');
		}

		$poll_data = $this->bo->get_poll_data($poll_id,$vote_id);
		$answer_value = trim($poll_data[0]['text']);
		//$poll_id = $GLOBALS['egw']->db->f('poll_id');

		$this->t->set_var('header_message',lang('Edit answer'));
		$this->t->set_var('td_message','&nbsp;');
		$this->t->set_var('form_action',$this->adminlink('edit','answer',array('vote_id'=>$vote_id)));
		$this->button_bar(array(
			'submit' => lang('Edit'),
			'cancel' => lang('Cancel')
		));

		$poll_select = $this->select_poll($poll_id);

		$this->add_template_row(lang('Which poll'),$poll_select);
		$this->add_template_row(lang('Answer'),'<input name="answer" value="' . $answer_value . '">');

		$this->t->pparse('out','form');
		$GLOBALS['egw']->common->egw_footer();
	}
	
	function get_acl_values()
	{
		static $acl_values;
		
		if (is_null($acl_values))
		{
			$acl_values = array(
				0 => lang('Everyone incl. anonymous users'),
				1 => lang('eGroupWare users (non-anonymous)'),
				2 => lang('Administrators'),
				3 => lang('Noone'),
			);
			foreach($GLOBALS['egw']->accounts->search(array('type'=>'groups')) as $group)
			{
				$acl_values[(string)$group['account_id']] = lang('%1 group',$group['account_lid']);
			}
		}
		return $acl_values;
	}

	function editquestion()
	{
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('Edit poll question');
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$this->t->set_file(array('admin' => 'admin_form.tpl'));
		$this->t->set_block('admin','form','form');
		$this->t->set_block('admin','row','row');
		$this->t->set_block('admin','button','button');
		$this->t->set_block('admin','input','input');
		$this->t->set_block('admin','results','results');
		$this->t->set_block('admin','answers','answers');
		$this->t->set_block('admin','answer_row','answer_row');
		$this->t->set_block('admin','messagebar','messagebar');
		$this->t->set_var('hidden','');

		$poll_id = get_var('poll_id',array('GET','POST'));
		$this->t->set_var('poll_id',$poll_id);

		if($_POST['submit'])
		{
			$question = trim($_POST['question']);
			if (get_magic_quotes_gpc()) $question = stripslashes($question);
			$this->bo->update_question($poll_id,$question,$_POST['poll_visible'],$_POST['poll_votable']);
			$this->t->set_var('message',lang('Question has been updated'));
		}
		else
		{
			$this->t->set_var('message','');
		}

		$poll = $this->bo->get_poll($poll_id);

		$answers = $this->bo->get_poll_data($poll_id);

		$this->t->set_var('header_message',lang('Edit poll question'));
		$this->t->set_var('td_message','&nbsp;');
		$this->t->set_var($poll);
		$this->t->set_var('form_action',$this->adminlink('edit','question',array('poll_id'=>$poll_id)));
		$this->button_bar(array(
			'edit' => lang('Edit'),
			'cancel' => lang('Cancel')
		));

		foreach(array(
			'poll_title' => lang('Poll question'),
			'poll_votable' => lang('Poll votable by'),
			'poll_visible' => lang('Result visible to'),
		) as $id => $label)
		{
			if ($id == 'poll_title')
			{
				$this->t->set_var('input_name','question');
				$this->t->set_var('input_value',$poll['poll_title']);
				$input = $this->t->parse('td_2','input',True);
			}
			else
			{
				$input = $GLOBALS['egw']->html->select($id,$poll[$id],$this->get_acl_values(),true);
			}
			$this->add_template_row($label,$input);
		}
		$this->t->set_var('mesg',lang('Answers'));
		$this->t->parse('rows','messagebar',True);

		//$this->t->set_var('poll_results', $this->view_results($poll_id,false,true));

		foreach($answers as $answer)
		{
			$option_text  = $answer['text'];
			$option_count = $answer['votes'];
			$vote_id = $answer['vote_id'];

			$actions = '';
			$_options = array(
				'menuaction' => 'polls.uipolls.admin',
				'type'       => 'answer',
				'poll_id'    => $poll_id,
				'vote_id'    => $vote_id
			);
			foreach(array('edit','delete') as $_action)
			{
				$_options['action'] = $_action;
				$actions .= $this->action_button($_action,$_options);
			}
			$this->t->set_var('tr_class',$this->nextmatchs->alternate_row_color('',true));
			$this->t->set_var('answer_actions',$actions);
			$this->t->set_var('option_text',$option_text);

			$this->t->parse('poll_answers','answer_row',True);
		}
		$this->t->parse('rows','answers',True);

		$this->t->pparse('out','form');
	}

	function settings()
	{
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('Site configuration');
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$this->t->set_file(array('admin' => 'admin_settings.tpl'));
		$this->t->set_block('admin','settings','settings');
		//$this->t->set_block('admin','row','row');

		if($_POST['submit'])
		{
			$this->bo->save_settings($_POST['settings']);
			echo '<center>' . lang('Settings updated') . '</center>';
		}
		// load after a save, so this page displays correctly
		$settings = $this->bo->load_settings();

		$var = array(
			'form_action' => $this->adminlink('settings',''),
//			the backend takes now care for anon users by using additionally their ip
//			'lang_allowmultiple' => lang('Allow users to vote more then once'),
//			'check_allow_multiple_votes' => $GLOBALS['poll_settings']['allow_multiple_votes']?' checked':'',
			'lang_selectpoll' => lang('Select current poll'),
			'lang_latest_poll' => lang('Allways use latest poll'),
			'lang_submit' => lang('Submit'),
			'lang_cancel' => lang('Cancel'),
		);
		$this->t->set_var($var);

		$poll_questions = $this->select_poll($GLOBALS['poll_settings']['currentpoll'],false);
		$this->t->set_var('poll_questions', $poll_questions);

		$this->t->pparse('out','settings');
	}

	function viewquestion()
	{
		$poll_id = (int)$_GET['poll_id'];

		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.lang('View poll');
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$this->t->set_file(array('admin' => 'admin_form.tpl'));
		$this->t->set_block('admin','form','form');
		$this->t->set_block('admin','row','row');
		$this->t->set_block('admin','button','button');
		$this->t->set_var('hidden','');

		$poll_title = $this->bo->get_poll_title($poll_id);

		$this->t->set_var('message','');
		$this->t->set_var('header_message',lang('View poll'));
		$this->t->set_var('td_message',$poll_title);
		$this->t->set_var('th_bg',$GLOBALS['egw_info']['theme']['th_bg']);
		$this->t->set_var('form_action',$this->adminlink('edit','question'));
		$this->t->set_var('poll_id',$poll_id);

		$this->button_bar(array(
			'submit' => lang('Edit'),
			'delete' => lang('Delete'),
			'cancel' => lang('Cancel')
		));

		$this->t->set_var('rows', '<tr><td colspan="2" width="100%">'
			. $this->view_results($poll_id,false,true) . '</td></tr>');

		$this->t->pparse('out','form');
	}

	function show()
	{
		$type  = get_var('type',array('GET','POST'));
		if($type == 'question')
		{
			$pagetitle = lang('Show questions');
			$allowed_actions = array('view','edit','delete');
			$this->t->set_file(array('admin' => 'admin_list_questions.tpl'));
		}
		elseif($type == 'answer')
		{
			$pagetitle = lang('Show answers');
			$allowed_actions = array('edit','delete');
			$this->t->set_file(array('admin' => 'admin_list_answers.tpl'));
		}
		else
		{
			$this->index('/polls/index.php');
			$GLOBALS['egw']->common->egw_exit(True);
			return 0;
		}

		$GLOBALS['egw_info']['flags']['app_header'] = lang('Polls').' - '.$pagetitle;
		$GLOBALS['egw']->common->egw_header();
		echo parse_navbar();

		$this->bo->sort  = $_GET['sort'] ? $_GET['sort'] : 'ASC';
		$this->bo->order = isset($_GET['order']) ? $_GET['order'] : 'poll_title';
		if(!$this->bo->start)
		{
			$this->bo->start = 0;
		}
		$this->bo->save_sessiondata();

		$this->t->set_block('admin','form','form');
		$this->t->set_block('admin','row','row');

		$this->t->set_unknowns('remove');

		$thelist = $this->bo->get_list($type);

		$this->t->set_var('sort_title',$this->nextmatchs->show_sort_order($this->bo->sort,'poll_title',$this->bo->order,'index.php',lang('Title'),'&menuaction=polls.uipolls.admin&action=show&type='.$type));
		if($type == 'answer')
		{
			$this->t->set_var('sort_answer',$this->nextmatchs->show_sort_order($this->bo->sort,'answer_text',$this->bo->order,'index.php',lang('Answer'),'&menuaction=polls.uipolls.admin&action=show&type='.$type));
		}

		$left  = $this->nextmatchs->left('/index.php',$this->bo->start,$this->bo->total,'menuaction=polls.uipolls.admin&action=show&type='.$type);
		$right = $this->nextmatchs->right('/index.php',$this->bo->start,$this->bo->total,'menuaction=polls.uipolls.admin&action=show&type='.$type);
		$this->t->set_var('match_left',$left);
		$this->t->set_var('match_right',$right);

		$this->t->set_var('lang_showing',$this->nextmatchs->show_hits($this->bo->total,$this->bo->start));

		$this->t->set_var('lang_actions',lang('actions'));
		$this->t->set_var('lang_view',lang('view'));
		$this->t->set_var('lang_edit',lang('edit'));
		$this->t->set_var('lang_delete',lang('delete'));

		$this->t->set_var('rows','');
		foreach($thelist as $key => $array)
		{
			$this->t->set_var('tr_class',$this->nextmatchs->alternate_row_color('',true));

			$poll_id = $array['poll_id'];
			$vote_id = $array['answer_id'];

			$poll_title = $array['poll_title'];
			$option_text = $array['answer_text'];

			$actions = '';
			$_options = array(
				'menuaction' => 'polls.uipolls.admin',
				'type'       => $type,
				'poll_id'    => $poll_id
			);
			foreach($allowed_actions as $_action)
			{
				$_options['action'] = $_action;
				if($type == 'answer')
				{
					$_options['vote_id'] = $vote_id;
				}
				$actions .= $this->action_button($_action,$_options);
			}
			$this->t->set_var('row_actions',$actions);

			if($type == 'question')
			{
				$this->t->set_var('row_title',stripslashes($poll_title));
			}
			else
			{
				$this->t->set_var('row_answer',stripslashes($option_text));
				$this->t->set_var('row_title',stripslashes($poll_title));
				$this->t->set_var(
					'row_edit',
					'<a href="' . $this->adminlink(
						'edit',
						'answer',array(
							'vote_id' => $vote_id,
							'poll_id' => $poll_id
						)
					) .'">' . lang('Edit') . '</a>'
				);
				$this->t->set_var(
					'row_delete',
					'<a href="' . $this->adminlink(
						'delete',
						'answer',array(
							'vote_id' => $vote_id,
							'poll_id' => $poll_id
						)
					) .'">' . lang('Delete') . '</a>'
				);
			}
			$this->t->parse('rows','row',True);
		}

		$this->t->set_var('add_action',$this->adminlink('add',$type));
		$this->t->set_var('lang_add',lang('add'));

		$this->t->pparse('out','form');

		$GLOBALS['egw']->common->egw_footer();
	}

	function view_results($poll_id,$showtitle=true,$showtotal=true)
	{
		if (!$this->bo->check_acl($poll_id,'visible'))
		{
			return "<p align='center'>".lang('You are not (yet) allowed view the result of this vote.')."</p>\n";
		}
		$title = $this->bo->get_poll_title($poll_id);
		$sum = $this->bo->get_poll_total($poll_id);
		$results = $this->bo->get_poll_data($poll_id);

		$this->t->set_file(array('viewpoll' => 'view_poll.tpl'));
		$this->t->set_block('viewpoll','title','title');
		$this->t->set_block('viewpoll','poll','poll');
		$this->t->set_block('viewpoll','vote','vote');
		$this->t->set_block('viewpoll','image','image');
		$this->t->set_block('viewpoll','total','total');

		$this->t->set_var('titlebar', '');
		if($showtitle)
		{
			$this->t->set_var('poll_title', $title);
			$this->t->parse('titlebar','title');
		}

		$this->t->set_var('votes', '');
		$this->t->set_var('server_url',$GLOBALS['egw_info']['server']['webserver_url']);
		foreach($results as $result)
		{
			$option_text  = $result['text'];
			$option_count = $result['votes'];

			$this->t->set_var('tr_class', $this->nextmatchs->alternate_row_color('',true));

			if($option_text != '')
			{
				if($sum)
				{
					$poll_percent = 100 * $option_count / $sum;
				}
				else
				{
					$poll_percent = 0;
				}
				$poll_percent = sprintf("%2.1f",$poll_percent);

				$this->t->set_var('poll_bar','');
				if($poll_percent > 0)
				{
					$poll_percentScale = (int)($poll_percent * 1);
					$this->t->set_var('scale',$poll_percentScale);
					$this->t->parse('poll_bar','image');
				}
				else
				{
					$this->t->set_var('poll_bar','&nbsp;');
				}

				$this->t->set_var('option_text',$option_text);
				$this->t->set_var('option_count',$option_count);
				$this->t->set_var('percent',$poll_percent);
				$this->t->set_var('sum',$sum);

				$this->t->parse('votes','vote',True);
			}
		}

		if($showtotal)
		{
			$this->t->set_var('sum',$sum);
			$this->t->set_var('lang_total',lang('Total votes'));
			$this->t->parse('show_total','total');
		}
		return $this->t->parse('out','poll');
	}

	function show_ballot($poll_id = '',$action=null)
	{
		if(empty($poll_id))
		{
			$poll_id = $this->bo->get_latest_poll();
		}
		if(!$poll_id)
		{
			return False;
		}

		$poll_id = (int)$poll_id;

		if(!$this->bo->user_can_vote($poll_id))
		{
			return False;
		}

		$poll_title = $this->bo->get_poll_title($poll_id);
		$poll_sum = $this->bo->get_poll_total($poll_id);
		$results = $this->bo->get_poll_data($poll_id);

		$this->t->set_file(array('ballot' => 'ballot.tpl'));
		$this->t->set_block('ballot','form_top','form_top');
		$this->t->set_block('ballot','form_end','form_end');
		$this->t->set_block('ballot','entry','entry');

		$this->t->set_var('form_action',$action ? $action :
			$GLOBALS['egw']->link('/index.php',array('menuaction'=>'polls.uipolls.vote'))
		);
		$this->t->set_var('poll_id',$poll_id);
		$this->t->set_var('poll_title',$poll_title);
		$this->t->set_var('title_bgcolor', $GLOBALS['egw_info']['theme']['th_bg']);
		$this->t->set_var('bgcolor', $GLOBALS['egw_info']['theme']['bgcolor']);

		$this->t->set_var('entries', '');
		foreach($results as $result)
		{
			$vote_id = $result['vote_id'];
			$option_text  = $result['text'];
			$option_count = $result['votes'];

			$this->t->set_var('tr_class',$this->nextmatchs->alternate_row_color('',true));
			$this->t->set_var('vote_id', $vote_id);
			$this->t->set_var('option_text', $option_text);

			$this->t->parse('entries','entry',True);
		}

		$this->t->set_var('lang_vote', lang('Vote'));

		return $this->t->parse('out','form_top').$this->t->parse('out','form_end');
	}
}
