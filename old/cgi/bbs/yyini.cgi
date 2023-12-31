#┌─────────────────────────────────
#│ YY-BOARD v5.5
#│ yyini.cgi - 2005/11/20
#│ Copyright (c) KentWeb
#│ webmaster@kent-web.com
#│ http://www.kent-web.com/
#│
#│携帯電話対応スクリプト
#│2005/01/04　湯一路　http://www.url-battle.com/cgi/
#│
#│ Modified by isso. August, 2006
#│ http://swanbay-web.hp.infoseek.co.jp/index.html
#└─────────────────────────────────
$ver = 'YY-BOARD v5.5 Rev1.87k';
#┌─────────────────────────────────
#│ [注意事項]
#│ 1. このスクリプトはフリーソフトです。このスクリプトを使用した
#│    いかなる損害に対して作者は一切の責任を負いません。
#│ 2. 改変版CGI設置に関するご質問は設置URLを明記のうえ、下記までお願いします。
#│    http://swanbay-web.hp.infoseek.co.jp/index.html
#│    お問い合わせ前に、「このサイトについて」
#│    http://swanbay-web.hp.infoseek.co.jp/about.html
#│    「よくあるご質問」
#│    http://swanbay-web.hp.infoseek.co.jp/faq.html
#│   「お問い合わせに関する注意事項」
#│    http://swanbay-web.hp.infoseek.co.jp/mail.html
#│    に必ず目を通してください。
#│
#│    最新のNGワードデータファイルは下記よりダウンロードしてください。
#│    http://swanbay-web.hp.infoseek.co.jp/spamdata.html
#│
#│    アクセス制限IPアドレスファイル下記よりダウンロードしてください。
#│    http://swanbay-web.hp.infoseek.co.jp/accessdeny.html
#│
#│    掲示板へのリンク方法をJavascript表示する方法は下記を参照下さい。
#│    http://swanbay-web.hp.infoseek.co.jp/cgi-bin/javascript.html
#│
#│    自動アクセス制限の利用方法は下記サイトを参照下さい。
#│    http://swanbay-web.hp.infoseek.co.jp/accesstrap/index.html
#│＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿
#│本改造スクリプトに関してはKENT氏に問い合わせしないようお願いします。
#│￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣
#│ 3. 添付の home.gif は L.O.V.E の mayuRin さんによる画像です。
#└─────────────────────────────────
#
# 【ファイル構成例】
#
#  public_html (ホームディレクトリ)
#      |
#      +-- yybbs / yybbs.cgi  [705]
#            |     yyregi.cgi [705]
#            |     yyini.cgi  [604]
#            |     yylog.cgi  [606]
#            |     count.dat  [606]
#            |     jcode.pl   [604]
#            |     pastno.dat [606]
#            |     spamdata.cgi [606]
#            |
#            +-- img / home.gif, bear.gif, ...
#            |
#            +-- lock [707] /
#            |
#            +-- past [707] / 0001.cgi [606] ...

#-------------------------------------------------
# ▼設定項目
#-------------------------------------------------

# タイトル名
$title = "公式BBS";

# タイトル文字色
$tCol = "#000000";

# タイトルサイズ
$tSize = '26px';

# 本文文字フォント
$bFace = "MS UI Gothic, Osaka, ＭＳ Ｐゴシック";

# 本文文字サイズ
$bSize = '15px';

# 壁紙を指定する場合（http://から指定）
$backgif = "";

# 背景色を指定
$bgcolor = "#FFFFFF";

# 文字色を指定
$text = "#000000";

# リンク色を指定
$link  = "#0000FF";	# 未訪問
$vlink = "#800080";	# 訪問済
$alink = "#FF0000";	# 訪問中

# 戻り先のURL (index.htmlなど)
$homepage = "http://www.sysken.net/";

# 最大記事数
$max = 100;

# 管理者用パスワード (英数字で８文字以内)
$pass = '[set-bbs:su]';

# アイコン画像のあるディレクトリ
# → フルパスなら http:// から記述する
# → 最後は必ず / で閉じる
$imgurl = "./img/";

# アイコンを定義
# →　上下は必ずペアにして、スペースで区切る
$ico1 = 'bear.gif cat.gif cow.gif dog.gif fox.gif hituji.gif monkey.gif zou.gif mouse.gif panda.gif pig.gif usagi.gif';
$ico2 = 'くま ねこ うし いぬ きつね ひつじ さる ぞう ねずみ パンダ ぶた うさぎ';

# 管理者専用アイコン機能 (0=no 1=yes)
# (使い方) 記事投稿時に「管理者アイコン」を選択し、暗証キーに
#         「管理パスワード」を入力して下さい。
$my_icon = 0;

# 管理者専用アイコンの「ファイル名」を指定
$my_gif  = 'admin.gif';

# アイコンモード (0=no 1=yes)
$iconMode = 0;

# 返信がつくと親記事をトップへ移動 (0=no 1=yes)
$topsort = 1;

# タイトルにGIF画像を使用する時 (http://から記述)
$t_img = "";
$t_w = 150;	# 画像の幅 (ピクセル)
$t_h = 50;	#   〃  高さ (ピクセル)

# ファイルロック形式
#  → 0=no 1=symlink関数 2=mkdir関数
$lockkey = 0;

# ロックファイル名
$lockfile = './lock/yybbs.lock';

# ミニカウンタの設置
#  → 0=no 1=テキスト 2=画像
$counter = 1;

# ミニカウンタの桁数
$mini_fig = 6;

# テキストのとき：ミニカウンタの色
$cntCol = "#DD0000";

# 画像のとき：画像ディレクトリを指定
#  → 最後は必ず / で閉じる
$gif_path = "./img/";
$mini_w = 8;		# 画像の横サイズ
$mini_h = 12;		# 画像の縦サイズ

# カウンタファイル
$cntfile = './count.dat';

# 本体ファイルURL
$script = './yybbs.cgi';

# 更新ファイルURL
$regist = './yyregi.cgi';

# ログファイル
$logfile = './yylog.cgi';

