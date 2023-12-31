<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | PHP version 4/5                                                      |
// +----------------------------------------------------------------------+
// | This source file is a part of iScripts eSwap                         |
// +----------------------------------------------------------------------+
// | Authors: Programmer<simi@armia.com>        		                  |
// +----------------------------------------------------------------------+
// | Copyrights Armia Systems, Inc and iScripts.com © 2005                |
// | All rights reserved                                                  |
// +----------------------------------------------------------------------+
// | This script may not be distributed, sold, given away for free to     |
// | third party, or used as a part of any internet services such as      |
// | webdesign etc.                                                       |
// +----------------------------------------------------------------------+
include ("./includes/config.php");
include ("./includes/session.php");
include ("./includes/functions.php");
include("./languages/" . $_SESSION['lang_folder'] . "/user.php"); //language file
include ("./includes/session_check.php");
include_once('./includes/gpc_map.php');

$flag_to_continue = false;

$transstatus = $_REQUEST['transStatus'];

if (isset($_REQUEST['transStatus']) && $_REQUEST['transStatus'] == 'Y') {
    $flag_to_continue = true;
}//end if


$var_id = "";
$var_new_id = "";
$message = "";
$flag = false;

if ($flag_to_continue == true) {
    if (DisplayLookUp('worldpaydemo') == "YES") {
        $txnid = 'TEST-' . time();
    }//end if
    else {
        $txnid = time();
    }//end if

    $var_txnid = $txnid;


    $sqltxn = @mysqli_query($conn, "Select vTxnId from " . TABLEPREFIX . "successtransactionpayments where vTxnId ='" . $txnid . "' AND vMethod='wp'") or die(mysqli_error($conn));

    if (@mysqli_num_rows($sqltxn) > 0) {
        $message = ERROR_COMMUNICATION_ERROR_WITH_PAYMENT_SERVER;
        $flag = true;
    }//end if
    else {
        $var_date = date('m/d/Y');

        //select value from succes
        $sqlSuccess = mysqli_query($conn, "select nProdId,nAmount,nPoints from " . TABLEPREFIX . "successfee where nSId='" . $_SESSION['sess_success_fee_id'] . "'") or die(mysqli_error($conn));
        if (mysqli_num_rows($sqlSuccess) > 0) {
            $passProdId = mysqli_result($sqlSuccess, 0, 'nProdId');
            $passAmount = mysqli_result($sqlSuccess, 0, 'nAmount');
            $passPoints = mysqli_result($sqlSuccess, 0, 'nPoints');
        }//end if
        //update status in success fee table
        mysqli_query($conn, "UPDATE " . TABLEPREFIX . "successfee SET vStatus='A' WHERE nSId='" . $_SESSION['sess_success_fee_id'] . "'") or die(mysqli_error($conn));

//        $sql = "Update " . TABLEPREFIX . "users set nAccount = nAccount +  " . $passAmount . "  where  nUserId='" . $_SESSION["guserid"] . "'";
//        mysqli_query($conn, $sql) or die(mysqli_error($conn));

        //checking alredy exits
        $chkPoint = fetchSingleValue(select_rows(TABLEPREFIX . 'usercredits', 'nPoints', "WHERE nUserId='" . $_SESSION["guserid"] . "'"), 'nPoints');
        if (trim($chkPoint) != '') {
            //update points to user credit
            mysqli_query($conn, "UPDATE " . TABLEPREFIX . "usercredits set nPoints=nPoints+" . $passPoints . " WHERE
												nUserId='" . $_SESSION["guserid"] . "'") or die(mysqli_error($conn));
        }//end if
        else {
            //add points to user credit
            mysqli_query($conn, "INSERT INTO " . TABLEPREFIX . "usercredits (nPoints,nUserId) VALUES ('" . $passPoints . "','" . $_SESSION["guserid"] . "')") or die(mysqli_error($conn));
        }//end else
        //add into user table
        mysqli_query($conn, "INSERT INTO " . TABLEPREFIX . "successtransactionpayments (nUserId,nAmount,nProdId,vTxnId,vMethod,dDate,vStatus,nSId) VALUES
								('" . $_SESSION["guserid"] . "','" . round(DisplayLookUp('SuccessFee'), 2) . "','" . $passProdId . "','" . $txnid . "',
								'wp',now(),'A','" . $_SESSION['sess_success_fee_id'] . "')") or die(mysqli_error($conn));


        /*
        * Fetch user language details
        */

        $lanSql = "SELECT lang_name,folder_name FROM ".TABLEPREFIX."lang WHERE lang_id = '".$_SESSION["lang_id"]."'";
        $langRs = mysqli_query($conn, $lanSql) or die(mysqli_error($conn));
        $langRw = mysqli_fetch_array($langRs);

        /*
        * Fetch email contents from content table
        */
        $mailSql = "SELECT L.content,L.content_title
          FROM ".TABLEPREFIX."content C
          JOIN ".TABLEPREFIX."content_lang L
            ON C.content_id = L.content_id
           AND C.content_name = 'SuccessFeeMailToUser'
           AND C.content_type = 'email'
           AND L.lang_id = '".$_SESSION["lang_id"]."'";

        $mailRs  = mysqli_query($conn, $mailSql) or die(mysqli_error($conn));
        $mailRw  = mysqli_fetch_array($mailRs);

        $mainTextShow   = $mailRw['content'];

        $arrTSearch     = array("{SITE_NAME}","{SITE_URL}","{SITE_EMAIL}","{point_val}","{point_name}","{payment_type}","{date}","{sess_PointAmount}","{guserFName}","{purchase_details}");
        $arrTReplace    = array(SITE_NAME,SITE_URL,SITE_EMAIL,$_SESSION['sess_PointSelected'],POINT_NAME,"World Pay",date('m/d/Y'),$_SESSION["sess_PointAmount"],$_SESSION["guserFName"],$passPoints);
        $mainTextShow   = str_replace($arrTSearch,$arrTReplace,$mainTextShow);

        $mailcontent1   = $mainTextShow;

        $subject    = $mailRw['content_title'];
        $subject    = str_replace("{POINT_NAME}", POINT_NAME, $subject);

        $StyleContent = MailStyle($sitestyle, SITE_URL);     
        
        $EMail = $_SESSION["guseremail"];

        //readf file n replace
        $arrSearch = array("{TITLE}", "{STYLE}", "{SITE-URL}", "{NAME}", "{CONTENT}", "{SITE-LOGO}", "{DATE}", "{SITE-NAME}", "{HEAD}");
        $arrReplace = array(SITE_TITLE, $StyleContent, SITE_URL, 'Member', $mailcontent1, $logourl, date('m/d/Y'), SITE_NAME, $subject);
        $msgBody    = file_get_contents('languages/'.$langRw["folder_name"].'/mail.html');
        $msgBody = str_replace($arrSearch, $arrReplace, $msgBody);
        send_mail($EMail, $subject, $msgBody, SITE_EMAIL, 'Admin');


        //mail sent to admin
        $var_admin_email = SITE_NAME;

        if (DisplayLookUp('4') != '') {
            $var_admin_email = DisplayLookUp('4');
        }//end if

        /*
        * Fetch email contents from content table
        */
        $mailSql = "SELECT L.content,L.content_title
          FROM ".TABLEPREFIX."content C
          JOIN ".TABLEPREFIX."content_lang L
            ON C.content_id = L.content_id
           AND C.content_name = 'SuccessFeeMailMailToAdmin'
           AND C.content_type = 'email'
           AND L.lang_id = '".$_SESSION["lang_id"]."'";

        $mailRs  = mysqli_query($conn, $mailSql) or die(mysqli_error($conn));
        $mailRw  = mysqli_fetch_array($mailRs);

        $mainTextShow   = $mailRw['content'];

        $arrTSearch     = array("{SITE_NAME}","{SITE_URL}","{SITE_EMAIL}","{point_val}","{point_name}","{payment_type}","{date}","{sess_PointAmount}","{guserFName}");
        $arrTReplace    = array(SITE_NAME,SITE_URL,SITE_EMAIL,$_SESSION['sess_PointSelected'],POINT_NAME,"World Pay",date('m/d/Y'),$_SESSION["sess_PointAmount"],$_SESSION["guserFName"]);
        $mainTextShow   = str_replace($arrTSearch,$arrTReplace,$mainTextShow);

        $mailcontent1   = $mainTextShow;

        $subject    = $mailRw['content_title'];
        $subject    = str_replace("{POINT_NAME}", POINT_NAME, $subject);

        $StyleContent = MailStyle($sitestyle, SITE_URL);
        $EMail = $var_admin_email;


        $arrSearch = array("{TITLE}", "{STYLE}", "{SITE-URL}", "{NAME}", "{CONTENT}", "{SITE-LOGO}", "{DATE}", "{SITE-NAME}", "{HEAD}");
        $arrReplace = array(SITE_TITLE, $StyleContent, SITE_URL, 'Admin', $mailcontent1, $logourl, date('m/d/Y'), SITE_NAME, $subject);
        $msgBody    = file_get_contents('languages/'.$langRw["folder_name"].'/mail.html');
        $msgBody = str_replace($arrSearch, $arrReplace, $msgBody);
        send_mail($EMail, $subject, $msgBody, SITE_EMAIL, 'Admin');

        $flag = false;

        $message = MESSAGE_TRANSACTION_COMPLETED_MAIL_SENT_TO_YOU;
        //$message .="<br>&nbsp;<br>You may visit the \"" . POINT_NAME . "\" to view details of this transaction.";

        //clear sessions
        $_SESSION['sess_success_fee_id'] = "";
    }//end else
}//end if

include_once('./includes/title.php');
?>
<body onLoad="timersOne();">
    <?php include_once('./includes/top_header.php'); ?>
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="headerbg"><?php require_once("./includes/header.php"); ?>
                <?php require_once("menu.php"); ?>
                <table width="100%"  border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="10%" height="688" valign="top"><?php include_once ("./includes/usermenu.php"); ?>
                                        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td id="leftcoloumnbtm"></td>
                                            </tr>
                                        </table></td>
                                    <td width="74%" valign="top">
                                        <table width="100%"  border="0" cellspacing="0" cellpadding="2">
                                            <tr>
                                                <td class="link3">&nbsp;</td>
                                            </tr>
                                        </table>
                                        <table width="100%"  border="0" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td class="heading" align="left"><?php echo HEADING_PAYMENT_STATUS; ?></td>
                                            </tr>
                                        </table>
                                        <table width="100%"  border="0" cellspacing="0" cellpadding="10">
                                            <tr>
                                                <td align="left" valign="top"><?php include('./includes/account_menu.php'); ?>
                                                    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td bgcolor="#EEEEEE"><table width="100%"  border="0" cellspacing="1" cellpadding="4" class="maintext2">
                                                                    <?php
                                                                    if (isset($message) && trim($message) != '') {
                                                                        ?>
                                                                        <tr bgcolor="#FFFFFF">
                                                                            <td width="100%" align="center" class="<?php if($flag==true){ ?>warning<?php }else{?>success<?php } ?>"><?php echo $message; ?></td>
                                                                        </tr>
                                                                        <?php
                                                                    }//end if
                                                                    else {
                                                                        ?>
                                                                        <?php
                                                                    }//end else
                                                                    ?>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table></td>
                                            </tr>
                                        </table>
										<?php include('./includes/sub_banners.php'); ?>
                                    </td>
                                </tr>
                            </table></td>
                    </tr>
                </table>
                <?php require_once("./includes/footer.php"); ?>