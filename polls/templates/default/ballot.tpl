<!-- BEGIN form_top -->
<form action="{form_action}" method="post">
<input type="hidden" name="poll_id" value="{poll_id}">

<table border="0" align="center" width="50%">
 <tr class="th">
  <td>&nbsp;</td>
  <td>{poll_title}</td>
 </tr>

 {entries}
<!-- END form_top -->

<!-- BEGIN form_end -->
 <tr>
  <td colspan="2" align="center"><input name="vote" type="submit" value="{lang_vote}"></td>
 </tr>

</table>

</form>
<!-- END form_end -->

<!-- BEGIN entry -->
 <tr class="{tr_class}">
  <td align="center"><input type="radio" name="poll_voteNr" id="vote{vote_id}" value="{vote_id}"></td>
  <td><label for="vote{vote_id}">{option_text}</label></td>
 </tr>
<!-- END entry -->