# メールアドレスの入力必須 (0=no 1=yes)
$in_email = 0;

# 記事 [タイトル] 部の長さ (全角文字換算)
$sub_len = 12;

# 記事の [タイトル] 部の色
$subCol = "#006600";

# 記事表示部の下地の色
$tblCol = "#FFFFFF";

# 投稿フォーム及びボタンの文字色
$formCol1 = "#F7FAFD";	# 下地の色
$formCol2 = "#000000";	# 文字の色

# 家アイコンの使用 (0=no 1=yes)
$home_icon = 1;
$home_gif = "home.gif";	# 家アイコンのファイル名
$home_wid = 16;		# 画像の横サイズ
$home_hei = 20;		#   〃  縦サイズ

# イメージ参照画面の表示形態
#  1 : JavaScriptで表示
#  2 : HTMLで表示
$ImageView = 1;

# イメージ参照画面のサイズ (JavaScriptの場合)
$img_w = 550;	# 横幅
$img_h = 450;	# 高さ

# １ページ当たりの記事表示数 (親記事)
$pageView = 5;

# 投稿があるとメール通知する (sendmail必須)
#  0 : 通知しない
#  1 : 通知するが、自分の投稿記事は通知しない。
#  2 : すべて通知する。
$mailing = 0;

# メールアドレス(メール通知する時)
$mailto = 'xxx@xxx.xxx';

# sendmailパス（メール通知する時）
$sendmail = '/usr/lib/sendmail';

# 文字色の設定
#  →　スペースで区切る
$color = '#800000 #DF0000 #008040 #0000FF #C100C1 #FF80C0 #FF8040 #000080';

# URLの自動リンク (0=no 1=yes)
$autolink = 1;

# タグ広告挿入オプション
#  → <!-- 上部 --> <!-- 下部 --> の代わりに「広告タグ」を挿入
#  → 広告タグ以外に、MIDIタグ や LimeCounter等のタグにも使用可能
$banner1 = '<!-- 上部 -->';	# 掲示板上部に挿入
$banner2 = '<!-- 下部 -->';	# 掲示板下部に挿入

# ホスト取得方法
# 0 : gethostbyaddr関数を使わない
# 1 : gethostbyaddr関数を使う
$gethostbyaddr = 0;

# アクセス制限（半角スペースで区切る、アスタリスク可）
#  → 拒否ホスト名を記述（後方一致）【例】*.anonymizer.com
$deny_host = '';
#  → 拒否IPアドレスを記述（前方一致）【例】210.12.345.*
$deny_addr = '';

# １回当りの最大投稿サイズ (bytes)
$maxData = 51200;

# 記事の更新は method=POST 限定する場合（セキュリティ対策）
#  → 0=no 1=yes
$postonly = 1;

# 他サイトから投稿排除時に指定する場合（セキュリティ対策）
#  → 掲示板のURLをhttp://から書く
$baseUrl = '';

# 投稿制限（セキュリティ対策）
#  0 : しない
#  1 : 同一IPアドレスからの投稿間隔を制限する
#  2 : 全ての投稿間隔を制限する
$regCtl = 2;

# 制限投稿間隔（秒数）
#  → $regCtl での投稿間隔
$wait = 100;

# 投稿後の処理
#  → 掲示板自身のURLを記述しておくと、投稿後リロードします
#  → ブラウザを再読み込みしても二重投稿されない措置。
#  → Locationヘッダの使用可能なサーバのみ
$location = '';

# 禁止ワード
#  → コンマで区切って複数指定する（例）$deny_word = 'アダルト,出会い,カップル';
$deny_word = '';

#---(以下は「過去ログ」機能を使用する場合の設定です)---#
#
# 過去ログ生成 (0=no 1=yes)
$pastkey = 0;

# 過去ログ用NOファイル
$nofile = './pastno.dat';

# 過去ログのディレクトリ
#  → フルパスなら / から記述（http://からではない）
#  → 最後は必ず / で閉じる
$pastdir = './past/';

# 過去ログ１ファイルの行数
#  → この行数を超えると次ページを自動生成します
$pastmax = 650;

# １ページ当たりの記事表示数 (親記事)
$pastView = 10;


#-------------------------------------------------
# ログファイル消失防止
#-------------------------------------------------
# ログファイル消失防止機能の利用
# Write Errorになる場合は掲示板設置ディレクトリの
# パーミッションを(707や777に)変更するか、
# この機能をゼロに設定してください
# 0 : 利用しない
# 1 : 利用する
$logbackup = 1;

# 一時ログファイル
$tempfile = './yy_temp.cgi';

#-------------------------------------------------
# スパム投稿(宣伝投稿)拒否設定
#-------------------------------------------------
# 通常は設定変更の必用はありません(特に秒数設定)。
# そのままで運用して頂き、拒否できない投稿が多いか
# あるいは誤処理が多い場合にのみ設定を変更して下さい。
# [基本設定] のみの設定でほとんど全てのスパムを排除できます。
# 通常は [拡張オプション] を使用しないで(ゼロに設定して)下さい。

# ＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿
# [基本設定]  (ゼロにはせず、必ず設定して下さい)
# ￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣
# フォーム投稿確認用
# 削除すると動作しませんので絶対に削除しないで下さい。(変更は可)
# 半角の英数字およびアンダースコアのみ設定可能、空白や記号は設定不可です。
# 
# 変更する場合は意味不明な文字列にすることをお薦めします。
# (例) $bbscheckmode = 'L4g_Ks16_4Nd9c';
$bbscheckmode = 'YY_BOARD';

# 削除すると動作しませんので絶対に削除しないで下さい。(変更は可)
# 半角の英数字およびアンダースコアのみ設定可能、空白や記号は設定不可です。
# 特に必用がなければ、変更せずに初期設定のまま運用してください。
# 
# 変更する場合は意味不明な文字列かあるいは
# cancel,clear,delete,reject,reset,erase,annul,effase
# などの語句(を含む文字列)にして下さい。
# ただし、下で設定する$postvalueとは違う文字列にしてください。
# (例) $writevalue = 'k9SL0sv_3rk_wq2';
# (例) $writevalue = 'cancel';
$writevalue = 'cancel';

