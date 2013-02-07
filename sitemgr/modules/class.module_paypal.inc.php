<?php
/**
 * SiteMgr - Paypal module
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package sitemgr
 * @subpackage modules
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_paypal.inc.php 25174 2008-03-25 16:12:51Z ralfbecker $ 
 */

class module_paypal extends Module 
{
	function module_paypal()
	{
		$this->arguments = array(
			'business' => array(
				'type' => 'textfield', 
				'label' => lang('Email address of the PayPal account selling the item'),
			),
			'item_name' => array(
				'type' => 'textfield', 
				'label' => lang('Name of the item for sale'),
			),
			'item_number' => array(
				'type' => 'textfield', 
				'label' => lang('Identifier you can use to track an internal inventory number'),
			),
			'quantity' => array(
				'type' => 'textfield', 
				'label' => lang('Quantity items (empty allows buyer to specify)'),		
			),
			'amount' => array(
				'type' => 'textfield', 
				'label' => lang('Price of the item (empty allows buyer to choose, eg. for donations)'),
			),
			'currency_code' => array(
				'type' => 'textfield', 
				'label' => lang('Currency code of the value specified in amount (eg. USD, EUR)'),
			),
			'no_note' => array(
				'type' => 'select',
				'label' => lang('Allow customer to input a note'),
				'options' => array(
					'0' => lang('Yes'),
					'1' => lang('No'),
				),
			),
			'no_shipping' => array(
				'type' => 'select',
				'label' => lang('Shipping address'),
				'options' => array(
					'0' => lang('Ask customer'),
					'1' => lang('Dont ask'),
					'2' => lang('Required'),
				),
			),
			'button' => array(
				'type' => 'textfield',
				'label' => 'URL of the image of the button (empty for a text-button)',
//				'default' => 'https://www.paypal.com/en_US/i/btn/x-click-but23.gif',
			),
			'button_title' => array(
				'type' => 'textfield',
				'label' => 'Title of the button',
				'default' => lang('Pay with Paypal'),
			),
		);
		$this->post = array('name' => array('type' => 'textfield'));
		$this->session = array('name');
		$this->title = lang('Paypal');
		$this->description = lang('Paypal pay now button');
	}

	function get_content(&$arguments,$properties) 
	{
		return '<form method="POST" action="https://www.paypal.com/cgi-bin/webscr">'.
			html::input_hidden(array(
				'cmd' => '_xclick',
				'business' => $arguments['business'],
				'item_name' => $arguments['item_name'],
				'item_number' => $arguments['item_number'],
				'quantity' => $arguments['quantity'],
				'amount' => $arguments['amount'],
  				'no_shipping' => $arguments['no_shipping'],
   				'no_note'  => $arguments['no_note'],
				'currency_code' => $arguments['currency_code'],
 				'bn' => 'IC_Sample',
			)+(!$arguments['quantity'] ? array('undefined_quantity' => 1) : array())).
			($arguments['button'] ? 
				html::input('submit','','image',	// image button with url
					'src="'.htmlspecialchars($arguments['button']).'" title="'.htmlspecialchars($arguments['button_title']).'"'
				) : 
				html::input('submit',$arguments['button_title'],'submit')).	// text button
			"</form>\n";
	}
}
