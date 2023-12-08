var c_pc = navigator.userAgent.toLowerCase();
var c_ver = parseInt(navigator.appVersion);
var ie = ((c_pc.indexOf("msie") != -1) && (c_pc.indexOf("opera") == -1));
var win = ((c_pc.indexOf("win")!=-1) || (c_pc.indexOf("16bit") != -1));

size_tags = new Array('7','8','9','10','12','14','16','18','20','22','24','26','28','30','36','40');
color_tags = new Array('black','brown','red','orange','yellow','green','blue','violet','gray','white');
font_tags_open = new Array('<b>','<i>','<u>','<s>','<div align="left">','<div align="center">','<div align="right">','<p>','<blockquote>','<pre>');
font_tags_close = new Array('</b>','</i>','</u>','</s>','</div>','</div>','</div>','</p>','</blockquote>','</pre>');

link_tags_open = new Array('<a href="','<a href="');
link_tags_center = new Array('" target="_blank">','">');
link_tags_close = new Array('</a>','</a>');

function wopen_pict_profile() {
	window.open("pict.php?type=p","WindowOpen1","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=480,height=350")
}
function wopen_pict_message() {
	window.open("pict.php","WindowOpen1","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,width=480,height=350")
}

function wopen_icon_profile() {
	window.open("icon.php?type=p","WindowOpen2","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=240,height=250")
}
function wopen_icon_message() {
	window.open("icon.php","WindowOpen2","toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=240,height=250")
}

function linkins(tags,d) {
	if (d == 0) {
		var txt = document.post.blogn_profile;
	}
	if (d == 1) {
		var txt = document.post.blogn_mes;
	}
	if (d == 2) {
		var txt = document.post.blogn_more_mes;
	}
	txt.focus();
	var msg;
	if (tags == 0) {
		msg = prompt("Link Address","http://");
	}else{
		msg = prompt("Mail Address","mailto:");
	}
	if (!msg) return;
	var t0 = false;
	if ((c_ver >= 4) && ie && win) {
		if (t0 = document.selection.createRange().text) {
			document.selection.createRange().text = link_tags_open[tags] + msg + link_tags_center[tags] + t0 + link_tags_close[tags];
			t0 = "";
			txt.focus();
			return;
		}else{
			txt.focus();
			tag = document.selection.createRange();
			tag.text = link_tags_open[tags] + msg + link_tags_center[tags] + link_tags_close[tags];
			return;
		}
	} else if (txt.selectionEnd && (txt.selectionEnd - txt.selectionStart > 0)) {
		if (txt.selectionEnd == 1 || txt.selectionEnd == 2) txt.selectionEnd = txt.textLength;
		txt.value = (txt.value).substring(0,txt.selectionStart) + link_tags_open[tags] + msg + link_tags_center[tags] + (txt.value).substring(txt.selectionStart, txt.selectionEnd) + link_tags_close[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		txt.focus();
		return;
	} else if (txt.selectionStart) {
		txt.value = (txt.value).substring(0,txt.selectionStart) + link_tags_open[tags] + msg + link_tags_center[tags] + link_tags_close[tags] + (txt.value).substring(txt.selectionEnd, txt.textLength);
		return;
	}
	txt.value += link_tags_open[tags] + msg + link_tags_center[tags] + link_tags_close[tags];
	txt.focus();
}

function ins(type,tags,d) {
	if (d == 0) {
		var txt = document.post.blogn_profile;
	}
	if (d == 1) {
		var txt = document.post.blogn_mes;
	}
	if (d == 2) {
		var txt = document.post.blogn_more_mes;
	}
	if (type == 0) {
		if (d == 0) {
			tags = document.post.font_profile.selectedIndex;
		}
		if (d == 1) {
			tags = document.post.font_mes.selectedIndex;
		}
		if (d == 2) {
			tags = document.post.font_more.selectedIndex;
		}
		open_tag = '<span style="font-size:' + size_tags[tags] + 'px;">';
		close_tag = '</span>';
	}
	if (type == 1) {
		open_tag = '<font color="' + color_tags[tags] + '">';
		close_tag = '</font>';
	}
	if (type == 2) {
		open_tag = font_tags_open[tags];
		close_tag = font_tags_close[tags];
	}
	txt.focus();
	t0 = false;
	if ((c_ver >= 4) && ie && win) {
		if (t0 = document.selection.createRange().text) {
			document.selection.createRange().text = open_tag + t0 + close_tag;
			t0 = "";
			txt.focus();
			return;
		}else{
			txt.focus();
			tag = document.selection.createRange();
			if (msg = prompt("Input","")) {
				tag.text = open_tag + msg + close_tag;
			}else{
				tag.text = open_tag + close_tag;
			}
			return;
		}
	} else if (txt.selectionEnd && (txt.selectionEnd - txt.selectionStart > 0)) {
		if (txt.selectionEnd == 1 || txt.selectionEnd == 2) txt.selectionEnd = txt.textLength;
		txt.value = (txt.value).substring(0,txt.selectionStart) + open_tag + (txt.value).substring(txt.selectionStart, txt.selectionEnd) + close_tag + (txt.value).substring(txt.selectionEnd, txt.textLength);
		txt.focus();
		return;
	} else if (txt.selectionStart) {
		if (msg = prompt("Input","")) {
			txt.value = (txt.value).substring(0,txt.selectionStart) + open_tag + msg + close_tag + (txt.value).substring(txt.selectionEnd, txt.textLength);
		}else{
			txt.value = (txt.value).substring(0,txt.selectionStart) + open_tag + close_tag + (txt.value).substring(txt.selectionEnd, txt.textLength);
		}
		return;
	}
	if (msg = prompt("Input","")) {
		txt.value += open_tag + msg + close_tag;
	}else{
		txt.value += open_tag + close_tag;
	}
	txt.focus();
	pos(txt);
}

function icon(t1,d) {
	if (d == 0) {
		var txt = document.post.blogn_profile;
	}
	if (d == 1) {
		var txt = document.post.blogn_mes;
	}
	if (d == 2) {
		var txt = document.post.blogn_more_mes;
	}
	if (document.selection) {
		txt.focus();
		sel = document.selection.createRange();
		sel.text = t1;
	} else if (txt.selectionStart) {
		txt.value = (txt.value).substring(0,txt.selectionStart) + t1 + (txt.value).substring(txt.selectionEnd, txt.textLength);
	}else{
		txt.value  += t1;
	}
	t1 = "";
	txt.focus();
	return;
}

function dnow(){
	now = new Date();
	document.post.blogn_ent_year.selectedIndex=1;
	document.post.blogn_ent_month.selectedIndex=now.getMonth();
	document.post.blogn_ent_day.selectedIndex=now.getDate()-1;
	document.post.blogn_ent_hour.selectedIndex=now.getHours();
	document.post.blogn_ent_minutes.selectedIndex=now.getMinutes();
	document.post.blogn_ent_second.selectedIndex=now.getSeconds();
}

		function logout(ref) {
			if (confirm("ログアウトしてよろしいですか。")) {
				window.location.href=ref;
			}
		}

