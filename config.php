<?php
/**
 * File path which held the sync information
 */ 
define('HISTORY_FILE_PATH', APP_ROOT.'/res/main/history.json');

/**
 * Folder path where are stored sync file
 */ 
define('FOLDER_TO_BE_VERSIONNED_FILE_PATH', APP_ROOT.'/res/main/content');

/**
 * Avoid hackers to find any zip archive file on your web server.
 * YOU MUST DENY FOLDER LISTING INSIDE YOUR WEB SERVER CONFIGURATION WHERE IS INSTALLED HOURGLASS
 * Consider changing the message authentification code on your first installation.
 */  
define('ARCHIVE_ZIPNAME_AUTHENTIFICATION_CODE', 'y%}FcROObOQ,-7ezj7]oe34MS#Fc.aJYfG<ublv}OOa]=7ai.Vs1#BcQJdqcRE.KF2O#f<p;G>o|lya3BWO%6r!q<{RQhF9x:pa3gq<qJ#88JG))n1]6[TQsoj%AMw.TXhs{%*iHMj4vd6r+A%cBIn>A3dMlB-UwIWh):VM+#,t>,gTdYQu%*VW|tllfH>jPpG!8f%!Ylmf[ed0v&#d#U<4|z0!X7Hh(!|0bZ;]XFN_U]Ob]zihWAI8Jd*4S]*7z0lFvBKd%{jOWiFg,gW-XEEl6JBgj,Rg8ez90ci3GErLxSfs[NRw=fux%jy-xu8BBFgVjrP,-=y|3-)xX3-[<cmW|2T)4bvtOX18rEOLPq&W7:7d:e,if<2d_60JWbZA7S0-BGB}c_1i.VyKY((9PT7aSv1(G79zp,e:gTAVG[6>m3FI]Lh%(ua1k*=C(L8z*4Q>]YGrq(xDbGfj5B{A.<v#P1yeOw-gcEvMg_+AG0Vqj}sgM1cfNcqA)4fIavLb+'); // Change with yours, it's an example, generated with KeePass Tool !

/**
 * Memory and execution
 */
ini_set('memory_limit', '1024M'); // Tweak depending your server memory, usually 25% of your server memory
set_time_limit(600); // Default 600 seconds (= 10 minutes)

/**
 * Defines testing purspose
 */
define('HISTORY_FILE_PATH_TEST', APP_ROOT.'/res/test/history.json');
define('FOLDER_TO_BE_VERSIONNED_FILE_PATH_TEST', APP_ROOT.'/res/test/content');
define('DATA_FOLDER_TEST', APP_ROOT.'/res/test/data');

