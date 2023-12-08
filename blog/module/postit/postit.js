var c_pc = navigator.userAgent.toLowerCase();
var c_ver = parseInt(navigator.appVersion);
var ie = ((c_pc.indexOf("msie") != -1) && (c_pc.indexOf("opera") == -1));
var win = ((c_pc.indexOf("win")!=-1) || (c_pc.indexOf("16bit") != -1));

color_tags = new Array('black','brown','red','orange','yellow','green','blue','violet','gray','white');
font_tags_open = new Array('<b>','<i>','<u>','<s>','<div align="left">','<div align="center">','<div align="right">','<p>','<blockquote>','<pre>');
font_tags_close = new Array('</b>','</i>','</u>','</s>','</div>','</div>','</div>','</p>','</blockquote>','</pre>');

link_tags_open = new Array('<a href="','<a href="');
link_tags_center = new Array('" target="_blank">','">');
link_tags_close = new Array('</a>','</a>');

function linkins(tags,d) {
	if (d == 1) {
		var txt = document.postit1.mes1;
	}
	if (d == 2) {
		var txt = document.postit2.mes2;
	}
	if (d == 3) {
		var txt = document.postit3.mes3;
	}
	if (d == 4) {
		var txt = document.postit4.mes4;
	}
	if (d == 5) {
		var txt = document.postit5.mes5;
	}
	if (d == 6) {
		var txt = document.postit6.mes6;
	}
	if (d == 7) {
		var txt = document.postit7.mes7;
	}
	if (d == 8) {
		var txt = document.postit8.mes8;
	}
	if (d == 9) {
		var txt = document.postit9.mes9;
	}
	if (d == 10) {
		var txt = document.postit10.mes10;
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
	if (d == 1) {
		var txt = document.postit1.mes1;
	}
	if (d == 2) {
		var txt = document.postit2.mes2;
	}
	if (d == 3) {
		var txt = document.postit3.mes3;
	}
	if (d == 4) {
		var txt = document.postit4.mes4;
	}
	if (d == 5) {
		var txt = document.postit5.mes5;
	}
	if (d == 6) {
		var txt = document.postit6.mes6;
	}
	if (d == 7) {
		var txt = document.postit7.mes7;
	}
	if (d == 8) {
		var txt = document.postit8.mes8;
	}
	if (d == 9) {
		var txt = document.postit9.mes9;
	}
	if (d == 10) {
		var txt = document.postit10.mes10;
	}
	if (type == 1) {
		open_tag = '<span style="color:' + color_tags[tags] + ';">';
		close_tag = '</span>';
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
	if (d == 1) {
		var txt = document.postit1.mes1;
	}
	if (d == 2) {
		var txt = document.postit2.mes2;
	}
	if (d == 3) {
		var txt = document.postit3.mes3;
	}
	if (d == 4) {
		var txt = document.postit4.mes4;
	}
	if (d == 5) {
		var txt = document.postit5.mes5;
	}
	if (d == 6) {
		var txt = document.postit6.mes6;
	}
	if (d == 7) {
		var txt = document.postit7.mes7;
	}
	if (d == 8) {
		var txt = document.postit8.mes8;
	}
	if (d == 9) {
		var txt = document.postit9.mes9;
	}
	if (d == 10) {
		var txt = document.postit10.mes10;
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
