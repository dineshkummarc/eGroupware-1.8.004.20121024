<?php
/**
 * eGroupWare Tracker - Handle incoming mails
 *
 * This class handles incoming mails in the async services.
 * It is an addition for the eGW Tracker app by Ralf Becker
 *
 * @link http://www.egroupware.org
 * @author Oscar van Eijk <oscar.van.eijk-AT-oveas.com>
 * @package tracker
 * @copyright (c) 2008 by Oscar van Eijk <oscar.van.eijk-AT-oveas.com>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_mailhandler.inc.php 40473 2012-10-11 10:32:38Z leithoff $
 */

class tracker_mailhandler extends tracker_bo
{
	/**
	 * UID of the mailsender, 0 if not recognized
	 *
	 * @var int
	 */
	var $mailSender;

	/**
	 * Subject line of the incoming mail
	 *
	 * @var string
	 */
	var $mailSubject;

	/**
	 * Text from the mailbody (1st part)
	 *
	 * @var string
	 */
	var $mailBody;

	/**
	 * Identification of the mailbox
	 *
	 * @var string
	 */
	var $mailBox;

	/**
	 * List with all messages retrieved from the server
	 *
	 * @var array
	 */
	var $msgList = array();

	/**
	 * Mailbox stream
	 *
	 * @var int
	 */
	var $mbox;

	/**
	 * Ticket ID or 0 if not recognize
	 *
	 * @var int
	 */
	var $ticketId;

	/**
	 * User ID currently executing. Used in case we execute in fallback
	 *
	 * @var int
	 */
	var $originalUser;

	/**
	 * Supported mailservertypes, extracted from parent::mailservertypes
	 *
	 * @var array
	 */
	var $serverTypes = array();

