#��������������������������������������������������������������������
#�� YY-BOARD v5.5
#�� yyini.cgi - 2005/11/20
#�� Copyright (c) KentWeb
#�� webmaster@kent-web.com
#�� http://www.kent-web.com/
#��
#���g�ѓd�b�Ή��X�N���v�g
#��2005/01/04�@����H�@http://www.url-battle.com/cgi/
#��
#�� Modified by isso. August, 2006
#�� http://swanbay-web.hp.infoseek.co.jp/index.html
#��������������������������������������������������������������������
$ver = 'YY-BOARD v5.5 Rev1.87k';
#��������������������������������������������������������������������
#�� [���ӎ���]
#�� 1. ���̃X�N���v�g�̓t���[�\�t�g�ł��B���̃X�N���v�g���g�p����
#��    �����Ȃ鑹�Q�ɑ΂��č�҂͈�؂̐ӔC�𕉂��܂���B
#�� 2. ���ϔ�CGI�ݒu�Ɋւ��邲����͐ݒuURL�𖾋L�̂����A���L�܂ł��肢���܂��B
#��    http://swanbay-web.hp.infoseek.co.jp/index.html
#��    ���₢���킹�O�ɁA�u���̃T�C�g�ɂ��āv
#��    http://swanbay-web.hp.infoseek.co.jp/about.html
#��    �u�悭���邲����v
#��    http://swanbay-web.hp.infoseek.co.jp/faq.html
#��   �u���₢���킹�Ɋւ��钍�ӎ����v
#��    http://swanbay-web.hp.infoseek.co.jp/mail.html
#��    �ɕK���ڂ�ʂ��Ă��������B
#��
#��    �ŐV��NG���[�h�f�[�^�t�@�C���͉��L���_�E�����[�h���Ă��������B
#��    http://swanbay-web.hp.infoseek.co.jp/spamdata.html
#��
#��    �A�N�Z�X����IP�A�h���X�t�@�C�����L���_�E�����[�h���Ă��������B
#��    http://swanbay-web.hp.infoseek.co.jp/accessdeny.html
#��
#��    �f���ւ̃����N���@��Javascript�\��������@�͉��L���Q�Ɖ������B
#��    http://swanbay-web.hp.infoseek.co.jp/cgi-bin/javascript.html
#��
#��    �����A�N�Z�X�����̗��p���@�͉��L�T�C�g���Q�Ɖ������B
#��    http://swanbay-web.hp.infoseek.co.jp/accesstrap/index.html
#���Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q
#���{�����X�N���v�g�Ɋւ��Ă�KENT���ɖ₢���킹���Ȃ��悤���肢���܂��B
#���P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P
#�� 3. �Y�t�� home.gif �� L.O.V.E �� mayuRin ����ɂ��摜�ł��B
#��������������������������������������������������������������������
#
# �y�t�@�C���\����z
#
#  public_html (�z�[���f�B���N�g��)
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
# ���ݒ荀��
#-------------------------------------------------

# �^�C�g����
$title = "����BBS";

# �^�C�g�������F
$tCol = "#000000";

# �^�C�g���T�C�Y
$tSize = '26px';

# �{�������t�H���g
$bFace = "MS UI Gothic, Osaka, �l�r �o�S�V�b�N";

# �{�������T�C�Y
$bSize = '15px';

# �ǎ����w�肷��ꍇ�ihttp://����w��j
$backgif = "";

# �w�i�F���w��
$bgcolor = "#FFFFFF";

# �����F���w��
$text = "#000000";

# �����N�F���w��
$link  = "#0000FF";	# ���K��
$vlink = "#800080";	# �K���
$alink = "#FF0000";	# �K�⒆

# �߂���URL (index.html�Ȃ�)
$homepage = "http://www.sysken.net/";

# �ő�L����
$max = 100;

# �Ǘ��җp�p�X���[�h (�p�����łW�����ȓ�)
$pass = '[set-bbs:su]';

# �A�C�R���摜�̂���f�B���N�g��
# �� �t���p�X�Ȃ� http:// ����L�q����
# �� �Ō�͕K�� / �ŕ���
$imgurl = "./img/";

# �A�C�R�����`
# ���@�㉺�͕K���y�A�ɂ��āA�X�y�[�X�ŋ�؂�
$ico1 = 'bear.gif cat.gif cow.gif dog.gif fox.gif hituji.gif monkey.gif zou.gif mouse.gif panda.gif pig.gif usagi.gif';
$ico2 = '���� �˂� ���� ���� ���� �Ђ� ���� ���� �˂��� �p���_ �Ԃ� ������';

# �Ǘ��Ґ�p�A�C�R���@�\ (0=no 1=yes)
# (�g����) �L�����e���Ɂu�Ǘ��҃A�C�R���v��I�����A�Ï؃L�[��
#         �u�Ǘ��p�X���[�h�v����͂��ĉ������B
$my_icon = 0;

# �Ǘ��Ґ�p�A�C�R���́u�t�@�C�����v���w��
$my_gif  = 'admin.gif';

# �A�C�R�����[�h (0=no 1=yes)
$iconMode = 0;

# �ԐM�����Ɛe�L�����g�b�v�ֈړ� (0=no 1=yes)
$topsort = 1;

# �^�C�g����GIF�摜���g�p���鎞 (http://����L�q)
$t_img = "";
$t_w = 150;	# �摜�̕� (�s�N�Z��)
$t_h = 50;	#   �V  ���� (�s�N�Z��)

# �t�@�C�����b�N�`��
#  �� 0=no 1=symlink�֐� 2=mkdir�֐�
$lockkey = 0;

# ���b�N�t�@�C����
$lockfile = './lock/yybbs.lock';

