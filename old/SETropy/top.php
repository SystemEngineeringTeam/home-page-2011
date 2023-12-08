<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>VM Vine Linux4.2</title>
	</head>
	<body>
		<h1>This is a test page.</h1>
		<p>Hello<br />
		<?php
			$today = date("Y.M.dS.");
		?>
		<b>Today:&nbsp;<?php echo $today; ?></b><br />
		<b>Last:&nbsp;2008.Feb.19th.</b>
		</p>
		<p>
		<table border=1><tr><th>/</th><th>ファイルタイプ</th><th>ファイルネーム</th></tr>
<?php
	$count = 1;
	exec("/bin/ls -p --time=atime", $ls);
	foreach($ls as $name){
		// バックアップファイルは表示しない
		$num = mb_strpos($name, "~");
		if($num == mb_strlen($name)-1){
			continue;
		}
		
		// ディレクトリ判定 0: ファイル　1:ディレクトリ
		$flag = 0;
		$num = mb_strpos($name, "/");
		if($num == mb_strlen($name)-1){
			$flag = 1;
		}
		echo "<tr><td>".$count."</td><td>";
		if($flag == 0){
			echo "ファイル";
		}
		else{
			echo "ディレクトリ";
		}
		echo "</td><td><a href=\"".$name."\">".$name."</a></td></tr>\n";
		$count++;
	}
?>
		</p>
	</body>
</html>
