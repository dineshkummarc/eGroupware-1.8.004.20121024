<?PHP 
    defined( "_VALID_MOS" ) or die('Direct Access to this location is not allowed.' );
    $templatename = 'stylite';
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"><html xmlns=\"http://www.w3.org/1999/xhtml\"><head> <title> $mosConfig_sitename </title> <meta http-equiv=\"Content-Type\" content=\"text/html;". _ISO. "\" />\n"; 
     include ("includes/metadata.php");  
     include ("editor/editor.php");  
     initEditor();
     echo "  
		<link rel=\"stylesheet\" href=\"/templates/simple_plain/css/style.css\" type=\"text/css\" rel=\"StyleSheet\" > 
                <script type=\"text/javascript\">
                        function module_headers() {
                           var divs = document.getElementsByTagName('div');
                           for (var i = 0; i < divs.length; i++) {
                            if (divs[i].className == 'centermodule_title' && divs[i].innerHTML.search(/[a-zA-Z0-9]+/)  == -1 ) {
                             divs[i].innerHTML = 'Inhalt' ;
                            }
                           }
                          }
                 </script>
		 </head>";
?>
<body onload="module_headers();" id="bg" border="0" style="border-style:none; border-width:0pt;"> 
<div>			
	<?php if (mosCountModules( 'header' )) { mosLoadModules ( 'header' ); } ?>
	<?php include ("mainbody.php"); ?>                 
	<?php if (mosCountModules( 'footer' )) { mosLoadModules ( 'footer' ); } ?>
</div> 
</body>
</html>
