/**********************************************************************

  入力内容チェック

**********************************************************************/

var sendFlag = false;

//記事入力内容チェック
function checkDiaryForm(form) {
	if (form.subj && !form.subj.value) {
		alert('題名が入力されていません。');
		return false;
	}
	if (form.text && !form.text.value) {
		alert('本文が入力されていません。');
		return false;
	}

	if (sendFlag == true) {
		alert('二重投稿は禁止です。');
		return false;
	} else {
		sendFlag = true;
	}

	return true;
}

//コメント入力内容チェック
function checkCommentForm(form) {
	if (form.name && !form.name.value) {
		alert('名前が入力されていません。');
		return false;
	}
	if (form.text && !form.text.value) {
		alert('本文が入力されていません。');
		return false;
	}

	if (sendFlag == true) {
		alert('二重投稿は禁止です。');
		return false;
	} else {
		sendFlag = true;
	}

	return true;
}

/**********************************************************************

  カレンダー

**********************************************************************/

//本日のセル色を変更
function setCalendar() {
	var today = new Date();
	var year  = new String(today.getFullYear());
	var month = new String(today.getMonth() + 1);
	var date  = new String(today.getDate());

	while (month.length < 2) {
		month = '0' + month;
	}
	while (date.length < 2) {
		date = '0' + date;
	}

	var node_calendar_cel = document.getElementById('calendar_' + year + month + date);
	if (node_calendar_cel) {
		node_calendar_cel.className = 'today';
	}

	return;
}

/**********************************************************************

  処理開始

**********************************************************************/

//読み込み完了時
window.onload = function() {
	//トップウインドウ更新用
	if (top.location != self.location) {
		var node_a = document.getElementsByTagName('a');
		for (var i in node_a) {
			if (node_a[i].className == 'top') {
				node_a[i].onclick = function() {
					window.top.location = this.href;
				};
			}
		}
	}

	//カレンダー用
	setCalendar();

	//入力内容チェック
	var node_diary_form = document.getElementById('diary_form');
	if (node_diary_form) {
		node_diary_form.onsubmit = function() {
			return checkDiaryForm(node_diary_form);
		};
	}
	var node_comment_form = document.getElementById('comment_form');
	if (node_comment_form) {
		node_comment_form.onsubmit = function() {
			return checkCommentForm(node_comment_form);
		};
	}
};
