<?php
// $Id: newpage.inc.php,v 1.15 2005/02/27 09:35:16 henoheno Exp $
//
// Newpage plugin

function plugin_newpage_convert()
{
	global $script, $vars, $_btn_edit, $_msg_newpage, $BracketName;
	static $id = 0;

	if (PKWK_READONLY) return ''; // Show nothing

	$newpage = '';
	if (func_num_args()) list($newpage) = func_get_args();
	if (! preg_match('/^' . $BracketName . '$/', $newpage)) $newpage = '';

	$s_page    = htmlspecialchars(isset($vars['refer']) ? $vars['refer'] : $vars['page']);
	if ($newpage == '' && $s_page != 'FrontPage' && $s_page != 'MenuBar') {
		$newpage = isset($vars['refer']) ? $vars['refer'] : $vars['page'];
		$newpage .= "/";
	}
	$s_newpage = htmlspecialchars($newpage);
	$r_refer = rawurlencode($vars['refer']);
	++$id;

	$ret = <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="newpage" />
  <label for="_p_newpage_$id">$_msg_newpage:</label>
  <input type="text"   name="page" id="_p_newpage_$id" value="$s_newpage" size="30" />
  <input type="submit" value="$_btn_edit" />
 </div>
</form>
EOD;

	return $ret;
}

function plugin_newpage_action()
{
	global $vars, $_btn_edit, $_msg_newpage;

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	if ($vars['page'] == '') {
		$retvars['msg']  = $_msg_newpage;
		$retvars['body'] = plugin_newpage_convert();
		return $retvars;
	} else {
		$page    = strip_bracket($vars['page']);
		$r_page  = rawurlencode(isset($vars['refer']) ?
			get_fullname($page, $vars['refer']) : $page);
		$r_refer = rawurlencode($vars['refer']);

		pkwk_headers_sent();
		header('Location: ' . get_script_uri() .
			'?cmd=read&page=' . $r_page . '&refer=' . $r_refer);
		exit;
	}
}
?>