# 削除すると動作しませんので絶対に削除しないで下さい。(変更は可)
# 半角の英数字およびアンダースコアのみ設定可能、空白や記号は設定不可です。
# 特に必用がなければ、変更せずに初期設定のまま運用してください。
# 
# 変更する場合は意味不明な文字列かあるいは
# cancel,clear,delete,reject,reset,erase,annul,effase
# などの語句(を含む文字列)にして下さい。
# ただし、上で設定した$writevalueとは違う文字列にしてください。
# (例) $postvalue = 'x2oMw7fepc_7ge3';
# (例) $postvalue = 'clear';
$postvalue = 'clear';

# 削除すると動作しませんので絶対に削除しないで下さい。(変更は可)
# 半角の英数字およびアンダースコアのみ設定可能、空白や記号は設定不可です。
# 特に必用がなければ、変更せずに初期設定のまま運用してください。
$formcheck = 'formcheck';

# 掲示板アクセスからの経過時間(秒)
# 投稿フォームを使わないプログラム投稿対策です。
# 投稿者が掲示板を開いて投稿完了するまでの最小時間間隔です。
# 通常は数秒程度に設定しておきます。
# 初期設定は5秒で、ゼロにするとこのチェックは行いません。
$mintime = 5;

# 投稿者が掲示板を開いて投稿完了するまでの最長時間間隔です。
# 通常は7200秒(2時間)〜90000秒(25時間)程度に設定しておきます。
# 初期設定は18,000秒(5時間)で、ゼロにするとこのチェックは行いません。
$maxtime = 18000;

# 投稿までの間隔がグレーゾーンの場合には
# プレビューを表示してから投稿
# 0 : プレビューを表示しない
# 1 : プレビューを表示する【推奨】
$previewtime = 1;

# プレビュー非表示の最小時間
# アクセスから投稿までの時間間隔が設定秒数以下の場合、
# 投稿内容をプレビュー表示し、クリック後に書き込み処理をします。
# 通常は初期設定のままで問題ありません。
# 拒否されないスパムが多くなるようでしたら長く設定してください。
# 推奨値10〜60(秒)、初期設定は 15(秒)。
$previewmin = 15;

# プレビュー非表示の最大時間
# アクセスから投稿までの時間間隔が設定秒数以上の場合、
# 投稿内容をプレビュー表示し、クリック後に書き込み処理をします。
# 通常は初期設定のままで問題ありません。
# 拒否されないスパムが多くなるようでしたら短く設定してください。
# 推奨値1000〜10000(秒)、初期設定は5000秒(約80分)。
$previewmax = 5000;

# チェックデータの符号化処理
# 0 : 符号化しない
# 1 : 符号化する(解析対策)
$fcencode = 1;

# ハッシュキーの変換設定
# 0 : ハッシュキー変換しない
# 1 : ハッシュキー変換をする(スパム対策)
$keychange = 1;

# ＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿
# [投稿拒否ログ設定]  (スパム投稿として拒否された書き込みに関する設定です)
# ￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣
# 掲示板スパムの投稿拒否ログ
# 数ヶ月間はログを記録し、
# 誤処理がなければ「記録しない」にして下さい。
# 0 : 記録しない
# 1 : 記録する【推奨】
$spamlog = 1;

# 投稿拒否ログファイル
$spamlogfile = './spamlog.cgi';

# 投稿拒否ログ1ページあたりの表示数
# 20に設定すると、拒否ログ閲覧の1ページに20件の拒否ログを表示します
$spamlog_page = 20;

# 投稿拒否ログファイル設定
# 投稿拒否ログファイル容量が大きくなり設定容量を超過すると
# 警告を出すか古い拒否ログから順番に削除するかを選択します。
# 0 : 掲示板に警告メッセージをを出す
# 1 : 古い拒否ログから順に自動削除する
$spamlog_max = 1;

# 投稿拒否ログファイルの最大容量
# この許容量を超過すると上記の設定に従って
# 「投稿拒否ログファイルを削除」するよう警告を出すか、
# 古い拒否ログから順番にログを削除します。
# 初期値は 1000000 (1MB)。
$spamlog_maxfile = 1E06;

# 投稿拒否ログに残すURL許容数
# スパム投稿に、この設定値以上のURLが書き込まれていた場合、
# 拒否ログにはメッセージ本文を省略して記録します。
# 推奨値は20〜50、初期値は40。
$maxurl = 40;

# ＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿
# [オプション設定]  (必用があれば設定変更して下さい)
# ￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣
# スパムチェック緩和設定
# クッキーデータがある場合(常連投稿者)には
# スパムチェックを緩和し投稿しやすくします。
# 0 : 通常通りスパムチェックをする
# 1 : スパムチェックを緩和する【推奨】
$cookiecheck = 1;

# URL重複書き込み設定
# URL欄に記入したURLと同一URLがメッセージ内に書かれている場合
# スパム投稿と見なし書き込みを拒否します。
# 日本語のアダルト・出会い系・ワンクリック詐欺スパムに
# この傾向が多く見られます。
# 
# 0 : URLの重複書き込みを許可する
# 1 : 新規投稿の場合のみURLの重複書き込みを拒否する【推奨】
# 2 : 返信でもURLの重複書き込みを拒否する
$urlcheck = 1;

# 禁止語句(NGワード、URL)登録ファイル
# 書き込み禁止語句を登録するファイルです。
# このファイルに登録された語句、URLを本文やURL欄に書き込むと投稿拒否されます。
# このファイルを削除すると、禁止語句のチェックは行いません。
$spamdata = './spamdata.cgi';

# 最新の禁止語句(NGワード、URL)登録ファイルは下記よりダウンロードしてください。
# http://swanbay-web.hp.infoseek.co.jp/spamdata.html

# 禁止語句(NGワード、URL)チェック設定
# 0 : 新規投稿の場合のみ禁止語句(NGワード、URL)チェックをする【推奨】
# 1 : 返信でも禁止語句(NGワード、URL)チェックをする
$spamdatacheck = 0;