# �~�j�J�E���^�̐ݒu
#  �� 0=no 1=�e�L�X�g 2=�摜
$counter = 1;

# �~�j�J�E���^�̌���
$mini_fig = 6;

# �e�L�X�g�̂Ƃ��F�~�j�J�E���^�̐F
$cntCol = "#DD0000";

# �摜�̂Ƃ��F�摜�f�B���N�g�����w��
#  �� �Ō�͕K�� / �ŕ���
$gif_path = "./img/";
$mini_w = 8;		# �摜�̉��T�C�Y
$mini_h = 12;		# �摜�̏c�T�C�Y

# �J�E���^�t�@�C��
$cntfile = './count.dat';

# �{�̃t�@�C��URL
$script = './yybbs.cgi';

# �X�V�t�@�C��URL
$regist = './yyregi.cgi';

# ���O�t�@�C��
$logfile = './yylog.cgi';

# ���[���A�h���X�̓��͕K�{ (0=no 1=yes)
$in_email = 0;

# �L�� [�^�C�g��] ���̒��� (�S�p�������Z)
$sub_len = 12;

# �L���� [�^�C�g��] ���̐F
$subCol = "#006600";

# �L���\�����̉��n�̐F
$tblCol = "#FFFFFF";

# ���e�t�H�[���y�у{�^���̕����F
$formCol1 = "#F7FAFD";	# ���n�̐F
$formCol2 = "#000000";	# �����̐F

# �ƃA�C�R���̎g�p (0=no 1=yes)
$home_icon = 1;
$home_gif = "home.gif";	# �ƃA�C�R���̃t�@�C����
$home_wid = 16;		# �摜�̉��T�C�Y
$home_hei = 20;		#   �V  �c�T�C�Y

# �C���[�W�Q�Ɖ�ʂ̕\���`��
#  1 : JavaScript�ŕ\��
#  2 : HTML�ŕ\��
$ImageView = 1;

# �C���[�W�Q�Ɖ�ʂ̃T�C�Y (JavaScript�̏ꍇ)
$img_w = 550;	# ����
$img_h = 450;	# ����

# �P�y�[�W������̋L���\���� (�e�L��)
$pageView = 5;

# ���e������ƃ��[���ʒm���� (sendmail�K�{)
#  0 : �ʒm���Ȃ�
#  1 : �ʒm���邪�A�����̓��e�L���͒ʒm���Ȃ��B
#  2 : ���ׂĒʒm����B
$mailing = 0;

# ���[���A�h���X(���[���ʒm���鎞)
$mailto = 'xxx@xxx.xxx';

# sendmail�p�X�i���[���ʒm���鎞�j
$sendmail = '/usr/lib/sendmail';

# �����F�̐ݒ�
#  ���@�X�y�[�X�ŋ�؂�
$color = '#800000 #DF0000 #008040 #0000FF #C100C1 #FF80C0 #FF8040 #000080';

# URL�̎��������N (0=no 1=yes)
$autolink = 1;

# �^�O�L���}���I�v�V����
#  �� <!-- �㕔 --> <!-- ���� --> �̑���Ɂu�L���^�O�v��}��
#  �� �L���^�O�ȊO�ɁAMIDI�^�O �� LimeCounter���̃^�O�ɂ��g�p�\
$banner1 = '<!-- �㕔 -->';	# �f���㕔�ɑ}��
$banner2 = '<!-- ���� -->';	# �f�������ɑ}��

# �z�X�g�擾���@
# 0 : gethostbyaddr�֐����g��Ȃ�
# 1 : gethostbyaddr�֐����g��
$gethostbyaddr = 0;

# �A�N�Z�X�����i���p�X�y�[�X�ŋ�؂�A�A�X�^���X�N�j
#  �� ���ۃz�X�g�����L�q�i�����v�j�y��z*.anonymizer.com
$deny_host = '';
#  �� ����IP�A�h���X���L�q�i�O����v�j�y��z210.12.345.*
$deny_addr = '';

# �P�񓖂�̍ő哊�e�T�C�Y (bytes)
$maxData = 51200;

# �L���̍X�V�� method=POST ���肷��ꍇ�i�Z�L�����e�B�΍�j
#  �� 0=no 1=yes
$postonly = 1;

# ���T�C�g���瓊�e�r�����Ɏw�肷��ꍇ�i�Z�L�����e�B�΍�j
#  �� �f����URL��http://���珑��
$baseUrl = '';

# ���e�����i�Z�L�����e�B�΍�j
#  0 : ���Ȃ�
#  1 : ����IP�A�h���X����̓��e�Ԋu�𐧌�����
#  2 : �S�Ă̓��e�Ԋu�𐧌�����
$regCtl = 2;

# �������e�Ԋu�i�b���j
#  �� $regCtl �ł̓��e�Ԋu
$wait = 100;

# ���e��̏���
#  �� �f�����g��URL���L�q���Ă����ƁA���e�ナ���[�h���܂�
#  �� �u���E�U���ēǂݍ��݂��Ă���d���e����Ȃ��[�u�B
#  �� Location�w�b�_�̎g�p�\�ȃT�[�o�̂�
$location = '';

# �֎~���[�h
#  �� �R���}�ŋ�؂��ĕ����w�肷��i��j$deny_word = '�A�_���g,�o�,�J�b�v��';
$deny_word = '';

#---(�ȉ��́u�ߋ����O�v�@�\���g�p����ꍇ�̐ݒ�ł�)---#
#
# �ߋ����O���� (0=no 1=yes)
$pastkey = 0;

# �ߋ����O�pNO�t�@�C��
$nofile = './pastno.dat';

