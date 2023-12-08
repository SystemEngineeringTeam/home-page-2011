<?

# 指定したディレクトリから画像をひとつ選びだし出力する
# JPEGフォーマット以外の画像はすべて無視し、
# サブディレクトリの検索も行わない
# 2006/03/07 菱田 童之

$path = './img/topimg'; # 呼出し側からみた画像のあるディレクトリの相対パス

srand(make_seed());

$file_list = array(); # 画像のなまえを保存しておく配列
$fd = opendir($path);
while($file = readdir($fd)){
	# jpg で終るものだけをファイルリストに追加していく
	if(ereg("jpg$", $file)){
		array_push($file_list, $file);
	}
}

# みつけた画像の数までの乱数を発生させ出力する画像を選ぶ
$n = count($file_list);
$r = rand(0, $n-1);

# パスつきでランダムに選んだ画像のファイル名を出力
print "\"$path/$file_list[$r]\"";

function make_seed(){
	list($usec, $sec) = explode(' ', microtime());
	return (float) $sec + ((float) $usec * 100000);
}


?>
