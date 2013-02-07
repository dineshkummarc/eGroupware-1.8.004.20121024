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

/* $Id: class.xslt_transform.inc.php 26463 2008-11-27 06:02:49Z ralfbecker $ */

// some constanst for pre php4.3
if (!defined('PHP_SHLIB_SUFFIX'))
{
	define('PHP_SHLIB_SUFFIX',strtoupper(substr(PHP_OS, 0,3)) == 'WIN' ? 'dll' : 'so');
}
if (!defined('PHP_SHLIB_PREFIX'))
{
	define('PHP_SHLIB_PREFIX',PHP_SHLIB_SUFFIX == 'dll' ? 'php_' : '');
}

class xslt_transform
{
	var $arguments;

	function xslt_transform($xsltfile,$xsltparameters=NULL)
	{
		//$this->xsltfile = $xsltfile;
		$this->xsltparameters = $xsltparameters;
		$this->xslcontent = file_get_contents($xsltfile);

	    if (PHP_VERSION >=5)
	    {
	  		$this->xslt_extension_availible = extension_loaded('xsl') || @dl(PHP_SHLIB_PREFIX.'xsl.'.PHP_SHLIB_SUFFIX);
	    }
	    else
	    {
	  		$this->xslt_extension_availible = extension_loaded('xslt') || @dl(PHP_SHLIB_PREFIX.'xslt.'.PHP_SHLIB_SUFFIX);
	    }
	}

	function apply_transform($title,$content)
	{
		if (!$this->xslt_extension_availible)
		{
			return 'The xslt_transformation used, needs the "xsl" or "xslt" extension of php !!!';
		}
		$xh = xslt_create();
		$xsltarguments = array('/_xml' => $content,
		                       '/_xsl' => $this->xslcontent);
		$result = xslt_process($xh, 'arg:/_xml', 'arg:/_xsl', NULL, $xsltarguments,$this->xsltparameters);
		xslt_free($xh);
		return $result;
	}
}

if (PHP_VERSION >= 5 && !function_exists('xslt_create'))
{
	function xslt_create()
	{
		return new XsltProcessor();
	}

	function xslt_process($xsltproc,$xml_arg,$xsl_arg,$xslcontainer = null,$args = null,$params = null)
	{
		$xml_arg = str_replace('arg:', '', $xml_arg);
		$xsl_arg = str_replace('arg:', '', $xsl_arg);
		$xml = new DomDocument;
		$xsl = new DomDocument;
		$xml->loadXML($args[$xml_arg]);
		$xsl->loadXML($args[$xsl_arg]);
		$xsltproc->importStyleSheet($xsl);
		if ($params)
		{
			foreach ($params as $param => $value)
			{
				$xsltproc->setParameter("", $param, $value);
			}
		}
		$processed = $xsltproc->transformToXML($xml);
		if ($xslcontainer)
		{
			return @file_put_contents($xslcontainer, $processed);
		}
		return $processed;
	}

	function xslt_free($xsltproc)
	{
		unset($xsltproc);
	}
}