# �ߋ����O�̃f�B���N�g��
#  �� �t���p�X�Ȃ� / ����L�q�ihttp://����ł͂Ȃ��j
#  �� �Ō�͕K�� / �ŕ���
$pastdir = './past/';

# �ߋ����O�P�t�@�C���̍s��
#  �� ���̍s���𒴂���Ǝ��y�[�W�������������܂�
$pastmax = 650;

# �P�y�[�W������̋L���\���� (�e�L��)
$pastView = 10;


#-------------------------------------------------
# ���O�t�@�C�������h�~
#-------------------------------------------------
# ���O�t�@�C�������h�~�@�\�̗��p
# Write Error�ɂȂ�ꍇ�͌f���ݒu�f�B���N�g����
# �p�[�~�b�V������(707��777��)�ύX���邩�A
# ���̋@�\���[���ɐݒ肵�Ă�������
# 0 : ���p���Ȃ�
# 1 : ���p����
$logbackup = 1;

# �ꎞ���O�t�@�C��
$tempfile = './yy_temp.cgi';

#-------------------------------------------------
# �X�p�����e(��`���e)���ېݒ�
#-------------------------------------------------
# �ʏ�͐ݒ�ύX�̕K�p�͂���܂���(���ɕb���ݒ�)�B
# ���̂܂܂ŉ^�p���Ē����A���ۂł��Ȃ����e��������
# ���邢�͌돈���������ꍇ�ɂ̂ݐݒ��ύX���ĉ������B
# [��{�ݒ�] �݂̂̐ݒ�łقƂ�ǑS�ẴX�p����r���ł��܂��B
# �ʏ�� [�g���I�v�V����] ���g�p���Ȃ���(�[���ɐݒ肵��)�������B

# �Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q
# [��{�ݒ�]  (�[���ɂ͂����A�K���ݒ肵�ĉ�����)
# �P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P
# �t�H�[�����e�m�F�p
# �폜����Ɠ��삵�܂���̂Ő�΂ɍ폜���Ȃ��ŉ������B(�ύX�͉�)
# ���p�̉p��������уA���_�[�X�R�A�̂ݐݒ�\�A�󔒂�L���͐ݒ�s�ł��B
# 
# �ύX����ꍇ�͈Ӗ��s���ȕ�����ɂ��邱�Ƃ����E�߂��܂��B
# (��) $bbscheckmode = 'L4g_Ks16_4Nd9c';
$bbscheckmode = 'YY_BOARD';

# �폜����Ɠ��삵�܂���̂Ő�΂ɍ폜���Ȃ��ŉ������B(�ύX�͉�)
# ���p�̉p��������уA���_�[�X�R�A�̂ݐݒ�\�A�󔒂�L���͐ݒ�s�ł��B
# ���ɕK�p���Ȃ���΁A�ύX�����ɏ����ݒ�̂܂܉^�p���Ă��������B
# 
# �ύX����ꍇ�͈Ӗ��s���ȕ����񂩂��邢��
# cancel,clear,delete,reject,reset,erase,annul,effase
# �Ȃǂ̌��(���܂ޕ�����)�ɂ��ĉ������B
# �������A���Őݒ肷��$postvalue�Ƃ͈Ⴄ������ɂ��Ă��������B
# (��) $writevalue = 'k9SL0sv_3rk_wq2';
# (��) $writevalue = 'cancel';
$writevalue = 'cancel';

# �폜����Ɠ��삵�܂���̂Ő�΂ɍ폜���Ȃ��ŉ������B(�ύX�͉�)
# ���p�̉p��������уA���_�[�X�R�A�̂ݐݒ�\�A�󔒂�L���͐ݒ�s�ł��B
# ���ɕK�p���Ȃ���΁A�ύX�����ɏ����ݒ�̂܂܉^�p���Ă��������B
# 
# �ύX����ꍇ�͈Ӗ��s���ȕ����񂩂��邢��
# cancel,clear,delete,reject,reset,erase,annul,effase
# �Ȃǂ̌��(���܂ޕ�����)�ɂ��ĉ������B
# �������A��Őݒ肵��$writevalue�Ƃ͈Ⴄ������ɂ��Ă��������B
# (��) $postvalue = 'x2oMw7fepc_7ge3';
# (��) $postvalue = 'clear';
$postvalue = 'clear';

# �폜����Ɠ��삵�܂���̂Ő�΂ɍ폜���Ȃ��ŉ������B(�ύX�͉�)
# ���p�̉p��������уA���_�[�X�R�A�̂ݐݒ�\�A�󔒂�L���͐ݒ�s�ł��B
# ���ɕK�p���Ȃ���΁A�ύX�����ɏ����ݒ�̂܂܉^�p���Ă��������B
$formcheck = 'formcheck';

# �f���A�N�Z�X����̌o�ߎ���(�b)
# ���e�t�H�[�����g��Ȃ��v���O�������e�΍�ł��B
# ���e�҂��f�����J���ē��e��������܂ł̍ŏ����ԊԊu�ł��B
# �ʏ�͐��b���x�ɐݒ肵�Ă����܂��B
# �����ݒ��5�b�ŁA�[���ɂ���Ƃ��̃`�F�b�N�͍s���܂���B
$mintime = 5;

# ���e�҂��f�����J���ē��e��������܂ł̍Œ����ԊԊu�ł��B
# �ʏ��7200�b(2����)�`90000�b(25����)���x�ɐݒ肵�Ă����܂��B
# �����ݒ��18,000�b(5����)�ŁA�[���ɂ���Ƃ��̃`�F�b�N�͍s���܂���B
$maxtime = 18000;

# ���e�܂ł̊Ԋu���O���[�]�[���̏ꍇ�ɂ�
# �v���r���[��\�����Ă��瓊�e
# 0 : �v���r���[��\�����Ȃ�
# 1 : �v���r���[��\������y�����z
$previewtime = 1;

