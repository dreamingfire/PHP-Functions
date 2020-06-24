<?php

// Web页面上高亮显示SQL代码
function SQL_DEBUG( $query )
{
    if( $query == '' ) return 0;

    global $SQL_INT;
    if( !isset($SQL_INT) ) $SQL_INT = 0;

    //[dv] this has to come first or you will have goofy results later.
    $query = preg_replace("/['\"]([^'\"]*)['\"]/i", "'<FONT COLOR='#FF6600'>$1</FONT>'", $query, -1);

    $query = str_ireplace(
                            array (
                                    '*',
                                    'SELECT ',
                                    'UPDATE ',
                                    'DELETE ',
                                    'INSERT ',
                                    'INTO',
                                    'VALUES',
                                    'FROM',
                                    'LEFT',
                                    'JOIN',
                                    'WHERE',
                                    'LIMIT',
                                    'ORDER BY',
                                    'AND',
                                    'OR ', //[dv] note the space. otherwise you match to 'COLOR' ;-)
                                    'DESC',
                                    'ASC',
                                    'ON '
                                  ),
                            array (
                                    "<FONT COLOR='#FF6600'><B>*</B></FONT>",
                                    "<FONT COLOR='#00AA00'><B>SELECT</B> </FONT>",
                                    "<FONT COLOR='#00AA00'><B>UPDATE</B> </FONT>",
                                    "<FONT COLOR='#00AA00'><B>DELETE</B> </FONT>",
                                    "<FONT COLOR='#00AA00'><B>INSERT</B> </FONT>",
                                    "<FONT COLOR='#00AA00'><B>INTO</B></FONT>",
                                    "<FONT COLOR='#00AA00'><B>VALUES</B></FONT>",
                                    "<FONT COLOR='#00AA00'><B>FROM</B></FONT>",
                                    "<FONT COLOR='#00CC00'><B>LEFT</B></FONT>",
                                    "<FONT COLOR='#00CC00'><B>JOIN</B></FONT>",
                                    "<FONT COLOR='#00AA00'><B>WHERE</B></FONT>",
                                    "<FONT COLOR='#AA0000'><B>LIMIT</B></FONT>",
                                    "<FONT COLOR='#00AA00'><B>ORDER BY</B></FONT>",
                                    "<FONT COLOR='#0000AA'><B>AND</B></FONT>",
                                    "<FONT COLOR='#0000AA'><B>OR</B> </FONT>",
                                    "<FONT COLOR='#0000AA'><B>DESC</B></FONT>",
                                    "<FONT COLOR='#0000AA'><B>ASC</B></FONT>",
                                    "<FONT COLOR='#00DD00'><B>ON</B> </FONT>"
                                  ),
                            $query
                          );

    echo "<FONT COLOR='#0000FF'><B>SQL[".$SQL_INT."]:</B> ".$query."<FONT COLOR='#FF0000'>;</FONT></FONT><BR>\n";

    $SQL_INT++;

} //SQL_DEBUG


// 自定义不定参数函数的表示方式
function userFunc(...$name) {
    echo __FUNCTION__ . ", get " . implode(', ', $name) . "<br/>";
}


// 判断树是否为平衡二叉树，主要是匿名函数的递归传递方式
function isBalanced($root) {
        if (empty($root)) {
            return true;
        }
        $maxDiff = 0;
        $deep = function ($root) use (&$deep, &$maxDiff) {
            $ld = empty($root->left) ? 0 : $deep($root->left);
            $rd = empty($root->right) ? 0 : $deep($root->right);
            $maxDiff = max(abs($ld - $rd), $maxDiff);
            return max($ld, $rd) + 1;
        };
        $deep($root);
        return $maxDiff <= 1;
    }