# 0 : メールアドレス欄は禁止語句チェックをしない
# 1 : メールアドレス欄も禁止語句チェックをする
$ngmail  = 1;

# 0 : タイトル欄は禁止語句チェックをしない
# 1 : タイトル欄も禁止語句チェックをする
$ngtitle = 1;

# ここでは、多数のURL書き込みを禁止することができます。
# URLの直接書き込みを許可する場合($comment_url = 0; に設定)は
# URLを書き込める限度数を設定します。
# 10に設定すると、http://〜を10以上書き込んだ投稿を拒否します。
# ゼロにするとこのチェックは行いません。初期設定は5(推奨値5〜10)。
$spamurlnum = 5;

# 掲示板スパム投稿時の処理
# 0 : 書き込み拒否のみ(下記のメッセージを表示)【推奨】
# 1 : 即時エラー表示
# それ以外の数値 : 数値秒後にエラー表示
# 3600に設定すると3600秒(60分)後にエラー表示
$spamresult = 0;

# スパムと判断された場合の表示メッセージ
# $spammsg = '投稿は正常に受理されました';
# と設定すると通常の書き込みと投稿拒否を区別できなくすることができます。
# スパム業者に投稿拒否を知られづらくなります。(日本語スパムが多い掲示板向け)
# $spammsg = '';
# とメッセージを設定しない場合には「404 Not Found」エラーを返して
# 掲示板が削除されたかのように振る舞います。
# 初期設定は
# $spammsg = '迷惑投稿として正常に処理されました';
$spammsg = '迷惑投稿として正常に処理されました';

# チェックデータのJavascript表示化
# 1に設定するとJavascript表示に対応していない
# プログラムからの投稿を排除することができます。
# 0 : チェックデータのJavascript表示しない
# 1 : チェックデータのJavascript表示化する(スパム対策)
$javascriptpost = 0;

# タイトル入力チェック
# 0 : タイトル未入力のときは「無題」にする
# 1 : タイトル未入力のときはエラー表示する
# 2 : 半角数字のみのタイトルやhttp://を含むタイトルのときはエラー表示する
$suberror = 0;

# メッセージ内の日本語をチェック
# メッセージ内にひらがな、あるいはカタカナが含まれているかをチェックします。
# 0 : メッセージに日本語が含まれていなくても投稿を許可する
# 1 : メッセージに日本語が含まれていない場合は投稿を拒否する
$asciicheck = 0;

# メッセージ文字数のチェック設定
# 20に設定すると、URLの記載がある場合に限り
# URL以外の文字数が半角文字で20文字未満、
# 全角文字で10文字未満の場合に投稿を拒否します。
# ゼロにするとこのチェックは行いません。
$characheck = 0;

# 投稿用の合い言葉設定
# 合い言葉の入力を必須とする場合に設定してください。
# (合い言葉設定例)
# $aikotoba = 'ほげほげ';
# 合い言葉を利用しない場合には何も書かないでください。
$aikotoba = '';

# 合い言葉を設定する場合、合い言葉のヒントを書いてください。
# (例) 合い言葉には○○○をひらがなで書いてください
$hint = "合い言葉欄には$aikotobaと書いてください";

# ＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿＿
# [拡張オプション設定] (非常用/特に必要性のある場合のみ設定して下さい。)
# ￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣￣
# この [拡張オプション] は基本的には設定せず、全てゼロのままご利用下さい。
# この項目を設定しなくてもスパム投稿は排除できます。
# 設定してスパムチェックを厳しくするとスパム投稿は全く無くなりますが、
# それと同時に、投稿時の制限が多いと通常の投稿も減ります。
# 
# 
# [拡張オプション] URLの直接書き込みを禁止する
# URL(http://〜)のメッセージ内への直接書き込みを禁止し、
# ttp://〜と書き込んだときだけ、URLの書き込みを許可します。
# 0 : URLの直接書き込みを許可する
# 1 : URLの直接書き込みを禁止する(URLを書き込む場合には ttp://〜と記述)
$comment_url = 0;

# [拡張オプション] 掲示板への直接アクセス投稿制限
# 掲示板へ直接アクセスした場合に投稿を禁止させることができます。
# 掲示板リストを作成して自動投稿をするようなスパムを排除できますが、
# ブックマークから直接掲示板にアクセスした場合も投稿制限を受けます。
# 0 : 投稿を許可する
# 1 : 投稿を禁止する(閲覧は可能)
# 2 : 「404 Not Found」エラーを返す
$referercheck = 0;

# [拡張オプション] URL転送・短縮URLの掲載禁止設定
# URL転送サービスおよび短縮URLサービスの疑いのあるURLを
# 本文かURL欄に掲載した場合、投稿を禁止させることができます。
# (例) http://symy.jp/ http://xrl.us/ http://jpan.jp/
# http://urlsnip.com/ http://tinyurl.com/ http://204.jp/  など
# 
# 0 : 投稿を許可する
# 1 : 投稿を禁止する
$shorturl = 0;

# [拡張オプション] 不正な暗証キーの禁止
# 暗証キーに半角スペースを含む場合や、
# 「111111」「aaaaa」のような一字の繰り返しを禁止できます
# 0 : 不正な暗証キーを禁止しない
# 1 : 不正な暗証キーを禁止する
$ng_pass = 0;

# [拡張オプション] メールアドレスの入力を禁止できます
# 0 : メールアドレスの入力を自由にする
# 1 : メールアドレスの入力を禁止する
# 2 : メールアドレスの入力はアットマークを全角入力「 ＠ 」に限定する
$no_email = 0;
if ($no_email) { $in_email = 0; }

# [拡張オプション] 携帯端末からの書き込みに対してスパム投稿チェック
# 0 : 携帯からの書き込みはスパムチェックをしないで投稿を許可する
#     携帯からはスパム投稿がない
# 1 : 携帯からの書き込みもスパムチェックをする
#     携帯からもスパム投稿の可能性がある
$keitaicheck = 0;

$method = 'POST';