# �v���r���[��\���̍ŏ�����
# �A�N�Z�X���瓊�e�܂ł̎��ԊԊu���ݒ�b���ȉ��̏ꍇ�A
# ���e���e���v���r���[�\�����A�N���b�N��ɏ������ݏ��������܂��B
# �ʏ�͏����ݒ�̂܂܂Ŗ�肠��܂���B
# ���ۂ���Ȃ��X�p���������Ȃ�悤�ł����璷���ݒ肵�Ă��������B
# �����l10�`60(�b)�A�����ݒ�� 15(�b)�B
$previewmin = 15;

# �v���r���[��\���̍ő厞��
# �A�N�Z�X���瓊�e�܂ł̎��ԊԊu���ݒ�b���ȏ�̏ꍇ�A
# ���e���e���v���r���[�\�����A�N���b�N��ɏ������ݏ��������܂��B
# �ʏ�͏����ݒ�̂܂܂Ŗ�肠��܂���B
# ���ۂ���Ȃ��X�p���������Ȃ�悤�ł�����Z���ݒ肵�Ă��������B
# �����l1000�`10000(�b)�A�����ݒ��5000�b(��80��)�B
$previewmax = 5000;

# �`�F�b�N�f�[�^�̕���������
# 0 : ���������Ȃ�
# 1 : ����������(��͑΍�)
$fcencode = 1;

# �n�b�V���L�[�̕ϊ��ݒ�
# 0 : �n�b�V���L�[�ϊ����Ȃ�
# 1 : �n�b�V���L�[�ϊ�������(�X�p���΍�)
$keychange = 1;

# �Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q
# [���e���ۃ��O�ݒ�]  (�X�p�����e�Ƃ��ċ��ۂ��ꂽ�������݂Ɋւ���ݒ�ł�)
# �P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P
# �f���X�p���̓��e���ۃ��O
# �������Ԃ̓��O���L�^���A
# �돈�����Ȃ���΁u�L�^���Ȃ��v�ɂ��ĉ������B
# 0 : �L�^���Ȃ�
# 1 : �L�^����y�����z
$spamlog = 1;

# ���e���ۃ��O�t�@�C��
$spamlogfile = './spamlog.cgi';

# ���e���ۃ��O1�y�[�W������̕\����
# 20�ɐݒ肷��ƁA���ۃ��O�{����1�y�[�W��20���̋��ۃ��O��\�����܂�
$spamlog_page = 20;

# ���e���ۃ��O�t�@�C���ݒ�
# ���e���ۃ��O�t�@�C���e�ʂ��傫���Ȃ�ݒ�e�ʂ𒴉߂����
# �x�����o�����Â����ۃ��O���珇�Ԃɍ폜���邩��I�����܂��B
# 0 : �f���Ɍx�����b�Z�[�W�����o��
# 1 : �Â����ۃ��O���珇�Ɏ����폜����
$spamlog_max = 1;

# ���e���ۃ��O�t�@�C���̍ő�e��
# ���̋��e�ʂ𒴉߂���Ə�L�̐ݒ�ɏ]����
# �u���e���ۃ��O�t�@�C�����폜�v����悤�x�����o�����A
# �Â����ۃ��O���珇�ԂɃ��O���폜���܂��B
# �����l�� 1000000 (1MB)�B
$spamlog_maxfile = 1E06;

# ���e���ۃ��O�Ɏc��URL���e��
# �X�p�����e�ɁA���̐ݒ�l�ȏ��URL���������܂�Ă����ꍇ�A
# ���ۃ��O�ɂ̓��b�Z�[�W�{�����ȗ����ċL�^���܂��B
# �����l��20�`50�A�����l��40�B
$maxurl = 40;

# �Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q
# [�I�v�V�����ݒ�]  (�K�p������ΐݒ�ύX���ĉ�����)
# �P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P
# �X�p���`�F�b�N�ɘa�ݒ�
# �N�b�L�[�f�[�^������ꍇ(��A���e��)�ɂ�
# �X�p���`�F�b�N���ɘa�����e���₷�����܂��B
# 0 : �ʏ�ʂ�X�p���`�F�b�N������
# 1 : �X�p���`�F�b�N���ɘa����y�����z
$cookiecheck = 1;

# URL�d���������ݐݒ�
# URL���ɋL������URL�Ɠ���URL�����b�Z�[�W���ɏ�����Ă���ꍇ
# �X�p�����e�ƌ��Ȃ��������݂����ۂ��܂��B
# ���{��̃A�_���g�E�o��n�E�����N���b�N���\�X�p����
# ���̌X�������������܂��B
# 
# 0 : URL�̏d���������݂�������
# 1 : �V�K���e�̏ꍇ�̂�URL�̏d���������݂����ۂ���y�����z
# 2 : �ԐM�ł�URL�̏d���������݂����ۂ���
$urlcheck = 1;

# �֎~���(NG���[�h�AURL)�o�^�t�@�C��
# �������݋֎~����o�^����t�@�C���ł��B
# ���̃t�@�C���ɓo�^���ꂽ���AURL��{����URL���ɏ������ނƓ��e���ۂ���܂��B
# ���̃t�@�C�����폜����ƁA�֎~���̃`�F�b�N�͍s���܂���B
$spamdata = './spamdata.cgi';

# �ŐV�̋֎~���(NG���[�h�AURL)�o�^�t�@�C���͉��L���_�E�����[�h���Ă��������B
# http://swanbay-web.hp.infoseek.co.jp/spamdata.html

