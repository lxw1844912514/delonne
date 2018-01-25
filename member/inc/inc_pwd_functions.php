<?php   if(!defined('DEDEMEMBER')) exit("dedecms");
/**
 * 密码函数
 * 
 * @version        $Id: inc_pwd_functions.php 1 15:18 2010年7月9日Z tianya $
 * @package        DedeCMS.Member
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */

/**
 *  验证码生成函数
 *
 * @param     int  $length  需要生成的长度
 * @param     int  $numeric  是否为数字
 * @return    string
 */
function random($length, $numeric = 0)
{
    PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
    if($numeric)
    {
        $hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
    }
    else
    {
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        for($i = 0; $i < $length; $i++)
        {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}

/**
 *  邮件发送函数
 *
 * @param     string  $email  E-mail地址
 * @param     string  $mailtitle  E-mail标题
 * @param     string  $mailbody  E-mail内容
 * @param     string  $headers 头信息
 * @return    void
 */
// function sendmail($email, $mailtitle, $mailbody, $headers)
// {
                
//     global $cfg_sendmail_bysmtp, $cfg_smtp_server, $cfg_smtp_port, $cfg_smtp_usermail, $cfg_smtp_user, $cfg_smtp_password, $cfg_adminemail;
//     if($cfg_sendmail_bysmtp == 'Y' && !empty($cfg_smtp_server))
//     {
//         $mailtype = 'TXT';
//         require_once(DEDEINC.'/mail.class.php');
//         $smtp = new smtp($cfg_smtp_server,$cfg_smtp_port,true,$cfg_smtp_usermail,$cfg_smtp_password);

//         // var_dump($smtp);exit;

//         $smtp->debug = true;  //调试信息

//         $smtp->sendmail($email,$cfg_webname,$cfg_smtp_usermail, $mailtitle, $mailbody, $mailtype);
//     } else {
//         @mail($email, $mailtitle, $mailbody, $headers);
//     }
// }


    //邮件发送函数
    function sendmail($email, $mailtitle, $mailbody)
    {
        global $cfg_sendmail_bysmtp, $cfg_smtp_server, $cfg_smtp_port, $cfg_smtp_usermail, $cfg_smtp_user, $cfg_smtp_password, $cfg_adminemail,$cfg_webname;
        if($cfg_sendmail_bysmtp == 'Y' && !empty($cfg_smtp_server))
        {
            // var_dump($cfg_smtp_server);exit;
            $mailtype = 'HTML';
            require_once(DEDEINC.'/mail.class.php');
            $smtp = new smtp($cfg_smtp_server,$cfg_smtp_port,true,$cfg_smtp_usermail,$cfg_smtp_password);
            $smtp->debug = false;
            // var_dump($smtp->smtp_sockopen($cfg_smtp_server));exit;
            if(!$smtp->smtp_sockopen($cfg_smtp_server)){
              // ShowMsg('邮件发送失败,请联系管理员','-1');
                echo "<script>alert('邮件发送失败,请联系管理员 !');window.history.go(-1);</script>";
            exit();
            }
            $smtp->sendmail($email,$cfg_webname,$cfg_smtp_usermail, $mailtitle, $mailbody, $mailtype);
        }else{
            @mail($email, $mailtitle, $mailbody, $headers);
        }
    }
/**
 *  发送邮件；type为INSERT新建验证码，UPDATE修改验证码；
 *
 * @param     int  $mid  会员ID
 * @param     int  $userid  用户ID
 * @param     string  $mailto  发送到
 * @param     string  $type  类型
 * @param     string  $send  发送到
 * @return    string
 */
function newmail($mid, $telephone, $mailto, $type, $send)
{
    global $db,$cfg_adminemail,$cfg_webname,$cfg_basehost,$cfg_memberurl;
    $mailtime = time();
    $randval = random(8);

    // var_dump($randval);exit;
    $mailtitle = $cfg_webname.":密码修改";
    $mailto = $mailto;
    $headers = "From: ".$cfg_adminemail."\r\nReply-To: $cfg_adminemail";

     
     $mailbody = "亲爱的".$telephone."：\r\n您好！感谢您使用".$cfg_webname."网。
                    \r\n这是".$cfg_webname."发送的找回密码邮件，如果您确定要重置密码，请点击以下链接：
                    \r\n".$cfg_basehost.$cfg_memberurl."/resetpassword.php?dopost=getpasswd&id=".$mid.
                    "\r\n如无法点击，请将链接拷贝到浏览器地址栏中直接访问。";
    if($type == 'INSERT')
    {   
        // echo 'INSERT';
        $key = md5($randval);
        $sql = "INSERT INTO `#@__pwd_tmp` (`mid` ,`membername` ,`pwd` ,`mailtime`)VALUES ('$mid', '$telephone',  '$key', '$mailtime');";
        if($db->ExecuteNoneQuery($sql))
        {
            if($send == 'Y')
            {
                sendmail($mailto,$mailtitle,$mailbody,$headers);
                echo "<script>alert('密码修改连接已经发送到".$mailto."邮箱，请注意查收 !');window.location.href='index.php';</script>";
            } else if ($send == 'N')
            {
                return ShowMsg('稍后跳转到修改页', $cfg_basehost.$cfg_memberurl."/resetpassword.php?dopost=getpasswd&amp;id=".$mid."&amp;key=".$randval);
            }
        }
        else
        {
            return ShowMsg('对不起修改失败，请联系管理员', 'login.php');
        }
    }

    /*******UPDATE修改验证码**debug**/ 
    elseif($type == 'UPDATE')
    {
        // echo 'UPDATE';
        $key = md5($randval);
        $sql = "UPDATE `#@__pwd_tmp` SET `pwd` = '$key',mailtime = '$mailtime'  WHERE `mid` ='$mid';";

        // var_dump($db->ExecuteNoneQuery($sql));exit;

        if($db->ExecuteNoneQuery($sql))
        {   
            if($send == 'Y')
            {
                sendmail($mailto,$mailtitle,$mailbody,$headers);
                // ShowMsg('EMAIL修改验证码已经发送到原来的邮箱请查收', 'login.php','5000');
                echo "<script>alert('密码修改连接已经发送到".$mailto."邮箱，请注意查收 !');window.location.href='index.php';</script>";
            }
            elseif($send == 'N')
            {
                return ShowMsg('稍后跳转到修改页', $cfg_basehost.$cfg_memberurl."/resetpassword.php?dopost=getpasswd&amp;id=".$mid."&amp;key=".$randval);
            }
        }
        else
        {
            ShowMsg('对不起修改失败，请与管理员联系', 'login.php');
        }
    }
}

/**
 *  查询会员信息mail用户输入邮箱地址；userid用户名
 *
 * @param     string  $mail  邮件
 * @param     string  $userid  用户ID
 * @return    string
 */
        /*********($mail, $mail)****lxw823* */ 
function member($mail, $telephone)
{
    global $db;
    // $sql = "SELECT mid,email,safequestion FROM #@__member WHERE email='$mail' AND userid = '$userid'";
     $sql_mail = "UPDATE `#@__member` SET `email` = '$mail'  WHERE `telephone` ='$telephone';"; 
     $db->ExecuteNoneQuery($sql_mail);//将邮箱填入数据库

    // var_dump($db->ExecuteNoneQuery($sql_mail) );
    $sql = "SELECT mid,email FROM #@__member WHERE telephone = '$telephone'";

    $row = $db->GetOne($sql);
     // var_dump($row);
    if(!is_array($row)) 
        // return ShowMsg("对不起，用户ID输入错误！","-1");
         echo "<script>alert('对不起，您输入的手机账号不存在 !');window.location.href='resetpassword.php';</script>";
    else 
        return $row;
}

/**
 *  查询是否发送过验证码
 *
 * @param     string  $mid  会员ID
 * @param     string  $userid  用户名称
 * @param     string  $mailto  发送邮件地址
 * @param     string  $send  为Y发送邮件,为N不发送邮件默认为Y
 * @return    string
 */
function sn($mid,$telephone,$mailto, $send = 'Y')
{
    global $db;
    $tptim= (60*10);
    // $tptim= (1);
    $dtime = time();
    $sql = "SELECT * FROM #@__pwd_tmp WHERE mid = '$mid'";
    $row = $db->GetOne($sql);
    // var_dump($row);exit;
    if(!is_array($row))
    {
        //发送新邮件；
        newmail($mid,$telephone,$mailto,'INSERT',$send);
    }
    //10分钟后可以再次发送新验证码；
    elseif($dtime - $tptim > $row['mailtime'])
    {
        newmail($mid,$telephone,$mailto,'UPDATE',$send);
    }
    //重新发送新的验证码确认邮件；
    else
    {
        // return ShowMsg('对不起，请10分钟后再重新申请', 'login.php');
         echo "<script>alert('对不起，请10分钟后再重新申请 !');window.location.href='login.php';</script>";
    }
}