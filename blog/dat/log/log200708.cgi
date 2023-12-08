18,20070810233319,,,2,2|6,1,1,Win-MES間の協調試験成功。,カテゴリがハードかソフトかネットワークか微妙ですが･･･<br />とりあえずネットワークカテゴリに。<br /><br />本日、WindowsとMES間での、二値化データの受け渡しに成功しました。<br />ソフト主事のBOSSがWindows用に書いたものを、MES向けに移植したものです。<br />Windows側で作成した、一文字の二値化データ（8x8）を、ネットワークを通じてMESに送信します。<br /><br />次の作業は、<br />組み込み側：<br />・２文字以上の二値化データの受信対応<br />・展開し、回路に送信するモジュールの作成<br />MFC側：<br />・２文字以上の二値化データの送信対応<br />・ラスタライズ部から直に送信できるように改良<br /><br /><a href="files/dekita.JPG" target="_blank"><img src="files/dekita.JPG" width="320" height="228" alt=""></a><br /><br />本文には、コードを記載しておきます。,Windows側プログラム（庄子：BOSS　作成）<br /><br />　w2m.c<br /><br /><br />#include<stdio.h><br />#include<winsock2.h><br />#include<string.h><br /><br />#define OK 0<br />#define ONE_LINE_DATA_OK 2<br />#define CHAR_DATA_OK 5<br />#define QUIT_OK 99<br /><br />int main(){<br />	WSADATA wsaData;<br />	struct sockaddr_in serv;<br />	SOCKET sock;<br />	char buf[64]&#44; *data[8];<br />	int i&#44;reserve;<br />	<br />	//テストに用いるテストデータ(char *型)を以下に記します。<br />	data[0] = "□□□■■□□□";<br />	data[1] = "□□■□■□□□";<br />	data[2] = "□□□□■□□□";<br />	data[3] = "□□□□■□□□";<br />	data[4] = "□□□□■□□□";<br />	data[5] = "□□□□■□□□";<br />	data[6] = "□□□□■□□□";<br />	data[7] = "□□■■■■□□";<br />	<br />	//処理開始<br />	WSAStartup(MAKEWORD(2&#44;0)&#44;&wsaData);<br />	sock = socket(AF_INET&#44;SOCK_STREAM&#44;0);<br />	<br />	serv.sin_family = AF_INET;<br />	serv.sin_port = htons(13621);<br />	serv.sin_addr.S_un.S_addr = inet_addr("192.168.55.169");<br />	<br />	connect(sock&#44; (struct sockaddr *)&serv&#44; sizeof(serv));<br />	memset(buf&#44;0&#44;sizeof(buf));<br />	printf("Winsock connect...\n");<br />	<br />	//*******************************************************<br />	//<br />	//CHAR_MODEのテストを行います。手順は以下の通り。<br />	//<br />	//(1)文字通信の要求を送る。<br />	//(2)応答が0(=定数OK)ならば、n文字の通信を行う要求を送る。(ここでは1文字の通信のテストを行う)<br />	//(3)1行分のデータを送る。このテストではdata[0]～data[7]の順。<br />	//(4)1行送るごとに、1行分のデータを受理したか確認を行う。<br />	//   正常に受信した旨の応答(=定数ONE_LINE_DATA_OK)が帰ってきたら、次の行のデータを送る。<br />	//(5)文字データを送信し終わったら、文字通信終了の要求(=定数5)を送る。<br />	//(6)正常な終了応答(=定数CHAR_DATA_OK)が返ってきたら文字通信を終了する。<br />	//<br />	//*******************************************************<br />	send(sock&#44;"0"&#44;1&#44;0);<br />	recv(sock&#44;buf&#44;sizeof(buf)&#44;0);<br />	reserve = atoi(buf);<br />	if(reserve == OK){<br />		printf("Starting char data communication.\n");<br />		send(sock&#44;"1"&#44;1&#44;0); //1文字の通信を要求。第2引数に文字数を指定する。<br />		printf("1 char data.\n");<br />		memset(buf&#44;0&#44;sizeof(buf));<br />		for(i = 0;i < 8;i++){<br />			send(sock&#44;data[i]&#44;16&#44;0); //1行分のデータの送信<br />			recv(sock&#44;buf&#44;sizeof(buf)&#44;0); //データ受理の応答を待つ。<br />			reserve = atoi(buf);<br />			if(reserve == ONE_LINE_DATA_OK){<br />				printf("%d line char data ok.\n"&#44;i+1);<br />				memset(buf&#44;0&#44;sizeof(buf));<br />				continue;<br />			}else{<br />				//文字通信エラー<br />				printf("ﾊﾞｰｶﾊﾞｰｶ\n");<br />				exit(1);<br />			}<br />		}<br />		memset(buf&#44;0&#44;sizeof(buf));<br />		send(sock&#44;"5"&#44;1&#44;0);<br />		recv(sock&#44;buf&#44;sizeof(buf)&#44;0);<br />		reserve = atoi(buf);<br />		if(reserve == CHAR_DATA_OK){<br />			printf("char mode end.\n");<br />		}else{<br />			printf("char mode error.ﾊﾞｰｶﾊﾞｰｶ\n");<br />			exit(1);<br />		}<br />	}<br />	//CHAR_MODEのテスト_終わり<br />	<br />	//コマンドTESTのテストです<br />	memset(buf&#44;0&#44;sizeof(buf));<br />	send(sock&#44;"1"&#44;1&#44;0);<br />	<br />	//QUIT のテスト<br />	//正常終了のテストです。<br />	memset(buf&#44;0&#44;sizeof(buf));<br />	send(sock&#44;"9"&#44;1&#44;0);<br />	recv(sock&#44;buf&#44;sizeof(buf)&#44;0);<br />	reserve = atoi(buf);<br />	if(reserve == QUIT_OK){<br />		send(sock&#44;"999"&#44;3&#44;0);<br />		printf("connection end.\n");<br />		WSACleanup();<br />	}else{<br />		printf("通信終了でエラーだよﾊﾞｰｶ\n");<br />		exit(1);<br />	}<br />	return 0;<br />}<br /><br />MES側プログラム<br /><br />connect-1.c<br /><br />#include <ctype.h><br />#include <string.h><br />#include <stdlib.h><br />#include "mes2.h"<br /><br />#define CHAR_MODE 0          //文字通信モードをあらわすコマンドの定数<br />#define TEST 1               //テストモードをあらわすコマンドの定数<br />#define CHAR_DATA_COMPLETE 5 //文字データを完全に取得できたことを表す定数<br />#define QUIT 9               //通信を終了することをあらわすコマンドの定数<br />#define REPLY_QUIT 999       //通信終了応答に対する、更なる応答をあらわす定数(詳細は85行目を参照)<br /><br />struct sockaddr myaddr&#44;winaddr;<br />char buff[64]&#44;moji[5];<br />int port&#44;sock1&#44;sock2&#44;ip;<br /><br />int recvroop(void);<br /><br />int connect(void)<br />{<br />	int times;<br />	int res;<br />	int flg=0;<br />	int i&#44;j;<br /><br />	myaddr.sin_addr=0;<br />	myaddr.sin_port=13621;<br /><br />	sock1=tcp_socket(0);<br />	tcp_bind(sock1&#44;&myaddr);<br />	tcp_listen(sock1&#44;1);<br />	sock2=tcp_accept(sock1&#44;&winaddr);<br />	printf("win-conected\r\n");<br />	while(1)<br />	{<br />		switch(recvroop())<br />		{<br />		case CHAR_MODE:<br />			//文字通信要求が送られてきた場合<br />			tcp_write(sock2&#44;"0"&#44;strlen("0"));<br />			//send(sock&#44;"0"&#44;strlen("0")&#44;0);//文字通信了解。0はWin側で定数OKである。<br />			printf("Starting char data mode...\r\n");<br />			memset(buff&#44;0&#44;sizeof(buff));<br />			tcp_read(sock2&#44;buff&#44;sizeof(buff));<br />			//recv(sock&#44;buf&#44;sizeof(buf)&#44;0);<br />			times = atoi(buff); //変数times内に、「何文字の通信を行うのか」を表す文字数を格納。今回は1文字のみの試験としたい。<br />			for(i = 0;i < times;i++){<br />				for(j = 0;j < 8;j++){<br />					memset(buff&#44;0&#44;sizeof(buff));<br />					tcp_read(sock2&#44;buff&#44;sizeof(buff));<br />					//recv(sock&#44;buf&#44;sizeof(buf)&#44;0);<br />					printf("%s\r\n"&#44;buff);<br />					tcp_write(sock2&#44;"2"&#44;strlen("2"));<br />					//send(sock&#44;"2"&#44;1&#44;0);//1行分のデータを受け取ったことを承認し、応答。2はWin側で定数ONE_LINE_DATA_OKである。<br />				}<br />				//ここに<1文字分の表示処理>を埋め込む?<br />			}<br />			memset(buff&#44;0&#44;sizeof(buff));<br />			tcp_read(sock2&#44;buff&#44;sizeof(buff));<br />			//recv(sock&#44;buf&#44;sizeof(buf)&#44;0);<br />			res = atoi(buff);<br />			if(res == CHAR_DATA_COMPLETE){<br />				printf("char data end.\r\n");<br />				tcp_write(sock2&#44;"5"&#44;strlen("5"));<br />				//send(sock&#44;"5"&#44;1&#44;0);<br />			}else{<br />				printf("char data error.\r\n");<br />				exit(1);<br />			}	<br />			break;<br />		case TEST:<br />			//コマンドtest用<br />			printf("TEST:Let's playin' DEATH-MARCH!!\r\n"); //文字列を表示するのみ。ﾜﾎｰｲ<br />			break;<br />		case QUIT:<br />			//通信終了。<br />			flg=1;<br />			break;<br />		default:<br />			printf("cmd error.\r\n");<br />			//<再送要求>;<br />			break;<br />		}<br />		if(flg==1)<br />		{<br />				printf("connection quit.\r\n");<br /><br />				tcp_write(sock2&#44;"99"&#44;strlen("99"));<br />				//send(sock&#44;"99"&#44;2&#44;0); //通信終了応答<br />				tcp_read(sock2&#44;buff&#44;sizeof(buff));<br />				//recv(sock&#44;buf&#44;sizeof(buf)&#44;0); //通信終了応答に対し、さらに相手(Win側)からの応答を待つ(なぜかこうしないとバグる)<br />				res = atoi(buff);<br />				if(res == REPLY_QUIT)<br />				{ //通信終了応答に対する応答が正常に返ってきたら<br />					printf("bye.\r\n");<br />					tcp_close(sock2);<br />				}<br />				else<br />				{<br />					printf("quit error.\r\n");<br />					exit(1);<br />				}<br />		}<br />	}<br />	return 0;<br />}<br /><br />int recvroop(void)<br />{<br />	char recvbuf[64];<br />	memset(recvbuf&#44;0&#44;sizeof(buff));<br />	tcp_read(sock2&#44;recvbuf&#44;sizeof(recvbuf));<br />	return(atoi(recvbuf));<br />}<br /><br /><br /><br />int main(void)<br />{<br />	connect();<br />	return 0;<br />}<br /><br />,1,
17,20070804032842,,,2,2|5,1,1,夏休み中の連絡,夏休み中の工科展作業の連絡です。<br />いつ部室を空けるか・・・ですが、みんなmixiをやってるので<br /><br /><a href="http://mixi.jp/view_bbs.pl?id=21473743&comment_count=1&comm_id=817996" target="_blank">http://mixi.jp/view_bbs.pl?id=21473743&comment_count=1&comm_id=817996</a><br /><br />にて連絡を取ろうと思います。<br />（シス研の会員ページに作ろうとしたけど、こっちのほうが便利・・・）<br /><br />そういうわけでよろしこ。<br />,,1,