# �֎~���(NG���[�h�AURL)�`�F�b�N�ݒ�
# 0 : �V�K���e�̏ꍇ�̂݋֎~���(NG���[�h�AURL)�`�F�b�N������y�����z
# 1 : �ԐM�ł��֎~���(NG���[�h�AURL)�`�F�b�N������
$spamdatacheck = 0;

# 0 : ���[���A�h���X���͋֎~���`�F�b�N�����Ȃ�
# 1 : ���[���A�h���X�����֎~���`�F�b�N������
$ngmail  = 1;

# 0 : �^�C�g�����͋֎~���`�F�b�N�����Ȃ�
# 1 : �^�C�g�������֎~���`�F�b�N������
$ngtitle = 1;

# �����ł́A������URL�������݂��֎~���邱�Ƃ��ł��܂��B
# URL�̒��ڏ������݂�������ꍇ($comment_url = 0; �ɐݒ�)��
# URL���������߂���x����ݒ肵�܂��B
# 10�ɐݒ肷��ƁAhttp://�`��10�ȏ㏑�����񂾓��e�����ۂ��܂��B
# �[���ɂ���Ƃ��̃`�F�b�N�͍s���܂���B�����ݒ��5(�����l5�`10)�B
$spamurlnum = 5;

# �f���X�p�����e���̏���
# 0 : �������݋��ۂ̂�(���L�̃��b�Z�[�W��\��)�y�����z
# 1 : �����G���[�\��
# ����ȊO�̐��l : ���l�b��ɃG���[�\��
# 3600�ɐݒ肷���3600�b(60��)��ɃG���[�\��
$spamresult = 0;

# �X�p���Ɣ��f���ꂽ�ꍇ�̕\�����b�Z�[�W
# $spammsg = '���e�͐���Ɏ󗝂���܂���';
# �Ɛݒ肷��ƒʏ�̏������݂Ɠ��e���ۂ���ʂł��Ȃ����邱�Ƃ��ł��܂��B
# �X�p���Ǝ҂ɓ��e���ۂ�m���Â炭�Ȃ�܂��B(���{��X�p���������f������)
# $spammsg = '';
# �ƃ��b�Z�[�W��ݒ肵�Ȃ��ꍇ�ɂ́u404 Not Found�v�G���[��Ԃ���
# �f�����폜���ꂽ���̂悤�ɐU�镑���܂��B
# �����ݒ��
# $spammsg = '���f���e�Ƃ��Đ���ɏ�������܂���';
$spammsg = '���f���e�Ƃ��Đ���ɏ�������܂���';

# �`�F�b�N�f�[�^��Javascript�\����
# 1�ɐݒ肷���Javascript�\���ɑΉ����Ă��Ȃ�
# �v���O��������̓��e��r�����邱�Ƃ��ł��܂��B
# 0 : �`�F�b�N�f�[�^��Javascript�\�����Ȃ�
# 1 : �`�F�b�N�f�[�^��Javascript�\��������(�X�p���΍�)
$javascriptpost = 0;

# �^�C�g�����̓`�F�b�N
# 0 : �^�C�g�������͂̂Ƃ��́u����v�ɂ���
# 1 : �^�C�g�������͂̂Ƃ��̓G���[�\������
# 2 : ���p�����݂̂̃^�C�g����http://���܂ރ^�C�g���̂Ƃ��̓G���[�\������
$suberror = 0;

# ���b�Z�[�W���̓��{����`�F�b�N
# ���b�Z�[�W���ɂЂ炪�ȁA���邢�̓J�^�J�i���܂܂�Ă��邩���`�F�b�N���܂��B
# 0 : ���b�Z�[�W�ɓ��{�ꂪ�܂܂�Ă��Ȃ��Ă����e��������
# 1 : ���b�Z�[�W�ɓ��{�ꂪ�܂܂�Ă��Ȃ��ꍇ�͓��e�����ۂ���
$asciicheck = 0;

# ���b�Z�[�W�������̃`�F�b�N�ݒ�
# 20�ɐݒ肷��ƁAURL�̋L�ڂ�����ꍇ�Ɍ���
# URL�ȊO�̕����������p������20���������A
# �S�p������10���������̏ꍇ�ɓ��e�����ۂ��܂��B
# �[���ɂ���Ƃ��̃`�F�b�N�͍s���܂���B
$characheck = 0;

# ���e�p�̍������t�ݒ�
# �������t�̓��͂�K�{�Ƃ���ꍇ�ɐݒ肵�Ă��������B
# (�������t�ݒ��)
# $aikotoba = '�ق��ق�';
# �������t�𗘗p���Ȃ��ꍇ�ɂ͉��������Ȃ��ł��������B
$aikotoba = '';

# �������t��ݒ肷��ꍇ�A�������t�̃q���g�������Ă��������B
# (��) �������t�ɂ́��������Ђ炪�Ȃŏ����Ă�������
$hint = "�������t���ɂ�$aikotoba�Ə����Ă�������";

# �Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q�Q
# [�g���I�v�V�����ݒ�] (���p/���ɕK�v���̂���ꍇ�̂ݐݒ肵�ĉ������B)
# �P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P�P
# ���� [�g���I�v�V����] �͊�{�I�ɂ͐ݒ肹���A�S�ă[���̂܂܂����p�������B
# ���̍��ڂ�ݒ肵�Ȃ��Ă��X�p�����e�͔r���ł��܂��B
# �ݒ肵�ăX�p���`�F�b�N������������ƃX�p�����e�͑S�������Ȃ�܂����A
# ����Ɠ����ɁA���e���̐����������ƒʏ�̓��e������܂��B
# 
# 
# [�g���I�v�V����] URL�̒��ڏ������݂��֎~����
# URL(http://�`)�̃��b�Z�[�W���ւ̒��ڏ������݂��֎~���A
# ttp://�`�Ə������񂾂Ƃ������AURL�̏������݂������܂��B
# 0 : URL�̒��ڏ������݂�������
# 1 : URL�̒��ڏ������݂��֎~����(URL���������ޏꍇ�ɂ� ttp://�`�ƋL�q)
$comment_url = 0;

