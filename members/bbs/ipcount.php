<?php
/*
	板を見ているおおよその人数を出力する
	javascriptから呼び出す
	for namamono bbs (http://namamono.pandora.nu/)
	2005/07 PCjingle
	2005/10 Ver.2
	2006/01 Ver.2.1
	2006/02 Ver.2.1.1
*/
define("IPFILE",'ipcount.log');	//アクセスしたIPを時間保存
define("TIMELIMIT",5);			//IPの保持時間（デフォルト５分）

$hostname=gethostbyaddr($_SERVER["REMOTE_ADDR"]);
$countip=0;
$ipdat=array('dummy',0);
if(is_file(IPFILE)){
  $ipdat=file(IPFILE);
  $countip=count($ipdat);
  for($i=0;$i<$countip;++$i){
    $ipdat[$i]=trim($ipdat[$i],"\n");
  }
}
//時間切れのIPを削除（定義した時間「分」更新）
$flg=0;
for($i=$countip-1;$i>=0;$i-=2){
  if(time()-$ipdat[$i]>60*TIMELIMIT){
    array_pop($ipdat);
    array_pop($ipdat);
    $flg=2;
  }
  if($ipdat[$i-1]==$hostname){
    $flg++;
	$countip/=2;
    break;
  }
}
//新たなIPを追加
if(($flg==0)||($flg==2)) array_unshift($ipdat,$hostname,time());
if($flg!=1){
  $countip=(int)(count($ipdat)/2);
  $ipdat2=implode("\n",$ipdat);
  $fp=fopen(IPFILE,"r+");
  if($fp){
    if(flock($fp,LOCK_EX)){
      set_file_buffer($fp,0);
      fputs($fp,$ipdat2);
	  ftruncate($fp,strlen($ipdat2));
      flock($fp,LOCK_UN);
    }
    fclose($fp);
    @chmod(IPFILE,0666);
  }
}
echo "document.write(\"".$countip."\");";
?>