/*
+--------------------------------------------------------------------------
|   =============================================
|	MyTracker
|   by Tomm (www.xekko.co.uk)
|   2009 © Mooseypx / Xekko
|   =============================================
+---------------------------------------------------------------------------
|   > $Id: mytracker.js 3 2009-08-03 15:11:27Z Tomm $
+--------------------------------------------------------------------------
*/
jQuery.noConflict();
	jQuery(document).ready(function(){
									
jQuery("a[name='jscript']").attr("href","javascript:;");
jQuery("div[name='hide']").hide();

jQuery("div[id='clicksmilies']").append("[<a href=\"javascript:MyBB.popupWindow('../misc.php?action=smilies&popup=true&editor=clickableEditor', 'sminsert', 280, 280);\">Smilies</a>]<br /><br />");

});