# [�g���I�v�V����] �f���ւ̒��ڃA�N�Z�X���e����
# �f���֒��ڃA�N�Z�X�����ꍇ�ɓ��e���֎~�����邱�Ƃ��ł��܂��B
# �f�����X�g���쐬���Ď������e������悤�ȃX�p����r���ł��܂����A
# �u�b�N�}�[�N���璼�ڌf���ɃA�N�Z�X�����ꍇ�����e�������󂯂܂��B
# 0 : ���e��������
# 1 : ���e���֎~����(�{���͉\)
# 2 : �u404 Not Found�v�G���[��Ԃ�
$referercheck = 0;

# [�g���I�v�V����] URL�]���E�Z�kURL�̌f�ڋ֎~�ݒ�
# URL�]���T�[�r�X����ђZ�kURL�T�[�r�X�̋^���̂���URL��
# �{����URL���Ɍf�ڂ����ꍇ�A���e���֎~�����邱�Ƃ��ł��܂��B
# (��) http://symy.jp/ http://xrl.us/ http://jpan.jp/
# http://urlsnip.com/ http://tinyurl.com/ http://204.jp/  �Ȃ�
# 
# 0 : ���e��������
# 1 : ���e���֎~����
$shorturl = 0;

# [�g���I�v�V����] �s���ȈÏ؃L�[�̋֎~
# �Ï؃L�[�ɔ��p�X�y�[�X���܂ޏꍇ��A
# �u111111�v�uaaaaa�v�̂悤�Ȉꎚ�̌J��Ԃ����֎~�ł��܂�
# 0 : �s���ȈÏ؃L�[���֎~���Ȃ�
# 1 : �s���ȈÏ؃L�[���֎~����
$ng_pass = 0;

# [�g���I�v�V����] ���[���A�h���X�̓��͂��֎~�ł��܂�
# 0 : ���[���A�h���X�̓��͂����R�ɂ���
# 1 : ���[���A�h���X�̓��͂��֎~����
# 2 : ���[���A�h���X�̓��͂̓A�b�g�}�[�N��S�p���́u �� �v�Ɍ��肷��
$no_email = 0;
if ($no_email) { $in_email = 0; }

# [�g���I�v�V����] �g�ђ[������̏������݂ɑ΂��ăX�p�����e�`�F�b�N
# 0 : �g�т���̏������݂̓X�p���`�F�b�N�����Ȃ��œ��e��������
#     �g�т���̓X�p�����e���Ȃ�
# 1 : �g�т���̏������݂��X�p���`�F�b�N������
#     �g�т�����X�p�����e�̉\��������
$keitaicheck = 0;

$method = 'POST';

#-------------------------------------------------
# ���ݒ芮��
#-------------------------------------------------

#-------------------------------------------------
#�g�ђ[���ʂ̕���
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
#if (index($ENV{'HTTP_USER_AGENT'},"Opera") >= 0){$imode = 2;}	# �e�X�g�p

#-------------------------------------------------
#�A�C�R���摜�ݒ�
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
#�g�ђ[���̐ݒ�ύX
#-------------------------------------------------
if ($imode){
	if ($counter){$counter = 1;}
	$icon_mode = 0; #�A�C�R�����L�����Z��
	$p_log = $imodenum;
	$title_gif = "";
	$baseUrl = '';

	if ($imode == 1){$title_gif = $imode_title;}
	if ($imode == 2){$title_gif = $ezweb_title;}
	if ($imode == 4){$title_gif = $doti_title;}

	#J�X�J�C�̂�GET
	if ($imode == 5){
		if (index($ENV{'HTTP_USER_AGENT'},"J-PHONE/2.0") >= 0){
			$method = 'GET';
		}
		$title_gif = $jsky_title;
		$postonly = 0;
	}

}

#-------------------------------------------------
#���̓t�H�[���̐ݒ�
#-------------------------------------------------
if ($imode == 4) {
	# ASTEL�̏ꍇ
	$input_kanji = "astyle=\"hiragana\"";
	$input_alphabet = "astyle=\"alphabet\"";
	$input_numeric = "astyle=\"numeric\"";
}elsif ($imode == 5) {
	# J-PHONE�̏ꍇ
	$input_kanji = "mode=\"hiragana\"";
	$input_alphabet = "mode=\"alphabet\"";
	$input_numeric = "mode=\"numeric\"";
}else {
	# ���̑�
	$input_kanji ="istyle=1";
	$input_alphabet ="istyle=3";
	$input_numeric ="istyle=4";
}

#-------------------------------------------------
#  ���e���
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
  <td><b>�����O</b></td>
  <td><input type=text name=$name_key size=28 value="$nam" class=f></td>
</tr>
EOM
	if ($aikotoba) {
		print "<tr>\n  <td nowrap><b>�������t</b></td>\n  <td>",
		"<input type=text name=aikotoba size=10 value=\"$caikotoba\">",
		"<font color='#FF0000'>���K�{</font></td>\n</tr>";
		if ($caikotoba ne $aikotoba) { print "<tr>\n  <td nowrap colspan=2><b>$hint</b></td>\n</tr>\n"; }
	}
	print <<EOM;
