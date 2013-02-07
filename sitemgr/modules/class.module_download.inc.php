<?php
/**
 * sitemgr - Download from VFS block
 *
 * @link http://www.egroupware.org
 * @author Cornelius Weiss <egw@von-und-zu-weiss.de> based on old sitemgr module
 * @author Ralf Becker <RalfBecker(at)outdoor-training.de> updated to new vfs
 * @package sitemgr
 * @subpackage modules
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.module_download.inc.php 33996 2011-03-03 15:49:28Z ralfbecker $
 */

/**
 * Download from VFS block
 */
class module_download extends Module
{
	function module_download()
	{
		$this->arguments = array (
			'format' => array (
				'type'  => 'select',
				'label' => lang('Choose a format'),
				'options' => array (
					'file' => lang('Single file download'),
					'dir' => lang('Show contents of a directory'),
					'dirnsub' => lang('Show contents of a directory with subdirectories'),
					'recursive' => lang('Show files including the ones from subdirectories'),
				),
				'*switch-hide*' => array(
					'file' => array('showpath','order','upload','showcomments'),
					'*default*' => array('file','text'),
				)
			),
			'path' => array (
				'type' => 'textfield',
				'label' => lang('The path to the file to be downloaded'),
				'params' => array('size' => 60),
			),
			'showpath' => array (
				'type' => 'checkbox',
				'label' => lang('show path?'),
			),
			'order' => array(
				'type' => 'select',
				'label' => lang('Sort files by'),
				'options' => array(
					'name asc'   => lang('Name').': '.lang('ascending'),
					'name desc'  => lang('Name').': '.lang('descending'),
					'mime asc'   => lang('Type').': '.lang('ascending'),
					'mime desc'  => lang('Type').': '.lang('descending'),
					'size asc'   => lang('Size').': '.lang('ascending'),
					'size desc'  => lang('Size').': '.lang('descending'),
					'mtime asc'  => lang('Date').': '.lang('oldest first'),
					'mtime desc' => lang('Date').': '.lang('newest first'),
				),
			),
			'file' => array (
				'type' => 'textfield',
				'label' => lang('The file to be downloaded'),
			),
			'text' => array (
				'type' => 'textfield',
				'label' => lang('The text for the link, if empty the module returns the raw URL (without a link)'),
				'params' => array('size' => 60),
			),
			'upload' => array(
				'type' => 'checkbox',
				'label' => lang('Show a file upload (if user has write rights to current directory)'),
			),
			'showcomments' => array (
				'type' => 'checkbox',
				'label' => lang('Show comments?'),
			),
			'confirmation' => array (
				'type' => 'textfield',
				'label' => lang('Text for optional confirmation message, before download get displayed'),
				'params' => array('size' => 60),
			),
		);
		$this->post = array (
			'subdir' => array('type' => 'textfield'),
			'delete' => array('type' => 'textfield')
		);
		$this->get = array ('subdir','uploading');
		$this->title = lang('File download');
		$this->description = lang('This module create a link for downloading a file(s) from the VFS');
	}