#-------------------------------------------------
# ▲設定完了
#-------------------------------------------------

#-------------------------------------------------
#携帯端末別の分岐
#-------------------------------------------------
$imode = 0;
if (index($ENV{'HTTP_USER_AGENT'},"DoCoMo") >= 0){$imode = 1;}
if (index($ENV{'HTTP_USER_AGENT'},"UP\.Browser") >= 0){$imode = 2;}
if (index($ENV{'HTTP_USER_AGENT'},"PDXGW") >= 0){$imode = 3;}
if (index($ENV{'HTTP_USER_AGENT'},"ASTEL") >= 0){$imode = 4;}
if (index($ENV{'HTTP_USER_AGENT'},"J-PHONE") >= 0){$imode = 5;}
if (index($ENV{'HTTP_USER_AGENT'},"Vodafone") >= 0){$imode = 5;}
if (index($ENV{'HTTP_USER_AGENT'},"DDIPOCKET") >= 0){$imode = 6;}
if (index($ENV{'HTTP_USER_AGENT'},"L-mode") >= 0){$imode = 7;}
if (index($ENV{'HTTP_USER_AGENT'},"DoCoMo/2.0") >= 0){$imode = 8;}
#if (index($ENV{'HTTP_USER_AGENT'},"Opera") >= 0){$imode = 2;}	# テスト用

#-------------------------------------------------
#アイコン画像設定
#-------------------------------------------------
if ($imode == 2){
	if ($ez_gif){$imode_gif=$ez_gif;}
}elsif($imode == 3){
	if ($doti_gif){$imode_gif=$doti_gif;}
}elsif($imode == 4){
	if ($ddih_gif){$imode_gif=$ddih_gif;}
}elsif($imode == 5){
	if ($voda_gif){$imode_gif=$voda_gif;}
}elsif($imode == 6){
	if ($ddih_gif){$imode_gif=$ddih_gif;}
}elsif($imode == 7){
	if ($lmode_gif){$imode_gif=$lmode_gif;}
}elsif($imode == 8){
	if ($foma_gif){$imode_gif=$foma_gif;}
}

#-------------------------------------------------
#携帯端末の設定変更
#-------------------------------------------------
if ($imode){
	if ($counter){$counter = 1;}
	$icon_mode = 0; #アイコンをキャンセル
	$p_log = $imodenum;
	$title_gif = "";
	$baseUrl = '';

	if ($imode == 1){$title_gif = $imode_title;}
	if ($imode == 2){$title_gif = $ezweb_title;}
	if ($imode == 4){$title_gif = $doti_title;}

	#JスカイのみGET
	if ($imode == 5){
		if (index($ENV{'HTTP_USER_AGENT'},"J-PHONE/2.0") >= 0){
			$method = 'GET';
		}
		$title_gif = $jsky_title;
		$postonly = 0;
	}

}

#-------------------------------------------------
#入力フォームの設定
#-------------------------------------------------
if ($imode == 4) {
	# ASTELの場合
	$input_kanji = "astyle=\"hiragana\"";
	$input_alphabet = "astyle=\"alphabet\"";
	$input_numeric = "astyle=\"numeric\"";
}elsif ($imode == 5) {
	# J-PHONEの場合
	$input_kanji = "mode=\"hiragana\"";
	$input_alphabet = "mode=\"alphabet\"";
	$input_numeric = "mode=\"numeric\"";
}else {
	# その他
	$input_kanji ="istyle=1";
	$input_alphabet ="istyle=3";
	$input_numeric ="istyle=4";
}