<tr>
  <td><b>�d���[��</b></td><td>
    <input type=hidden name=mail size=28 value="$enaddress">
    <input type=text name=$mail_key size=28 value="$eml" class=f>
EOM
	if ($no_email eq '1') { print "<b style='color:#FF0000'>���[���A�h���X�͓��͋֎~</b>"; }
	elsif ($no_email eq '2') { print "���͂���ꍇ�ɂ͕K��<b style='color:#FF0000'>����S�p��</b>�����ĉ�����"; }
	print <<EOM;
</td>
</tr>
<tr>
  <td><b>�^�C�g��</b></td>
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
fcheck("mit value=���e����><input t","<inpu","t type=sub","ype=reset value=���Z�b�g>");
// -->
</script>
<noscript><br><b>Javascript��L���ɂ��Ă��������B</b><br><br></noscript>
EOM
			} else { print "<input type=submit value='���e����'><input type=reset value='���Z�b�g'>"; }
		} else { print "<BR>\n<B>�f���֒��ڃA�N�Z�X�����ꍇ�ɂ͓��e�ł��܂���B",
			"<A HREF=\"$homepage\">�g�b�v�y�[�W</A>������蒼���Ă��������B</B>\n"; }
	} else {
		if ($mode ne "edit" && $mode ne "admin") {
			print "    <input type=hidden name=mode value=\"$writevalue\">\n"; }
		print "<input type=submit value='���e����'><input type=reset value='���Z�b�g'>";
	}

	print <<EOM;
  </td>
</tr>
<tr>
  <td colspan=2>
    <b>���b�Z�[�W
EOM
	if ($comment_url) { print "�y���b�Z�[�W���̂t�q�k�͐擪�̂��𔲂��ď�������ŉ������B�z"; }
	print <<EOM;
</b><br>
    <textarea cols=56 rows=7 name=$comment_key wrap="soft" class=f>$com</textarea>
  </td>
</tr>
<tr>
  <td colspan=2>
EOM
	$f_c_d = int(rand(5E07)) + 11E08;
	if ($urlcheck) { print "  <b>���b�Z�[�W���ɂ͎Q�Ɛ�URL�Ɠ���URL���������܂Ȃ��ŉ�����</b>\n"; }
	print <<EOM;
  <input type=hidden name=$formcheck value="$f_c_d"></td>
</tr>
<tr>
  <td><b>�Q�Ɛ�</b></td>
  <td><input type=text size=52 name=$url_key value="$url" class=f></td>
</tr>
<!--//
<tr>
  <td><b>URL</b></td>
  <td><input type=text size=52 name=url2 value="" class=f></td>
</tr>
//-->
EOM

	# �Ǘ��҃A�C�R����z��ɕt��
	@ico1 = split(/\s+/, $ico1);
	@ico2 = split(/\s+/, $ico2);
	if ($my_icon) {
		push(@ico1,$my_gif);
		push(@ico2,"�Ǘ��җp");
	}
	if ($iconMode) {
		print "<tr><td><b>�C���[�W</b></td>
		<td><select name=icon class=f>\n";
		foreach(0 .. $#ico1) {
			if ($ico eq $ico1[$_]) {
				print "<option value=\"$_\" selected>$ico2[$_]\n";
			} else {
				print "<option value=\"$_\">$ico2[$_]\n";
			}
		}
		print "</select> &nbsp;\n";

		# �C���[�W�Q�Ƃ̃����N
		if ($ImageView == 1) {
			print "[<a href=\"javascript:ImageUp()\">�C���[�W�Q��</a>]";
		} else {
			print "[<a href=\"$script?mode=image\" target=\"_blank\">�C���[�W�Q��</a>]";
		}
		print "</td></tr>\n";
	}

	if ($pwd ne "??") {
		print "<tr><td><b>�Ï؃L�[</b></td>";
		print "<td><input type=password name=pwd size=8 maxlength=8 value=\"$pwd\" class=f>\n";
		print "(�p������8�����ȓ�)</td></tr>\n";
	}
	print "<tr><td><b>�����F</b></td><td>";

	# �F���
	@col = split(/\s+/, $color);
	if ($col eq "") { $col = 0; }
	foreach (0 .. $#col) {
		if ($col eq $col[$_] || $col eq $_) {
			print "<input type=radio name=color value=\"$_\" checked>";
			print "<font color=\"$col[$_]\">��</font>\n";
		} else {
			print "<input type=radio name=color value=\"$_\">";
			print "<font color=\"$col[$_]\">��</font>\n";
		}
	}

	print <<EOM;
</td></tr></table>
EOM
}

#-------------------------------------------------
#  �A�N�Z�X����
#-------------------------------------------------
sub axsCheck {
	# IP&�z�X�g�擾
	$host = $ENV{'REMOTE_HOST'};
	$addr = $ENV{'REMOTE_ADDR'};

	if ($gethostbyaddr && ($host eq "" || $host eq $addr)) {
		$host = gethostbyaddr(pack("C4", split(/\./, $addr)), 2);
	}

	# IP�`�F�b�N
	local($flg);
	foreach (@denyaddr) {
		s/\./\\\./g;
		s/\*/\.\*/g;

		if ($addr =~ /^$_/i) { $flg = 1; last; }
	}
	if ($flg) {
		&error("�A�N�Z�X��������Ă��܂���");

	# �z�X�g�`�F�b�N
	} elsif ($host) {

		foreach ( split(/\s+/, $deny_host) ) {
			s/\./\\\./g;
			s/\*/\.\*/g;

			if ($host =~ /$_$/i) { $flg = 1; last; }
		}
		if ($flg) {
			&error("�A�N�Z�X��������Ă��܂���");
		}
	}
	if ($host eq "") { $host = $addr; }
	if (-e "$denyfile") { &spambot; }
	if ($type eq "p") {
		if ($referercheck==2 && !$ENV{'HTTP_REFERER'}) { &access_error; }
	}
}

