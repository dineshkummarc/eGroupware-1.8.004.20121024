<?php
/**
 * eGroupWare - SiteMgr block for Polls
 * http://www.egroupware.org 
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package polls
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.module_polls.inc.php 23899 2007-05-20 15:01:21Z ralfbecker $ 
 */

require_once(EGW_INCLUDE_ROOT.'/polls/inc/class.uipolls.inc.php');

class module_polls extends Module 
{
	/**
	 * Instance of polls UI
	 *
	 * @var uipolls
	 */
	var $uipolls;
	
	function module_polls()  
	{
		$GLOBALS['egw']->translation->add_app('polls');

		$this->uipolls = new uipolls(false);

		$questions = $this->uipolls->bo->get_list('question',true);
		if (!$questions) $questions = array();
		
		$polls = array('' => lang('Current poll'));
		foreach($questions as $data)
		{
			$polls[$data['poll_id']] = $data['poll_title'];
		}
		$this->arguments = array(			
			'poll_id' => array(
				'type' => 'select', 
				'label' => lang('Which poll'),
				'options' => $polls,
			),
			'what' => array(
				'type' => 'select', 
				'label' => lang('What to show'),
				'options' => array(
					'' => lang('Show bullet if allowed to vote and result else'),
					'ballot' => lang('Show only the ballot'),
					'result' => lang('Show only the result'),
				)
			),
		);
		$this->title = lang('Polls');
		$this->description = lang('This module displays polls.');
 	}

	function get_content(&$arguments,$properties)
	{
		$poll_id = $arguments['poll_id'] ? $arguments['poll_id'] : ($GLOBALS['poll_settings']['currentpoll'] ? 
			$GLOBALS['poll_settings']['currentpoll'] : $this->uipolls->bo->get_latest_poll());

		if ($arguments['what'] != 'result' && $_POST['vote'] && $_POST['poll_id'] == $poll_id && $_POST['poll_voteNr'])
		{
			$this->uipolls->bo->add_vote($poll_id,$_POST['poll_voteNr']);
		}
		if ($this->uipolls->bo->user_can_vote($poll_id) && $arguments['what'] != 'result' || $arguments['what'] == 'ballot')
		{
			$html = $this->uipolls->show_ballot($poll_id,'#');
		}
		elseif ($arguments['what'] != 'ballot')
		{
			$html = $this->uipolls->view_results($poll_id);
		}
		if (!$html && $GLOBALS['sitemgr_info']['mode'] == 'Edit')
		{
			$html = ' ';	// otherwise the block is no longer editable;
		}
		return $html;
	}
}