#-------------------------------------------------
#  投稿画面
#-------------------------------------------------
sub form {
	local($nam,$eml,$url,$pwd,$ico,$col,$sub,$com) = @_;
	local(@ico1,@ico2,@col);

#	if ($url eq "") { $url = 'http://'; }
	$pattern = 'https?\:[\w\.\~\-\/\?\&\+\=\:\@\%\;\#\%]+';
	$com =~ s/<a href="$pattern" target="_blank">($pattern)<\/a>/$1/go;
	local($enaddress) = &encode_addr();
	if ($keychange && $mode ne "edit" && $mode ne "admin") {
		$url_key  = 'email'; $mail_key = 'url'; $name_key = 'comment'; $comment_key = 'name';
	} else { $url_key  = 'url'; $mail_key = 'email'; $name_key = 'name'; $comment_key = 'comment'; }

	print <<EOM;
<table border=0 cellspacing=1>
<tr>
  <td><b>お名前</b></td>
  <td><input type=text name=$name_key size=28 value="$nam" class=f></td>
</tr>
EOM
	if ($aikotoba) {
		print "<tr>\n  <td nowrap><b>合い言葉</b></td>\n  <td>",
		"<input type=text name=aikotoba size=10 value=\"$caikotoba\">",
		"<font color='#FF0000'>※必須</font></td>\n</tr>";
		if ($caikotoba ne $aikotoba) { print "<tr>\n  <td nowrap colspan=2><b>$hint</b></td>\n</tr>\n"; }
	}
	print <<EOM;
<tr>
  <td><b>Ｅメール</b></td><td>
    <input type=hidden name=mail size=28 value="$enaddress">
    <input type=text name=$mail_key size=28 value="$eml" class=f>
EOM
	if ($no_email eq '1') { print "<b style='color:#FF0000'>メールアドレスは入力禁止</b>"; }
	elsif ($no_email eq '2') { print "入力する場合には必ず<b style='color:#FF0000'>＠を全角で</b>書いて下さい"; }
	print <<EOM;
</td>
</tr>
<tr>
  <td><b>タイトル</b></td>
  <td>
<!-- //
    <input type=text name=subject size=36 value="" class=f>
// -->
    <input type=hidden name=title size=36 value="" class=f>
    <input type=hidden name=theme size=36 value="" class=f>
    <input type=text name=sub size=36 value="$sub" class=f>
EOM
	if (!$imode) {
		if (!$referercheck || $ENV{'HTTP_REFERER'}) {
			if ($mode ne "edit" && $mode ne "admin") {
				print "    <input type=hidden name=mode value=\"$writevalue\">\n"; }
			if ($javascriptpost) {
				print <<EOM;
<script type="text/javascript">
<!-- //
fcheck("mit value=投稿する><input t","<inpu","t type=sub","ype=reset value=リセット>");
// -->
</script>
<noscript><br><b>Javascriptを有効にしてください。</b><br><br></noscript>
EOM
			} else { print "<input type=submit value='投稿する'><input type=reset value='リセット'>"; }
		} else { print "<BR>\n<B>掲示板へ直接アクセスした場合には投稿できません。",
			"<A HREF=\"$homepage\">トップページ</A>から入り直してください。</B>\n"; }
	} else {
		if ($mode ne "edit" && $mode ne "admin") {
			print "    <input type=hidden name=mode value=\"$writevalue\">\n"; }
		print "<input type=submit value='投稿する'><input type=reset value='リセット'>";
	}

	print <<EOM;
  </td>
</tr>
<tr>
  <td colspan=2>
    <b>メッセージ
EOM
	if ($comment_url) { print "【メッセージ内のＵＲＬは先頭のｈを抜いて書き込んで下さい。】"; }
	print <<EOM;
</b><br>
    <textarea cols=56 rows=7 name=$comment_key wrap="soft" class=f>$com</textarea>
  </td>
</tr>
<tr>
  <td colspan=2>
EOM
	$f_c_d = int(rand(5E07)) + 11E08;
	if ($urlcheck) { print "  <b>メッセージ中には参照先URLと同じURLを書き込まないで下さい</b>\n"; }
	print <<EOM;
  <input type=hidden name=$formcheck value="$f_c_d"></td>
</tr>
<tr>
  <td><b>参照先</b></td>
  <td><input type=text size=52 name=$url_key value="$url" class=f></td>
</tr>
<!--//
<tr>
  <td><b>URL</b></td>
  <td><input type=text size=52 name=url2 value="" class=f></td>
</tr>
//-->
EOM

	# 管理者アイコンを配列に付加
	@ico1 = split(/\s+/, $ico1);
	@ico2 = split(/\s+/, $ico2);
	if ($my_icon) {
		push(@ico1,$my_gif);
		push(@ico2,"管理者用");
	}
	if ($iconMode) {
		print "<tr><td><b>イメージ</b></td>
		<td><select name=icon class=f>\n";
		foreach(0 .. $#ico1) {
			if ($ico eq $ico1[$_]) {
				print "<option value=\"$_\" selected>$ico2[$_]\n";
			} else {
				print "<option value=\"$_\">$ico2[$_]\n";
			}
		}
		print "</select> &nbsp;\n";

		# イメージ参照のリンク
		if ($ImageView == 1) {
			print "[<a href=\"javascript:ImageUp()\">イメージ参照</a>]";
		} else {
			print "[<a href=\"$script?mode=image\" target=\"_blank\">イメージ参照</a>]";
		}
		print "</td></tr>\n";
	}

	if ($pwd ne "??") {
		print "<tr><td><b>暗証キー</b></td>";
		print "<td><input type=password name=pwd size=8 maxlength=8 value=\"$pwd\" class=f>\n";
		print "(英数字で8文字以内)</td></tr>\n";
	}
	print "<tr><td><b>文字色</b></td><td>";

	# 色情報
	@col = split(/\s+/, $color);
	if ($col eq "") { $col = 0; }
	foreach (0 .. $#col) {
		if ($col eq $col[$_] || $col eq $_) {
			print "<input type=radio name=color value=\"$_\" checked>";
			print "<font color=\"$col[$_]\">■</font>\n";
		} else {
			print "<input type=radio name=color value=\"$_\">";
			print "<font color=\"$col[$_]\">■</font>\n";
		}
	}

	print <<EOM;
</td></tr></table>
EOM
}

#-------------------------------------------------
#  アクセス制限
#-------------------------------------------------
sub axsCheck {
	# IP&ホスト取得
	$host = $ENV{'REMOTE_HOST'};
	$addr = $ENV{'REMOTE_ADDR'};

	if ($gethostbyaddr && ($host eq "" || $host eq $addr)) {
		$host = gethostbyaddr(pack("C4", split(/\./, $addr)), 2);
	}

	# IPチェック
	local($flg);
	foreach (@denyaddr) {
		s/\./\\\./g;
		s/\*/\.\*/g;

		if ($addr =~ /^$_/i) { $flg = 1; last; }
	}
	if ($flg) {
		&error("アクセスを許可されていません");

	# ホストチェック
	} elsif ($host) {

		foreach ( split(/\s+/, $deny_host) ) {
			s/\./\\\./g;
			s/\*/\.\*/g;

			if ($host =~ /$_$/i) { $flg = 1; last; }
		}
		if ($flg) {
			&error("アクセスを許可されていません");
		}
	}
	if ($host eq "") { $host = $addr; }
	if (-e "$denyfile") { &spambot; }
	if ($type eq "p") {
		if ($referercheck==2 && !$ENV{'HTTP_REFERER'}) { &access_error; }
	}
}

#-------------------------------------------------
#  デコード処理
#-------------------------------------------------
sub decode {
	local($buf,$key,$val);
	undef(%in);

	if ($ENV{'REQUEST_METHOD'} eq "POST") {
		$post_flag=1;
		if ($ENV{'CONTENT_LENGTH'} > $maxData) {
			&error("投稿量が大きすぎます");
		}
		read(STDIN, $buf, $ENV{'CONTENT_LENGTH'});
	} else {
		$post_flag=0;
		$buf = $ENV{'QUERY_STRING'};
	}

	foreach ( split(/&/, $buf) ) {
		($key, $val) = split(/=/);
		$val =~ tr/+/ /;
		$val =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("H2", $1)/eg;

		# S-JISコード変換
		&jcode'convert(*val, "sjis", "", "z");

		# エスケープ
		$val =~ s/&/&amp;/g;
		$val =~ s/"/&quot;/g;
		$val =~ s/</&lt;/g;
		$val =~ s/>/&gt;/g;
		$val =~ s/\0//g;
		$val =~ s/\r\n/<br>/g;
		$val =~ s/\r/<br>/g;
		$val =~ s/\n/<br>/g;

		$in{$key} .= "\0" if (defined($in{$key}));
		$in{$key} .= $val;
	}
	$page = $in{'page'};
	$page =~ s/\D//g;
	if ($page < 0) { $page = 0; }
	$mode = $in{'mode'};

	$lockflag=0;
	$headflag=0;
}