#-------------------------------------------------
#  �f�R�[�h����
#-------------------------------------------------
sub decode {
	local($buf,$key,$val);
	undef(%in);

	if ($ENV{'REQUEST_METHOD'} eq "POST") {
		$post_flag=1;
		if ($ENV{'CONTENT_LENGTH'} > $maxData) {
			&error("���e�ʂ��傫�����܂�");
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

		# S-JIS�R�[�h�ϊ�
		&jcode'convert(*val, "sjis", "", "z");

		# �G�X�P�[�v
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
#  �G���[����
#-------------------------------------------------
sub error {
	# ���b�N���ł���Ή���
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
<input type=button value="�O��ʂɖ߂�" onClick="history.back()">
</form>
</div>
</body>
</html>
EOM
	exit;
}

#-------------------------------------------------
#  HTML�w�b�_
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
	# JavaScript�w�b�_

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
#  ���b�N����
#-------------------------------------------------
sub lock {
	# ���g���C��
	local($retry)=5;

	# �Â����b�N�͍폜����
	if (-e $lockfile) {
		local($mtime) = (stat($lockfile))[9];
		if ($mtime < time - 30) { &unlock; }
	}

	# symlink�֐������b�N
	if ($lockkey == 1) {
		while (!symlink(".", $lockfile)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}

	# mkdir�֐������b�N
	} elsif ($lockkey == 2) {
		while (!mkdir($lockfile, 0755)) {
			if (--$retry <= 0) { &error('LOCK is BUSY'); }
			sleep(1);
		}
	}
	$lockflag=1;
}

#-------------------------------------------------
#  ���b�N����
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
#  �N�b�L�[���s
#-------------------------------------------------
sub set_cookie {
	local(@cook) = @_;
	local($gmt, $cook, @t, @m, @w);

	@t = gmtime(time + 60*24*60*60);
	@m = ('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
	@w = ('Sun','Mon','Tue','Wed','Thu','Fri','Sat');

	# ���ەW�������`
	$gmt = sprintf("%s, %02d-%s-%04d %02d:%02d:%02d GMT",
			$w[$t[6]], $t[3], $m[$t[4]], $t[5]+1900, $t[2], $t[1], $t[0]);

	# �ۑ��f�[�^��URL�G���R�[�h
	foreach (@cook) {
		s/(\W)/sprintf("%%%02X", unpack("C", $1))/eg;
		$cook .= "$_<>";
	}

	# �i�[
	print "Set-Cookie: YY_BOARD=$cook; expires=$gmt\n";
}

#-------------------------------------------------
#  �N�b�L�[�擾
#-------------------------------------------------
sub get_cookie {
	local($key, $val, *cook);

	# �N�b�L�[�擾
	$cook = $ENV{'HTTP_COOKIE'};

	# �Y��ID�����o��
	foreach ( split(/;/, $cook) ) {
		($key, $val) = split(/=/);
		$key =~ s/\s//g;
		$cook{$key} = $val;
	}

	# �f�[�^��URL�f�R�[�h���ĕ���
	@cook=();
	foreach ( split(/<>/, $cook{'YY_BOARD'}) ) {
		s/%([0-9A-Fa-f][0-9A-Fa-f])/pack("H2", $1)/eg;

		push(@cook,$_);
	}
	return (@cook);
}

#-------------------------------------------------
#  �ړ��{�^��
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
		# ���y�[�W
		if ($page == $y) {

			print "| <b style='color:red' class=n>$x</b>\n";

		# �ؑփy�[�W
		} elsif ($x >= $start && $x <= $end) {

			print "| <a href=\"$link$y&bl=$in{'bl'}\" class=n>$x</a>\n";

		# �O�u���b�N
		} elsif ($x == $start-1) {

			$bk_bl = $in{'bl'}-1;
			print "| <a href=\"$link$y&bl=$bk_bl\">��</a>\n";

		# ���u���b�N
		} elsif ($x == $end+1) {

			$fw_bl = $in{'bl'}+1;
			print "| <a href=\"$link$y&bl=$fw_bl\">��</a>\n";

		}

		$x++;
		$y += $view;
		$i -= $view;
	}

	print "|\n";
}

#-------------------------------------------------
#  ��������
#-------------------------------------------------
sub search {
	local($file,$word,$view,$cond) = @_;
	local($i,$f,$top,$wd,$next,$back,@wd);

	# �L�[���[�h��z��
	$word =~ s/\x81\x40/ /g;
	@wd = split(/\s+/, $word);

	# �t�@�C���W�J
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

		# �q�b�g�����ꍇ
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
			# ���ʂ�\��
			print "<dt><hr>[<b>$no</b>] <b style=\"color:$subCol\">$sub</b> ";
			print "���e�ҁF<b>$nam</b> ���e���F$dat $url<br><br>\n";
			print "<dd style=\"color:$col\">$com\n";
		}
	}
	close(IN);

	print <<EOM;
<dt><hr>
�������ʁF<b>$i</b>��
</dl>
EOM
	$next = $page + $view;
	$back = $page - $view;
	return ($i, $next, $back);
}

#-------------------------------------------------
#  URL�G���R�[�h
#-------------------------------------------------
sub url_enc {
	local($_) = @_;

	s/(\W)/'%' . unpack('H2', $1)/eg;
	s/\s/+/g;
	$_;
}

#-------------------------------------------------
#  �t�H�[���`�F�b�N�f�[�^������
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
#  �t�H�[���`�F�b�N�f�[�^����
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
#  �A�h���X�Í���
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
#  �A�N�Z�X�����`�F�b�N
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
#  �A�N�Z�X�g���b�v�G���[
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