	function get_content(&$arguments, $properties)
	{
		translation::add_app('filemanager');

		if (substr($arguments['path'],-1) == '/')
		{
			$arguments['path'] = substr($arguments['path'], 0, -1);
		}
		$out = '';
		switch ($arguments['format'])
		{
			case 'dirnsub' :
				if ($arguments['subdir'])
				{
					$arguments['path'] = $arguments['path'].'/'.$arguments['subdir'];
				}
				// fall through
			case 'dir' :
			case 'recursive':
				if (!egw_vfs::file_exists($arguments['path']) || !egw_vfs::is_readable($arguments['path']))
				{
					return '<p style="color: red;"><i>'.lang('The requested path %1 is not available.',htmlspecialchars($arguments['path']))."</i></p>\n";
				}
				$show_upload = $arguments['upload'] && egw_vfs::is_writable($arguments['path']);

				//$out .= '<pre>'.print_r($arguments,true)."</pre>\n";
				if ($arguments['uploading'] && $show_upload)
				{
					foreach((array)$_FILES['upload'] as $name => $data)
					{
						$upload[$name] = $data[$this->block->id];
					}
					$to = $arguments['path'].'/'.$upload['name'];
					if (is_uploaded_file($upload['tmp_name']) &&
						(egw_vfs::is_writable($arguments['path']) || egw_vfs::is_writable($to)) &&
						copy($upload['tmp_name'],egw_vfs::PREFIX.$to))
					{
						$out .= '<p style="color: red;"><i>'.lang('File successful uploaded.')."</i></p>\n";
					}
					else
					{
						$out .= '<p style="color: red;"><i>'.lang('Error uploading file!').'<br />'.etemplate::max_upload_size_message()."</i></p>\n";
					}
				}
				if ($show_upload && $GLOBALS['egw_info']['user']['apps']['filemanager'] && $arguments['delete'])
				{
					translation::add_app('filemanager');
					$out .= '<p style="color: red;"><i>'.filemanager_ui::do_delete(array_flip($arguments['delete']))."</i></p>\n";
				}
				if ($arguments['showpath'])
				{
					$out .= '<p>'.lang('Path').': '.htmlspecialchars($arguments['path']).'</p><hr />';
				}
				list($order,$sort) = explode(' ',$arguments['order']);

				$ls_dir = egw_vfs::find($arguments['path'],array(
					'need_mime' => true,
					'maxdepth' => $arguments['format'] != 'recursive' ? 1 : null,
					'type' => $arguments['format'] != 'dirnsub' ? 'f' : null,
					'order' => $order ? $order : 'name',
					'sort' => $sort == 'desc' ? 'DESC' : 'ASC',
				),true);

				if ($show_upload)
				{
					translation::add_app('filemanager');
					$out .= '<form name="upload" action="'.$this->link(array(
						'subdir' => $arguments['subdir'],
					)).'" method="POST" enctype="multipart/form-data">';
				}
				$out .= '<table class="moduletable">
						<tr>
							<td width="1%">'./*mime png*/ ''.'</td>
							<td>'.lang('Filename').'</td>
							<td>'.($arguments['showcomments'] ? lang('Comment') : '').'</td>
							<td align="right">'.lang('Size').'</td>
							<td align="center">'.lang('Date').'</td>
							<td>'.($show_upload && $GLOBALS['egw_info']['user']['apps']['filemanager'] ? lang('Action') : '').'</td>
						</tr>
						<tr><td height="1px" colspan="6"><hr /></td></tr>';

				if ($arguments['subdir'] && $arguments['format'] == 'dirnsub')
				{
					$out .= '<tr>
							<td>..</td>
							<td colspan="5"><a href="'.htmlspecialchars($this->link(array ('subdir' => strrchr($arguments['subdir'], '/') ?
								substr($arguments['subdir'], 0, strlen($arguments['subdir']) - strlen(strrchr($arguments['subdir'], '/'))) :
								 false))).'">'.lang('parent directory').'</a>
							</td>
						</tr>';
				}

				$dateformat = $GLOBALS['egw_info']['user']['preferences']['common']['dateformat'].
					($GLOBALS['egw_info']['user']['preferences']['common']['timeformat'] != 12 ? ' H:i' : 'h:ia');

				if ($arguments['showcomments'])	// query properties / comments
				{
					$props = egw_vfs::propfind(array_keys($ls_dir));
				}
				foreach ($ls_dir as $path => &$file)
				{
					if ($props && isset($props[$path]))
					{
						foreach($props[$path] as $prop)
						{
							$file[$prop['name']] = $prop['val'];
						}
					}
					if ($show_upload && $GLOBALS['egw_info']['user']['apps']['filemanager'])
					{
						$file['action'] = html::submit_button('block['.$this->block->id.'][delete]['.$path.']',lang('Delete'),
							"return confirm('".lang('Delete this file or directory')."?')",
							false,'title="'.lang('Delete').'"','delete');
						$link = egw::link('/index.php',array(
							'menuaction' => 'filemanager.filemanager_ui.file',
							'path' => $path,
						));
						$file['action'] .= html::submit_button('','Edit',
							"egw_openWindowCentered2('$link', 'fileprefs', 495, 425, 'yes'); return false;",
							false,'title="'.lang('Edit').'"','edit');
					}
					if ($file['mime'] == egw_vfs::DIR_MIME_TYPE)
					{
						if ($arguments['format'] == 'dirnsub' && $file['name'])
						{
							$out .= '<tr>
									<td>'.egw_vfs::mime_icon($file['mime'],false).'</td>
									<td><a href="'.$this->link(array ('subdir' => $arguments['subdir'] ?
										$arguments['subdir'].'/'.$file['name'] : $file['name'])).'">'.
										egw_vfs::decodePath($file['name']).'</a></td>
									<td>'.$file['comment'].'</td>
									<td align="right">'./*egw_vfs::hsize($file['size']).*/'</td>
									<td>'. date($dateformat,$file['mtime'] ? $file['mtime'] : $file['ctime']).'</td>
									<td>'.$file['action'].'</td>
								</tr>';
						}
						unset ($ls_dir[$path]);
					}
				}

				foreach ($ls_dir as $path => &$file)
				{
					$link = egw_vfs::download_url($path,$arguments['op'] == 2);
					if ($link[0] == '/') $link = egw::link($link);
					$out .= '<tr>
							<td>'.egw_vfs::mime_icon($file['mime'],false).'</td>
							<td><a href="'.htmlspecialchars($link).'" target="_blank">'.egw_vfs::decodePath($file['name']).'</a></td>
							<td>'.$file['comment'].'</td>
							<td align="right">'.egw_vfs::hsize($file['size']).'</td>
							<td>'. date($dateformat,$file['mtime'] ? $file['mtime'] : $file['ctime']).'</td>
							<td>'.$file['action'].'</td>
						</tr>';
				}
				$out .= '</table>';

				if ($arguments['upload'] && egw_vfs::is_writable($arguments['path']))
				{
					$out .= '<hr />';
					// uploading=1 --> mark form submit as fileupload, to be able to detect when it failed (eg. because of upload limits)
					$out .= html::input('upload['.$this->block->id.']','','file',' onchange="this.form.action+=\'&block['.$this->block->id.'][uploading]=1\'; this.form.submit();"');
					$out .= "</form>\n";
				}
				break;

			case 'file' :
			default :
				$link = egw_vfs::download_url($arguments['path'].'/'.$arguments['file'],$arguments['op'] == 2);
				if ($link[0] == '/') $link = egw::link($link);
				$out = $arguments['text'] ? ('<a href="'.htmlspecialchars($link).'" target="_blank">'.$arguments['text'].'</a>') : $link;
				break;
		}
		// if (optional) confirmation text given, hide download until user checks confirmation message
		if ($arguments['confirmation'])
		{
			$div_id = 'content['.$this->block->id.']';
			$check_id = 'confirm['.$this->block->id.']';
			$onchange = "document.getElementById('$div_id').style.display = this.checked ? 'block' : 'none';";

			$out = html::div(html::input('','1','checkbox',' id="'.htmlspecialchars($check_id).'" onchange="'.$onchange.'"')."\n".
				html::label($arguments['confirmation'],$check_id)."\n".
				html::div($out,' id="'.$div_id.'" style="display: none;"'));
		}
		return $out;
	}
}