	/**
	 * How much should be logged to the apache error-log
	 *
	 * 0 = Nothing
	 * 1 = only errors
	 * 2 = more debug info
	 * 3 = complete debug info
	 */
	const LOG_LEVEL = 0;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		// In case we run in fallback, make sure the original user gets restored
		$this->originalUser = $this->user;
		foreach($this->mailservertypes as $ind => $typ)
		{
			$this->serverTypes[] = $typ[0];
		}
		if (($this->mailBox = self::get_mailbox()) === false)
		{
			return false;
		}
	}

	/**
	 * Destructor, close the stream if not done before.
	 */
	function __destruct()
	{
		if($this->mbox)
		{
			@imap_close($this->mbox);
		}
	}

	/**
	 * Compose the mailbox identification
	 *
	 * @return string mailbox identification as '{server[:port]/type}folder'
	 */
	function get_mailbox()
	{
		if (empty($this->mailhandling[0]['server']))
		{
			return false; // Or should we default to 'localhost'?
		}

		$mBox = '{'.$this->mailhandling[0]['server'];	// Set the servername

		if(!empty($this->mailhandling[0]['serverport']))
		{
			// If set, add the portnumber
			$mBox .= (':'.$this->mailhandling[0]['serverport']);
		}
		// Add the Servertype
		$mBox .= ('/'.$this->serverTypes[($this->mailhandling[0]['servertype'])]);
		$mBox .= '/norsh'; // do not use rsh or ssh to establish connection
		// Close the server ID
		$mBox .= '}';

		// Add the default incoming folder or the one specified
		if(empty($this->mailhandling[0]['folder']))
		{
			$mBox .= 'INBOX';
		}
		else
		{
			$mBox .= $this->mailhandling[0]['folder'];
		}
		return $mBox;
	}

	/**
	 * Get all mails from the server. Invoked by the async timer
	 *
	 * @return boolean true=run finished, false=an error occured
	 */
	function check_mail()
	{
		if (!($this->mbox = @imap_open($this->mailBox,
									$this->mailhandling[0]['username'],
									$this->mailhandling[0]['password'])))
		{
			$show_failed = true;
			// try novalidate cert, in case of ssl connection
			if ($this->mailhandling[0]['servertype']==2)
			{
				$this->mailBox = str_replace('/ssl','/ssl/novalidate-cert',$this->mailBox);
				if (($this->mbox = @imap_open($this->mailBox,$this->mailhandling[0]['username'],$this->mailhandling[0]['password']))) $show_failed=false;
			}
			if ($show_failed)
			{
				error_log(__FILE__.','.__METHOD__." failed to open mailbox:".print_r($this->mailBox,true));
				return false;
			}
		}

		// There seems to be a bug in imap_seach() (#48619) that causes a SegFault if all msg match
		// This was introduced in v5.2.10 and fixed in v5.2.11, so use a workaround in 5.2.10
		//
		if (empty($this->mailhandling[0]['address']) || (version_compare(PHP_VERSION, '5.2.10') === 0))
		{
			// Use sort here to ensure the format returned equals search
			$this->msgList = imap_sort ($this->mbox, SORTARRIVAL, 1);
		}
		else
		{
			$this->msgList = imap_search ($this->mbox, 'TO "' . $this->mailhandling[0]['address'] . '"');
		}

		if ($this->msgList)
		{
			$_cnt = count ($this->msgList);
			for ($_idx = 0; $_idx < $_cnt; $_idx++)
			{
				if ($this->msgList[$_idx])
				if (self::process_message($this->msgList[$_idx]) && $this->mailhandling[0]['delete_from_server'])
				{
					@imap_delete($this->mbox, $this->msgList[$_idx]);
				}
			}
		}
		// Expunge delete mails, if any
		@imap_expunge($this->mbox);

		// Close the stream
		@imap_close($this->mbox);

		// Restore original user (for fallback)
		$this->user = $this->originalUser;
	}

	/**
	 * determines the mime type of a eMail in accordance to the imap_fetchstructure
	 * found at http://www.linuxscope.net/articles/mailAttachmentsPHP.html
	 * by Kevin Steffer
	 */
	function get_mime_type(&$structure)
	{
		$primary_mime_type = array("TEXT", "MULTIPART","MESSAGE", "APPLICATION", "AUDIO","IMAGE", "VIDEO", "OTHER");
		if($structure->subtype) {
			return $primary_mime_type[(int) $structure->type] . '/' .$structure->subtype;
		}
		return "TEXT/PLAIN";
	}

	function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false)
	{
		//error_log(__METHOD__." getting body for ID: $msg_number, $mime_type, $part_number");
		if(!$structure) {
			//error_log(__METHOD__." fetching structure, as no structure passed.");
			$structure = imap_fetchstructure($stream, $msg_number);
		}
		if($structure)
		{
			if($mime_type == $this->get_mime_type($structure))
			{
				if(!$part_number)
				{
					$part_number = "1";
				}
				//error_log(__METHOD__." mime type matched. Part $part_number.");
				$struct = imap_bodystruct ($stream, $msg_number, "$part_number");
				$body = imap_fetchbody($stream, $msg_number, $part_number);
				return array('struct'=> $struct,
							 'body'=>$body,
							);
			}

			if($structure->type == 1) /* multipart */
			{
				while(list($index, $sub_structure) = each($structure->parts))
				{
					if($part_number)
					{
						$prefix = $part_number . '.';
					}
					$data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure,$prefix.($index + 1));
					if($data && !empty($data['body']))
					{
						return $data;
					}
				} // END OF WHILE
			} // END OF MULTIPART
		} // END OF STRUTURE
		return false;
	} // END OF FUNCTION

	/**
	 * Retrieve and decode a bodypart
	 *
	 * @param int Message ID from the server
	 * @param string The body part, defaults to "1"
	 * @return string The decoded bodypart
	 */
	function get_mailbody ($mid, $section=false, $structure = false)
	{
		$nonDisplayAbleCharacters = array('[\016]','[\017]',
			'[\020]','[\021]','[\022]','[\023]','[\024]','[\025]','[\026]','[\027]',
			'[\030]','[\031]','[\032]','[\033]','[\034]','[\035]','[\036]','[\037]');

		//error_log(__METHOD__." Fetching body for ID $mid, Section $section with Structure: ".print_r($structure,true));
		$charset = $GLOBALS['egw']->translation->charset(); // set some default charset, for translation to use
		$mailbodyasAttachment = false;
		if(function_exists(mb_decode_mimeheader)) {
			mb_internal_encoding($charset);
		}
		if ($section === false)
		{
			$part_number = 1;
		}
		else
		{
			$part_number = $section;
			$mailbodyasAttachment = true;
		}
		if ($structure === false) $structure = imap_fetchstructure($this->mbox, $mid);
		if($structure) {
			$mimeType = 'TEXT/PLAIN';
			$rv = $this->get_part($this->mbox, $mid, $mimeType, $structure,($section ? $part_number:false));
			$struct = $rv['struct'];
			$body = $rv['body'];
			if (empty($body))
			{
				$mimeType = 'TEXT/HTML';
				$rv = $this->get_part($this->mbox, $mid, $mimeType, $structure,($section ? $part_number:false));
				$struct = $rv['struct'];
				$body = $rv['body'];
			}
			//error_log(__METHOD__. "->get_part returned: ".print_r($rv,true));
			/*
			error_log($this->get_part($this->mbox, $mid, 'TEXT/HTML', $structure,2));
			error_log($this->get_part($this->mbox, $mid, 'TEXT/HTML', $structure,"2.1"));
			error_log($this->get_part($this->mbox, $mid, 'TEXT/HTML', $structure,3));
			error_log($this->get_part($this->mbox, $mid, 'TEXT/HTML', $structure,"3.1"));
			*/
			if (self::LOG_LEVEL) error_log(__METHOD__.print_r($structure,true));
			if (self::LOG_LEVEL>1) error_log(__METHOD__.print_r($struct,true));
			if (self::LOG_LEVEL>2) error_log(__METHOD__.print_r($body,true));
			if (isset($struct->ifparameters) && $struct->ifparameters == 1)
			{
				//error_log(__METHOD__.__LINE__.print_r($param,true));
				while(list($index, $param) = each($struct->parameters))
				{
					if (strtoupper($param->attribute) == 'CHARSET') $charset = $param->value;
				}
			}
			switch ($struct->encoding)
			{
				case 0: // 7 BIT
					//dont try to decode, as we do use convert anyway later on
					//$body = imap_utf7_decode($body);
					break;
				case 1: // 8 BIT
					if ($struct->subtype == 'PLAIN' && strtolower($charset) != 'iso-8859-1') {
						// only decode if we are at utf-8, not sure that we should decode at all, since we use convert anyway
						//$body = utf8_decode ($body);
					}
					break;
				case 2: // Binary
					$body = imap_binary($body);
					break;
				case 3: //BASE64
					$body = imap_base64($body);
					break;
				case 4: // QUOTED Printable
					$body = quoted_printable_decode($body);
					break;
				case 5: // other
				default:
					break;
			}
			$GLOBALS['egw']->translation->convert($body,$charset);
			if ($mimeType=='TEXT/PLAIN')
			{
				$newBody    = @htmlentities($body,ENT_QUOTES, strtoupper($charset));
				// if empty and charset is utf8 try sanitizing the string in question
				if (empty($newBody) && strtolower($charset)=='utf-8') $newBody = @htmlentities(iconv('utf-8', 'utf-8', $body),ENT_QUOTES, strtoupper($charset));
				// if the conversion to htmlentities fails somehow, try without specifying the charset, which defaults to iso-
				if (empty($newBody)) $newBody    = htmlentities($body,ENT_QUOTES);
				$body = $newBody;
			}
			$body = preg_replace($nonDisplayAbleCharacters,'',$body);

			// handle Attachments
			$contentParts = count($structure->parts);
			$additionalAttachments = array();
			$attachments = array();
			if($structure->type == 1 && $contentParts >=2) /* multipart */
			{
				$att = array();
				$partNumber = array();
				for ($i=2;$i<=$contentParts;$i++)
				{
					//error_log(__METHOD__. " --> part ".$i);
					$att[$i-2] = imap_bodystruct($this->mbox,$mid,$i);
					$partNumber[$i-2] = array('number' => $i,
											'substruct' => $structure->parts[$i-1],
										);
				}
				for ($k=0; $k<sizeof($att);$k++)
				{
					//error_log(__METHOD__. " processing part->".$k." Message Part:".print_r($partNumber[$k],true));
					if ($att[$k]->ifdisposition == 1 && strtoupper($att[$k]->disposition) == 'ATTACHMENT')
					{
						//$num = count($attachments) - 1;
						$num = $k;
						if ($num < 0) $num = 0;
						$attachments[$num]['type'] = $this->get_mime_type($att[$k]);
						//error_log(__METHOD__. " part:".print_r($att[$k],true));
						// type2 = Message; get mail as attachment, with its attachments too
						if ($att[$k]->type == 2)
						{
							//error_log(__METHOD__. " part $k ->".($section ? $part_number.".".$partNumber[$k]['number']:$partNumber[$k]['number'])." is MESSAGE:".print_r($partNumber[$k]['substruct']->parts[0],true));
							$rv = $this->get_mailbody($mid,($section ? $part_number.".".$partNumber[$k]['number']:$partNumber[$k]['number']) , $partNumber[$k]['substruct']->parts[0]);
							$attachments[$num]['attachment'] = $rv['body'];
							$attachments[$num]['type'] = $this->get_mime_type($rv['struct']);
							if ($att[$k]->ifparameters)
							{
								//error_log(__METHOD__. " parameters exist:");
								while(list($index, $param) = each($att[$k]->parameters))
								{
									//error_log(__METHOD__.__LINE__.print_r($param,true));
									if (strtoupper($param->attribute) == 'NAME') $attachments[$num]['name'] = $param->value;
								}
							}
							if ($att[$k]->ifdparameters)
							{
								//error_log(__METHOD__. " dparameters exist:");
								while(list($index, $param) = each($att[$k]->dparameters))
								{
									//error_log(__METHOD__.__LINE__.print_r($param,true));
									if (strtoupper($param->attribute) == 'FILENAME') $attachments[$num]['filename'] = $param->value;
								}
							}
							$att[$k] = $rv['struct'];
							if (!empty($rv['attachments'])) for ($a=0; $a<sizeof($rv['attachments']);$a++) $additionalAttachments[] = $rv['attachments'][$a];
							if (empty($attachments[$num]['attachment']) && empty($rv['attachments']))
							{
								unset($attachments[$num]);
								continue;  // no content -> skip
							}
						}
						else
						{
							$attachments[$num]['attachment'] = imap_fetchbody($this->mbox,$mid,$k+2);
							if (empty($attachments[$num]['attachment']))
							{
								unset($attachments[$num]);
								continue; // no content -> skip
							}
							if ($att[$k]->ifparameters)
							{
								//error_log(__METHOD__. " parameters exist:");
								while(list($index, $param) = each($att[$k]->parameters))
								{
									//error_log(__METHOD__.__LINE__.print_r($param,true));
									if (strtoupper($param->attribute) == 'CHARSET') $attachments[$num]['charset'] = $param->value;
									if (strtoupper($param->attribute) == 'NAME') $attachments[$num]['name'] = $param->value;
								}
							}
							if ($att[$k]->ifdparameters)
							{
								//error_log(__METHOD__. " dparameters exist:");
								while(list($index, $param) = each($att[$k]->dparameters))
								{
									//error_log(__METHOD__.__LINE__.print_r($param,true));
									if (strtoupper($param->attribute) == 'FILENAME') $attachments[$num]['filename'] = $param->value;
								}
							}
						}
						$this->decode_header($attachments[$num]['filename']);
						$this->decode_header($attachments[$num]['name']);
						if (empty($attachments[$num]['name'])) $attachments[$num]['name'] = $attachments[$num]['filename'];
						if (empty($attachments[$num]['name']))
						{
							$attachments[$num]['name'] = 'noname_'.$num;
							$st = '';
							if (strpos($attachments[$num]['type'],'/')!==false) list($t,$st) = explode('/',$attachments[$num]['type'],2);
							if (!empty($st)) $attachments[$num]['name'] = $attachments[$num]['name'].'.'.$st;
						}
						$attachments[$num]['tmp_name'] = tempnam($GLOBALS['egw_info']['server']['temp_dir'],$GLOBALS['egw_info']['flags']['currentapp']."_");
						$tmpfile = fopen($attachments[$num]['tmp_name'],'w');
						fwrite($tmpfile,((substr(strtolower($attachments[$num]['type']),0,4) == "text") ? $attachments[$num]['attachment']: imap_base64($attachments[$num]['attachment'])));
						fclose($tmpfile);
						unset($attachments[$num]['attachment']);
						//error_log(__METHOD__.print_r($attachments[$num],true));
					}
				}
			}
		}
		//if (!empty($attachments)) error_log(__METHOD__." Attachments with this mail:".print_r($attachments,true));
		if (!empty($additionalAttachments))
		{
			//error_log(__METHOD__." Attachments retrieved with attachments:".print_r($additionalAttachments,true));
			for ($a=0; $a<sizeof($additionalAttachments);$a++) $attachments[] = $additionalAttachments[$a];
		}
		return array('body' => $GLOBALS['egw']->translation->convertHTMLToText(html::purify($body)),
					 'struct' => $struct,
					 'attachments' =>  $attachments
					);
	}

	/**
	 * Decode a mail header
	 *
	 * @param string Pointer to the (possibly) encoded header that will be changes
	 */
	function decode_header (&$header)
	{
		$header = translation::decodeMailHeader($header);
	}

	/**
	 * Process a messages from the mailbox
	 *
	 * @param int Message ID from the server
	 * @return boolean true=message successfully processed, false=message couldn't or shouldn't be processed
	 */
	function process_message ($mid)
	{
		$senderIdentified = false;
		$this->mailBody = null; // Clear previous message
		$msgHeader = imap_headerinfo($this->mbox, $mid);

		// Workaround for PHP bug#48619
		//
		if (!empty($this->mailhandling[0]['address']) && (version_compare(PHP_VERSION, '5.2.10') === 0))
		{
			if (strstr($msgHeader->toaddress, $msgHeader->toaddress) === false)
			{
				return false;
			}
		}
		/*
		 # Recent - R if recent and seen (Read), N if recent and unseen (New), ' ' if not recent
		 # Unseen - U if not recent AND unseen, ' ' if seen  OR unseen and recent.
		 # Flagged - F if marked as important/urgent, else ' '
		 # Answered - A if Answered, else ' '
		 # Deleted - D if marked for deletion, else ' '
		 # Draft - X if marked as draft, else ' '
		 */

		if ($msgHeader->Deleted == 'D')
		{
			return false; // Already deleted
		}
		/*
		if ($msgHeader->Recent == 'R' ||		// Recent and seen or
				($msgHeader->Recent == ' ' &&	// not recent but
				$msgHeader->Unseen == ' '))		// seen
		*/
		// should do the same, but is more robust as recent is a flag with some sideeffects
		// message should be marked/flagged as seen after processing
		// (don't forget to flag the message if forwarded; as forwarded is not supported with all IMAP use Seen instead)
		if ((($msgHeader->Recent == 'R' || $msgHeader->Recent == ' ') && $msgHeader->Unseen == ' ') ||
			($msgHeader->Answered == 'A' && $msgHeader->Unseen == ' ') || // is answered and seen
			$msgHeader->Draft == 'X') // is Draft
		{
			if (self::LOG_LEVEL>1) error_log(__FILE__.','.__METHOD__.':'."\n".' Subject:'.print_r($msgHeader->subject,true).
				"\n Date:".print_r($msgHeader->Date,true).
	            "\n Recent:".print_r($msgHeader->Recent,true).
	            "\n Unseen:".print_r($msgHeader->Unseen,true).
	            "\n Flagged:".print_r($msgHeader->Flagged,true).
	            "\n Answered:".print_r($msgHeader->Answered,true).
	            "\n Deleted:".print_r($msgHeader->Deleted,true)."\n Stopped processing Mail. Not recent, new, or already answered, or deleted");
			return false;
		}

		if (self::LOG_LEVEL>1) error_log(__FILE__.','.__METHOD__.' Subject:'.print_r($msgHeader,true));
		// Try several headers to identify the sender
		$try_addr = array(
			0 => $msgHeader->from[0],
			1 => $msgHeader->sender[0],
			2 => $msgHeader->return_path[0],
			3 => $msgHeader->reply_to[0],
			// Users mentioned addresses where not recognized. That was not
			// reproducable by me, so these headers are a trial-and-error apprach :-S
			4 => $msgHeader->fromaddress,
			5 => $msgHeader->senderaddress,
			6 => $msgHeader->return_pathaddress,
			7 => $msgHeader->reply_toaddress,
		);

		foreach ($try_addr as $id => $sender)
		{
			if (($extracted = self::extract_mailaddress (
					(is_object($sender)
						? $sender->mailbox.'@'.$sender->host
						: $sender))) !== false)
			{
				if ($id == 3)
				{
					// Save the reply-to address in case the mailaddress should be
					// added to the CC field.
					$replytoAddress = $extracted;
				}
				if (($senderIdentified = self::search_user($extracted)) === true)
				{
					// Save the reply-to address if we found match for use with the
					// auto-reply
					$replytoAddress = $extracted;
				}
			}
			if ($senderIdentified === true)
			{
				break;
			}
		}

		// Handle unrecognized mails
		if (!$senderIdentified)
		{
			switch ($this->mailhandling[0]['unrecognized_mails'])
			{
				case 'ignore' :		// Do nothing
					return false;
					break;
				case 'delete' :		// Delete, whatever the overall delete setting is
					@imap_delete($this->mbox, $mid);
					return false;	// Prevent from a second delete attempt
					break;
				case 'forward' :	// Return the status of the forward attempt
					$returnVal = self::forward_message($mid, $msgHeader);
					if ($returnVal) $status = $this->flagMessageAsSeen($mid, $msgHeader);
					return $returnVal;
					break;
				case 'default' :	// Save as default user; handled below
				default :			// Duh ??
					break;
			}
		}

		$this->mailSubject = $msgHeader->subject;
		$this->decode_header ($this->mailSubject);
		$this->ticketId = self::get_ticketId($this->mailSubject);

		if ($this->ticketId == 0) // Create new ticket?
		{
			if (empty($this->mailhandling[0]['default_tracker']))
			{
				return false; // Not allowed
			}
			if (!$senderIdentified) // Unknown user
			{
				if (empty($this->mailhandling[0]['unrec_mail']))
				{
					return false; // Not allowed for unknown users
				}
				$this->mailSender = $this->mailhandling[0]['unrec_mail']; // Ok, set default user
			}
		}

		// By the time we get here, we know this ticket will be updated or created
		$rv = $this->get_mailbody ($mid);
		//error_log(__METHOD__.print_r($rv,true));
		$this->mailBody = $rv['body'];
		// as we read the mail here, we should mark it as seen \Seen, \Answered, \Flagged, \Deleted  and \Draft are supported
		$status = $this->flagMessageAsSeen($mid, $msgHeader);

		if ($this->ticketId == 0)
		{
			$this->init();
			$this->user = $this->mailSender;
			$this->data['tr_summary'] = $this->mailSubject;
			$this->data['tr_tracker'] = $this->mailhandling[0]['default_tracker'];
			$this->data['cat_id'] = $this->mailhandling[0]['default_cat'];
//			$this->data['tr_version'] = $this->mailhandling[0]['default_version'];
			$this->data['tr_priority'] = 5;
			$this->data['tr_description'] = $this->mailBody;
			if (!$senderIdentified && $this->mailhandling[0]['auto_cc'])
			{
				$this->data['tr_cc'] = $replytoAddress;
			}
		}
		else
		{
			$this->read($this->ticketId);
			if (!$senderIdentified)
			{
				switch ($this->mailhandling[0]['unrec_reply'])
				{
					case 0 :
						$this->user = $this->data['tr_creator'];
						break;
					case 1 :
						$this->user = 0;
						break;
					default :
						$this->user = 0;
						break;
				}
			}
			else
			{
				$this->user = $this->mailSender;
			}
			if ($this->mailhandling[0]['auto_cc'] && stristr($this->data['tr_cc'], $replytoAddress) === FALSE)
			{
				$this->data['tr_cc'] .= (empty($this->data['tr_cc'])?'':',').$replytoAddress;
			}
			$this->data['reply_message'] = $this->mailBody;

		}
		$this->data['tr_status'] = parent::STATUS_OPEN; // If the ticket isn't new, (re)open it anyway

		// Save the ticket and let tracker_bo->save() handle the autorepl, if required
		$saverv = $this->save(null,
			(($this->mailhandling[0]['auto_reply'] == 2		// Always reply or
			|| ($this->mailhandling[0]['auto_reply'] == 1	// only new tickets
				&& $this->ticketId == 0)					// and this is a new one
				) && (										// AND
					$senderIdentified		 				// we know this user
				|| (!$senderIdentified						// or we don't and
				&& $this->mailhandling[0]['reply_unknown'] == 1 // don't care
			))) == true
				? array(
					'reply_text' => $this->mailhandling[0]['reply_text'],
					// UserID or mail address
					'reply_to' => ($this->user ? $this->user : $replytoAddress),
				)
				: null
		);

		if (($saverv==0) && is_array($rv['attachments']))
		{
			foreach ($rv['attachments'] as $attachment)
			{
				if(is_readable($attachment['tmp_name']))
				{
					egw_link::attach_file('tracker',$this->data['tr_id'],$attachment);
				}
			}
		}

		return !$saverv;
	}

	/**
	 * flag message after processing
	 *
	 */
	function flagMessageAsSeen($mid, $messageHeader)
	{
		return imap_setflag_full($this->mbox, $mid, "\\Seen".($messageHeader->Flagged == 'F' ? "\\Flagged" : ""));
	}

	/**
	 * Get an email address in plain format, no matter how the address was specified
	 *
	 * @param string $addr a string (probably) containing an email address
	 */
	function extract_mailaddress($addr='')
	{
		if (empty($addr))
		{
			return false;
		}
		//preg_match_all("/[a-zA-Z0-9_\-\.]+?@([a-zA-Z0-9_\-]+?\.)+?[a-zA-Z]{2,}/", $addr, $address);
		preg_match_all("/([A-Za-z0-9][A-Za-z0-9._-]*)?[A-Za-z0-9]@([A-Za-z0-9ÄÖÜäöüß](|[A-Za-z0-9ÄÖÜäöüß_-]*[A-Za-z0-9ÄÖÜäöüß])\.)+[A-Za-z]{2,6}/", $addr, $address);
		return ($address[0][0]);
	}

	/**
	 * Retrieve the user ID based on the mail address that was extracted from the mailheaders
	 *
	 * @param string $mail_addr, the mail address.
	 */
	function search_user($mail_addr='')
	{
		$this->mailSender = null; // Make sure previous msg data is cleared

		$acc_search = array(
			'type' => 'accounts',
//			'app' => 'tracker', // Make this a config item?
			'query' => $mail_addr,
			'query_type' => 'email',
		);
		$account_info = $GLOBALS['egw']->accounts->search($acc_search);
		$match_cnt = $GLOBALS['egw']->accounts->total;

		if ($match_cnt != 1) {
			// No matches (0) or ambigious (>1)
			return false;
		}

		$first_match = array_shift($account_info); // shift, since the key is numeric, so [0] won't work
		$this->mailSender = $first_match['account_id'];
		return true;
	}

	/**
	 * Try to extract a ticket number from a subject line
	 *
	 * @param string the subjectline from the incoming message
	 * @return int ticket ID, or 0 of no ticket ID was recognized
	 */
	function get_ticketId($subj='')
	{
		if (empty($subj))
		{
			return 0; // Don't bother...
		}

		// The subject line is expected to be in the format:
		// [Re: |Fwd: |etc ]<Tracker name> #<id>: <Summary>
		// allow colon or dash to separate Id from summary, as our notifications use a dash (' - ') and not a colon (': ')
		preg_match_all("/(.*)( #[0-9]+:? ?-? )(.*)$/",$subj, $tr_data);
		if (!$tr_data[2])
		{
			return 0; //
		}

		preg_match_all("/[0-9]+/",$tr_data[2][0], $tr_id);
		$tracker_id = $tr_id[0][0];

		$trackerData = $this->search(array('tr_id' => $tracker_id),'tr_summary');

		// Use strncmp() here, since a Fwd might add a sqr bracket.
		if (strncmp($trackerData[0]['tr_summary'], $tr_data[3][0], strlen($trackerData[0]['tr_summary'])))
		{
			return 0; // Summary doesn't match. Should this be ok?
		}
		return $tracker_id;
	}

	/**
	 * Forward a mail that was not recognized
	 *
	 * @param int message ID from the server
	 * @return boolean status
	 */
	function forward_message($mid=0, &$headers=null)
	{
		if ($mid == 0 || $headers == null) // no data
		{
			return false;
		}

		// Sending mail is not implemented using notifations, since it's pretty straight forward here
		$to   = $this->mailhandling[0]['forward_to'];
		$subj = $headers->subject;
		$body = imap_body($this->mbox, $mid, FK_INTERNAL);
		$hdrs = 'From: ' . $headers->fromaddress . "\r\n" .
				'Reply-To: ' . $headers->reply_toaddress . "\r\n";

		return (mail($to, $subj, $body, $hdrs));
	}

	/**
	 * Check if exist and if not start or stop an async job to check incoming mails
	 *
	 * @param int $interval=1 >0=start, 0=stop
	 */
	static function set_async_job($interval=0)
	{
		$async = new asyncservice();

		// Make sure an existing timer is cancelled
		$async->cancel_timer('tracker-check-mail');

		if ($interval > 0)
		{
			if ($interval == 60)
			{
				$async->set_timer(array('hour' => '*'),'tracker-check-mail','tracker.tracker_mailhandler.check_mail',null);
			}
			else
			{
				$async->set_timer(array('min' => "*/$interval"),'tracker-check-mail','tracker.tracker_mailhandler.check_mail',null);
			}
		}
	}
}
