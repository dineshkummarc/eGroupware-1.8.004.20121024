<?php echo "<?xml version=\"1.0\"?>";
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $mosConfig_sitename; ?></title>	
	<?php include ("editor/editor.php"); ?>
	<?php initEditor(); ?>
	<meta http-equiv="Content-Type" content="text/html; <?php echo _ISO; ?>" />
	<?php include ("includes/metadata.php"); ?>
	<link href="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/css/template_css.css" rel="stylesheet" type="text/css" />
	<link rel="shortcut icon" href="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/favicon.ico" />
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
	<a name="up" id="up"></a>
	<center>
	<table width="898"  border="0" cellpadding="0" cellspacing="0">
		<tr>
		<td background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/generic_header.jpg" /><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="5" height="98" align="absmiddle"/><span class="sitetitle"><?php echo $mosConfig_sitename; ?> </span></td>
		</tr>
		<tr>
			<td background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/blocks_bg.gif">
			<table width="898" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td width="174" align="left"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="165" height="30" /></td>
				<td width="391" align="left"><?php include "pathway.php"; ?></td>
				<td width="178" align="right"><?php echo (strftime (_DATE_FORMAT_LC)); ?><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="18" height="5" /></td>
				<form action='<?php echo sefRelToAbs("index.php"); ?>' method='post'>
				<td width="155" align="left">
                        <input class="searchbox" type="text" name="searchword" height="16" size="15" value="<?php echo _SEARCH_BOX; ?>"  onblur="if(this.value=='') this.value='<?php echo _SEARCH_BOX; ?>';" onfocus="if(this.value=='<?php echo _SEARCH_BOX; ?>') this.value='';" />
                        <input type="hidden" name="option" value="search" />
                    </td></form>
<?php /* til I find time to get a search working - RalfBecker 2004/05/05*
				<form action='<?php echo sefRelToAbs("index.php"); ?>' method='post'>
				<td width="155" align="left">
                        <input class="searchbox" type="text" name="searchword" height="16" size="15" value="<?php echo _SEARCH_BOX; ?>"  onblur="if(this.value=='') this.value='<?php echo _SEARCH_BOX; ?>';" onfocus="if(this.value=='<?php echo _SEARCH_BOX; ?>') this.value='';" />
                        <input type="hidden" name="option" value="search" />
                    </td></form>
*/ ?>
			</tr>
			</table>
			<table width="898" cellpadding="0" cellspacing="0" border="0">
			<tr valign="top">
				<td class="leftnav" width="174">
					<!-- left nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="152">
					<tr>
						<td class="navcontent">
							<table border="0" cellspacing="0" cellpadding="0" width="146">
								<tr>
									<td class="navbg_main">
									<?php mosLoadModules ( 'left' ); ?>
									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="152">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="148" height="4" /></td>
					</tr>
					</table>
					<br>
					<?php if (mosCountModules( "user1" )) { ?>
					<!-- user1 nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="152">
					<tr>
						<td class="navcontent">
							<table border="0" cellspacing="0" cellpadding="0" width="146">
								<tr>
									<td class="navbg_user">
									<?php mosLoadModules ( 'user1' ); ?>
									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="152">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="146" height="4" /></td>
					</tr>
					</table>	
					<?php } ?>
				</td>
				
				<td class="centernav" width="99%">
					<?php if (mosCountModules( "top" )) { ?>
					<!-- top nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td class="navcontent" width="99%">
							<table border="0" cellspacing="0" cellpadding="0" width="100%">
								<tr>
									<td class="navbg_user">
									<?php mosLoadModules ( 'top' ); ?>
									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd" width="99%"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="99%" height="4" /></td>
					</tr>
					</table>	
					<br>
					<?php } ?>
					
					<!-- main nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td class="navcontent" width="99%">
							<table border="0" cellspacing="0" cellpadding="0" width="100%">
								<tr>
									<td class="navbg_main">
									<?php include ("mainbody.php"); ?>
									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd" width="99%"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="99%" height="4" /></td>
					</tr>
					</table>	
					<br>
					
					<?php if (mosCountModules( "bottom" )) { ?>
					<!-- bottom nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td class="navcontent" width="99%">
							<table border="0" cellspacing="0" cellpadding="0" width="100%">
								<tr>
									<td class="navbg_user">
									<?php mosLoadModules ( 'bottom' ); ?>

									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd" width="99%"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="99%" height="4" /></td>
					</tr>
					</table>	
					<?php } ?>
				</td>
				
				<?php if (mosCountModules( "right" ) + mosCountModules( "user2" ) > 0) { ?>
				<td class="rightnav" width="155">
					<?php if (mosCountModules( "right" )) { ?>
					<!-- right nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="151">
					<tr>
						<td class="navcontent">
							<table border="0" cellspacing="0" cellpadding="0" width="145">
								<tr>
									<td class="navbg_user">
									<?php mosLoadModules ( 'right' ); ?>
									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="151">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="147" height="4" /></td>
					</tr>
					</table>
					<br>
					<?php } ?>
					
					<?php if (mosCountModules( "user2" )) { ?>
					<!-- user2 nav -->
					<table border="0" cellspacing="0" cellpadding="0" width="151">
					<tr>
						<td class="navcontent">
							<table border="0" cellspacing="0" cellpadding="0" width="145">
								<tr>
									<td class="navbg_main">
									<?php mosLoadModules ( 'user2' ); ?>
									</td>
								</tr>
							</table>
						</td>	
						<td valign="top" background="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/shadow_right.gif"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4"/></td>
					</tr>
					</table>
					<table border="0" cellspacing="0" cellpadding="0" width="151">
					<tr>
						<td><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="4" height="4" /></td>
						<td bgcolor="#becbcd"><img src="<?php echo $mosConfig_live_site;?>/templates/rhuk_orange_smoothie/images/spacer.gif" width="147" height="4" /></td>
					</tr>
					</table>					
					<?php } ?>
				</td>
				<?php } ?>
			</tr>
			</table>
			<table width="898" cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
				<?php mosLoadModules ( 'inset' ); ?>
				</td>
			</tr>
			<tr>
				<td>
				<?php mosLoadModules ( 'footer' ); ?>
				</td>
			</tr>
			</table>
			</td>
		</tr>

	</table>
	</center>
</body>
</html>