// 使用socket发送邮件，echo 部分实际使用时可改为日志的方式
function sendmail($toemail, $subject, $message) {
    $config = array (
        'mailsend' => 2,
        'maildelimiter' => 1,
        'mailusername' => 1,
        'server' => 'your stmp server',
        'port' => 25,
        'mail_type' => 1,
        'auth' => 1,
        'from' => 'your email address',
        'auth_username' => 'username, is alway your email address',
        'auth_password' => 'your password for username',
    );

    $charset   = 'utf-8';
    $mail      = $config;
    $from      = $config['from'];
    $mail_type = $config['mail_type'];

    //mail 发送模式
    if($mail_type==0) {
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset='.$charset.'' . "\r\n";
        $headers .= 'From: <'.$from.'>' . "\r\n";
        mail($toemail, $subject, $message, $headers);
        return true;
    }
    //邮件头的分隔符
    $maildelimiter = $mail['maildelimiter'] == 1 ? "\r\n" : ($mail['maildelimiter'] == 2 ? "\r" : "\n");
    //收件人地址中包含用户名
    $mailusername = isset($mail['mailusername']) ? $mail['mailusername'] : 1;
    //端口
    $mail['port'] = $mail['port'] ? $mail['port'] : 25;
    $mail['mailsend'] = $mail['mailsend'] ? $mail['mailsend'] : 1;
    
    //发信者
    $email_from = $from == '' ? '=?'.$charset.'?B?'."?= <".$from.">" : (preg_match('/^(.+?) \<(.+?)\>$/',$from, $mats) ? '=?'.$charset.'?B?'.base64_encode($mats[1])."?= <$mats[2]>" : $from);
    
    $email_to = preg_match('/^(.+?) \<(.+?)\>$/',$toemail, $mats) ? ($mailusername ? '=?'.$charset.'?B?'.base64_encode($mats[1])."?= <$mats[2]>" : $mats[2]) : $toemail;;
    
    $email_subject = '=?'.$charset.'?B?'.base64_encode(preg_replace("/[\r|\n]/", '', $subject)).'?=';
    $email_message = chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r", "\n", str_replace("\r\n", "\n", str_replace("\n\r", "\r", $message))))));
    
    $headers = "From: $email_from{$maildelimiter}X-Priority: 3{$maildelimiter}X-Mailer: PHPCMS-V9 {$maildelimiter}MIME-Version: 1.0{$maildelimiter}Content-type: text/html; charset=".$charset."{$maildelimiter}Content-Transfer-Encoding: base64{$maildelimiter}";
        
    if(!$fp = fsockopen($mail['server'], $mail['port'], $errno, $errstr, 30)) {
        echo 'SMTP: ' . "($mail[server]:$mail[port]) CONNECT - Unable to connect to the SMTP server";
        return false;
    }
    stream_set_blocking($fp, true);

    $lastmessage = fgets($fp, 512);
    if(substr($lastmessage, 0, 3) != '220') {
        echo 'SMTP: ' . "$mail[server]:$mail[port] CONNECT - $lastmessage";
        return false;
    }

    fputs($fp, ($mail['auth'] ? 'EHLO' : 'HELO')." phpcms\r\n");
    $lastmessage = fgets($fp, 512);
    if(substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
        echo 'SMTP: ' . "($mail[server]:$mail[port]) HELO/EHLO - $lastmessage";
        return false;
    }

    while(1) {
        if(substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
            break;
        }
        $lastmessage = fgets($fp, 512);
    }

    if($mail['auth']) {
        fputs($fp, "AUTH LOGIN\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 334) {
            echo 'SMTP: ' . "($mail[server]:$mail[port]) AUTH LOGIN - $lastmessage";
            return false;
        }

        fputs($fp, base64_encode($mail['auth_username'])."\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 334) {
            echo 'SMTP: ' . "($mail[server]:$mail[port]) USERNAME - $lastmessage";
            return false;
        }

        fputs($fp, base64_encode($mail['auth_password'])."\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 235) {
            echo 'SMTP: ' . "($mail[server]:$mail[port]) PASSWORD - $lastmessage";
            return false;
        }

        $email_from = $mail['from'];
    }

    fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
    $lastmessage = fgets($fp, 512);
    if(substr($lastmessage, 0, 3) != 250) {
        fputs($fp, "MAIL FROM: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from).">\r\n");
        $lastmessage = fgets($fp, 512);
        if(substr($lastmessage, 0, 3) != 250) {
            echo 'SMTP: ' . "($mail[server]:$mail[port]) MAIL FROM - $lastmessage";
            return false;
        }
    }

    fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail).">\r\n");
    $lastmessage = fgets($fp, 512);
    if(substr($lastmessage, 0, 3) != 250) {
        fputs($fp, "RCPT TO: <".preg_replace("/.*\<(.+?)\>.*/", "\\1", $toemail).">\r\n");
        $lastmessage = fgets($fp, 512);
        echo 'SMTP: ' . "($mail[server]:$mail[port]) RCPT TO - $lastmessage";
        return false;
    }

    fputs($fp, "DATA\r\n");
    $lastmessage = fgets($fp, 512);
    if(substr($lastmessage, 0, 3) != 354) {
        echo 'SMTP: ' . "($mail[server]:$mail[port]) DATA - $lastmessage";
        return false;
    }

    $headers .= 'Message-ID: <'.gmdate('YmdHs').'.'.substr(md5($email_message.microtime()), 0, 6).rand(100000, 999999).'@'.$_SERVER['HTTP_HOST'].">{$maildelimiter}";

    fputs($fp, "Date: ".gmdate('r')."\r\n");
    fputs($fp, "To: ".$email_to."\r\n");
    fputs($fp, "Subject: ".$email_subject."\r\n");
    fputs($fp, $headers."\r\n");
    fputs($fp, "\r\n\r\n");
    fputs($fp, "$email_message\r\n.\r\n");
    $lastmessage = fgets($fp, 512);
    if(substr($lastmessage, 0, 3) != 250) {
        echo 'SMTP', "($mail[server]:$mail[port]) END - $lastmessage";
    }
    fputs($fp, "QUIT\r\n");
    return true;
}