#-------------------------------------------------
#  エラー処理
#-------------------------------------------------
sub error {
	# ロック中であれば解除
	if ($lockflag) { &unlock; }

	&header if (!$headflag);
	print <<EOM;
<div align="center">
<hr width=400><h3>ERROR !</h3>
<font color="red">$_[0]</font>
<p>
<hr width=400>
<p>
<form>
<input type=button value="前画面に戻る" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  HTMLヘッダ
#-------------------------------------------------
sub header {
	$headflag=1;

	if (!$imode){

	print "Content-type: text/html\n\n";
	print <<"EOM";
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=Shift_JIS">
<META HTTP-EQUIV="Content-Style-Type" content="text/css">
<META HTTP-EQUIV="Content-Script-Type" content="text/javascript">
<STYLE type="text/css">
<!--
body,td,th { font-size:$bSize; font-family:"$bFace"; }
a { text-decoration:none; }
a:hover { text-decoration:underline; color:$alink; }
.n { font-family:Verdana,Helvetica,Arial; }
.b {
	background-color:$formCol1;
	color:$formCol2;
	font-family:Verdana,Helvetica,Arial;
	}
.f {
	background-color:$formCol1;
	color:$formCol2;
	}
-->
</STYLE>
EOM
	# JavaScriptヘッダ

	print "<script type=\"text/javascript\">\n",
	"<!-- //\n",
	"function address(){\n",
	"user_name=address.arguments[1];\n",
	"document.write(user_name.link(\"mailto:\" + address.arguments[0] + \"&#64;\" + address.arguments[2]));\n",
	"}\n";

	if ($ImageView == 1 && $_[0] eq "ImageUp") {
		print "function ImageUp() {\n";
		print "window.open(\"$script?mode=image\",\"window1\",\"width=$img_w,height=$img_h,scrollbars=1\");\n}\n";
	}

	print "// -->\n</script>\n";

	print "<title>$title</title></head>\n";
	if ($backgif) {
		print "<body background=\"$backgif\" bgcolor=\"$bgcolor\" text=\"$text\" link=\"$link\" vlink=\"$vlink\" alink=\"$alink\">\n";
	} else {
		print "<body bgcolor=\"$bgcolor\" text=\"$text\" link=\"$link\" vlink=\"$vlink\" alink=\"$alink\">\n";
	}

	}else{
		print "Content-type: text/html\n\n";
		print "<html><title>$title</title><body>\n";
	}

}

#-------------------------------------------------
#  ロック処理
#-------------------------------------------------
sub lock {
	# リトライ回数
	local($retry)=5;

	# 古いロックは削除する
	if (-e $lockfile) {
		local($mtime) = (stat($lockfile))[9];
		if ($mtime < time - 30) { &unlock; }
	}

	# symlink関数式ロック
	if ($lockkey == 1) {
		while (!symlink(".", $lockfile)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}

	# mkdir関数式ロック
	} elsif ($lockkey == 2) {
		while (!mkdir($lockfile, 0755)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}
	}
	$lockflag=1;
}

#-------------------------------------------------
#  ロック解除
#-------------------------------------------------
sub unlock {
	if ($lockkey == 1) {
		unlink($lockfile);
	} elsif ($lockkey == 2) {
		rmdir($lockfile);
	}

	$lockflag=0;
}

#-------------------------------------------------
#  クッキー発行
#-------------------------------------------------
sub set_cookie {
	local(@cook) = @_;
	local($gmt, $cook, @t, @m, @w);

	@t = gmtime(time + 60*24*60*60);
	@m = ('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
	@w = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	# 国際標準時を定義
	$gmt = sprintf("%s, %02d-%s-%04d %02d:%02d:%02d GMT",
			$w[$t[6]], $t[3], $m[$t[4]], $t[5]+1900, $t[2], $t[1], $t[0]);

	# 保存データをURLエンコード
	foreach (@cook) {
		s/(\W)/sprintf("%%%02X", unpack("C", $1))/eg;
		$cook .= "$_<>";
	}

	# 格納
	print "Set-Cookie: YY_BOARD=$cook; expires=$gmt\n";
}

#-------------------------------------------------
#  クッキー取得
#-------------------------------------------------
sub get_cookie {
	local($key, $val, *cook);

	# クッキー取得
	$cook = $ENV{'HTTP_COOKIE'};

	# 該当IDを取り出す
	foreach ( split(/;/, $cook) ) {
		($key, $val) = split(/=/);
		$key =~ s/\s//g;
		$cook{$key} = $val;
	}

	# データをURLデコードして復元
	@cook=();
	foreach ( split(/<>/, $cook{'YY_BOARD'}) ) {
		s/%([0-9A-Fa-f][0-9A-Fa-f])/pack("H2", $1)/eg;

		push(@cook,$_);
	}
	return (@cook);
}

#-------------------------------------------------
#  移動ボタン
#-------------------------------------------------
sub mvbtn {
	local($link,$i,$view) = @_;
	local($start,$end,$x,$y,$bk_bl,$fw_bl);

#	if ($in{'view'}) { $view = $in{'view'}; }

	if ($in{'bl'}) {
		$start = $in{'bl'}*10 + 1;
		$end   = $start + 9;
	} else {
		$in{'bl'} = 0;
		$start = 1;
		$end   = 10;
	}

	$x=1; $y=0;
	while ($i > 0) {
		# 当ページ
		if ($page == $y) {

			print "| <b style='color:red' class=n>$x</b>\n";

		# 切替ページ
		} elsif ($x >= $start && $x <= $end) {

			print "| <a href=\"$link$y&bl=$in{'bl'}\" class=n>$x</a>\n";

		# 前ブロック
		} elsif ($x == $start-1) {

			$bk_bl = $in{'bl'}-1;
			print "| <a href=\"$link$y&bl=$bk_bl\">←</a>\n";

		# 次ブロック
		} elsif ($x == $end+1) {

			$fw_bl = $in{'bl'}+1;
			print "| <a href=\"$link$y&bl=$fw_bl\">→</a>\n";

		}

		$x++;
		$y += $view;
		$i -= $view;
	}

	print "|\n";
}

#-------------------------------------------------
#  検索処理
#-------------------------------------------------
sub search {
	local($file,$word,$view,$cond) = @_;
	local($i,$f,$top,$wd,$next,$back,@wd);

	# キーワードを配列化
	$word =~ s/\x81\x40/ /g;
	@wd = split(/\s+/, $word);

	# ファイル展開
	print "<dl>\n";
	$i=0;
	open(IN,"$file") || &error("Open Error: $file");
	$top = <IN> if ($mode ne "past");
	while (<IN>) {
		$f=0;
		foreach $wd (@wd) {
			if (index($_,$wd) >= 0) {
				$f++;
				if ($cond eq 'OR') { last; }
			} else {
				if ($cond eq 'AND') { $f=0; last; }
			}
		}

		# ヒットした場合
		if ($f) {
			$i++;
			next if ($i < $page + 1);
			next if ($i > $page + $view);

			($no,$reno,$dat,$nam,$eml,$sub,$com,$url,$hos,$pw,$col,$ico) = split(/<>/);
#			if ($eml) { $nam = "<a href=\"mailto:$eml\">$nam</a>"; }

			if (!$imode && $eml) { ($em0,$em1) = split(/\@/,$eml);
			$em1 =~ s/\./&#46;/g;
			$nam = "<script type=\"text/javascript\">\n<!-- //\n".
			"address(\"$em0\",\"$nam\",\"$em1\");\n// -->\n</script>\n".
			"<noscript><a href=\"$script?mode=noscript&page=$page\">$nam</a></noscript>\n";
			}

			if ($url) { $url = "&lt;<a href=\"$url\" target=\"_blank\">Home</a>&gt;"; }
			# 結果を表示
			print "<dt><hr>[<b>$no</b>] <b style=\"color:$subCol\">$sub</b> ";
			print "投稿者：<b>$nam</b> 投稿日：$dat $url<br><br>\n";
			print "<dd style=\"color:$col\">$com\n";
		}
	}
	close(IN);

	print <<EOM;
<dt><hr>
検索結果：<b>$i</b>件
</dl>
EOM
	$next = $page + $view;
	$back = $page - $view;
	return ($i, $next, $back);
}

#-------------------------------------------------
#  URLエンコード
#-------------------------------------------------
sub url_enc {
	local($_) = @_;

	s/(\W)/'%' . unpack('H2', $1)/eg;
	s/\s/+/g;
	$_;
}

#-------------------------------------------------
#  フォームチェックデータ符号化
#-------------------------------------------------
sub encode_bbsmode {
	local($fck) = shift;
	if(!$fck) { $fck = time; }
	if($fcencode) {
		srand;
		local($en) = rand(4); $en++; $en = int($en);
		if ($en%2) { $fck = sprintf("%X", $fck);
		} else { $fck = sprintf("%x", $fck); }
		if ($en == 1)    { $fck =~ tr/[0-9]/[g-p]/; }
		elsif ($en == 2) { $fck =~ tr/[0-9]/[q-z]/; }
		elsif ($en == 3) { $fck =~ tr/[0-9]/[G-P]/; }
		elsif ($en == 4) { $fck =~ tr/[0-9]/[Q-Z]/; }
		$fck = reverse($fck);
	}
	return $fck;
}

#-------------------------------------------------
#  フォームチェックデータ復号
#-------------------------------------------------
sub decode_bbsmode {
	local($fck) = shift;
	$fck2 = $fck;
	if ($fck =~ /[a-z]/i) {
		$fck = reverse($fck);
		$fck =~ tr/[g-p]/[0-9]/;
		$fck =~ tr/[q-z]/[0-9]/;
		$fck =~ tr/[G-P]/[0-9]/;
		$fck =~ tr/[Q-Z]/[0-9]/;
		$fck = sprintf("%d", hex($fck));
	}
	if($fck < 0) { $fck = $fck2; }
	return $fck;
}

#-------------------------------------------------
#  アドレス暗号化
#-------------------------------------------------
sub encode_addr {
	local ($adr,$i);
	$adr = shift;
	if (!$adr) { $adr = $addr; }
	$i=0;
	foreach (split(/\./, $adr)) {
		$addr[$i] = sprintf("%02x", $_);
		$i++;
	}
	$enadr = substr(crypt(join('',@addr), $addr[0]), 2);
	return $enadr;
}

#-------------------------------------------------
#  アクセス制限チェック
#-------------------------------------------------
sub spambot {
	open(IN, "$denyfile");
	local($deny) = <IN>;
	close (IN);
	local($flag) = 0;
	local(@denyip) = split(/\,/, $deny);
	foreach $denyip (@denyip) {
		if(length($denyip) > 7) {
			$denyip =~ s/\./\\\./g;
			$denyip =~ s/\*/\.\*/g;
			if ($addr =~ /^$denyip/) { $flag = 1; last; }
		}
	}
	if($flag) { &access_error; }
}

#-------------------------------------------------
#  アクセストラップエラー
#-------------------------------------------------
sub access_error {
	$script =~ s/^\.//;
	print "Content-type: text/html\n\n";
	print <<EOM;
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<HTML><HEAD>
<TITLE>404 Not Found</TITLE>
</HEAD><BODY>
<H1>Not Found</H1>
The requested URL $script was not found on this server.<P>
<HR>
<ADDRESS>Apache/1.3.34 Server at $ENV{'HTTP_HOST'} Port 80</ADDRESS>
</BODY></HTML>
EOM
	exit;
}


1;

__END__

