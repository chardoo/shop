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
sleep(15);
include ("./includes/config.php");
include ("./includes/session.php");
include ("./includes/functions.php");
include("./languages/" . $_SESSION['lang_folder'] . "/user.php"); //language file
include ("./includes/session_check.php");
include_once('./includes/gpc_map.php');

$txtPaypalEmail = DisplayLookUp('paypalemail');
$txtPaypalAuthtoken = DisplayLookUp('paypalauthtoken');
$txtPaypalSandbox = DisplayLookUp('paypalmode');

if ($txtPaypalSandbox == "TEST") {
    $paypalurl = "www.sandbox.paypal.com";
}//end if
else {
    $paypalurl = "www.paypal.com";
}//end else

$flag_to_continue = false;

//Data check for PDT and proceeded further
// read the post from PayPal system and add 'cmd'

if($_REQUEST['msg']=='' ) { 
$req = 'cmd=_notify-synch';

$tx_token = $_GET['tx'];

if($tx_token!=''){
$auth_token = $txtPaypalAuthtoken;
$req .= "&tx=$tx_token&at=$auth_token";

// post back to PayPal system to validate
/*$header .= "POST https://".$paypalurl."/cgi-bin/webscr HTTP/1.0\r\n"; 
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Host: ".$paypalurl."\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
//$fp = fsockopen($paypalurl, 80, $errno, $errstr, 30);
$fp = fsockopen('ssl://'.$paypalurl, 443, $errno, $errstr, 30);*/
$header .= "POST https://".$paypalurl."/cgi-bin/webscr HTTP/1.1\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Host: ".$paypalurl."\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n";
$header .= "Connection: close\r\n\r\n";
$fp = fsockopen ("ssl://".$paypalurl, 443, $errno, $errstr, 30);

/*$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
//$fp = fsockopen($paypalurl, 80, $errno, $errstr, 30);
$fp = fsockopen ("ssl://".$paypalurl, 443, $errno, $errstr, 30);*/

if (trim($txtPaypalAuthtoken)==''){
    $flag_to_continue = true;
}
else if (!$fp) {
    // HTTP ERROR
}//end if
else {
    fputs($fp, $header . $req);
    // read the body data
    $res = '';
    $headerdone = false;
    while (!feof($fp)) {
        $line = fgets($fp, 1024);
        if (strcmp($line, "\r\n") == 0) {
            // read the header
            $headerdone = true;
        }//end if
        else if ($headerdone) {
            // header has been read. now read the contents
            $res .= $line;
        }//end else if
    }//end while
    // parse the data

    $lines = explode("\n", $res);
    $keyarray = array();
    //echo($lines[0] . " here");
    if (strcmp($lines[1], "SUCCESS") == 0 || strcmp($lines[0], "SUCCESS") == 0) {
        for ($i = 2; $i < count($lines); $i++) {
            list($key, $val) = explode("=", $lines[$i]);
            $keyarray[urldecode($key)] = urldecode($val);
        }//end for loop
        // check the payment_status is Completed
        // check that txn_id has not been previously processed
        // check that receiver_email is your Primary PayPal email
        // check that payment_amount/payment_currency are correct
        // process payment
        $flag_to_continue = true;
    }//end if
    else if (strcmp($lines[1], "FAIL") == 0 || strcmp($lines[0], "FAIL") == 0) {
        // log for manual investigation
        //      echo("Fail in result");
        $flag_to_continue = false;
    }//end else if
}//end if

fclose($fp);

}

$var_id = "";
$var_new_id = "";
$message = "";
$flag = false;

if ($flag_to_continue == true) {
    // $txnid=$_GET["txnid"];
    $txnid = $keyarray['txn_id'];
    $var_txnid = $txnid;
$_SESSION['sess_PointAmount'] = $_GET["amt"];

    $sqltxn = @mysqli_query($conn, "Select vTxnId from " . TABLEPREFIX . "creditpayments where vTxnId ='" . $txnid . "' AND vMethod='pp'") or die(mysqli_error($conn));

    if (@mysqli_num_rows($sqltxn) > 0) {
        //$message = ERROR_COMMUNICATION_ERROR_WITH_PAYMENT_SERVER;
        $flag = true;

        $message = str_replace('{amount}',CURRENCY_CODE . $_SESSION['sess_PointAmount'],str_replace('{point_name}',POINT_NAME,MESSAGE_SUCCESS_PURCHASED_POINTS));

        //clear sessions
        $_SESSION['sess_PointSelected'] = "";
        $_SESSION['sess_PointAmount'] = "";
       $_SESSION['payment_message'] = $message;
    }//end if
    else {
        $var_date = date('m/d/Y');

        //checking alredy exits
        $chkPoint = fetchSingleValue(select_rows(TABLEPREFIX . 'usercredits', 'nPoints', "WHERE nUserId='" . $_SESSION["guserid"] . "'"), 'nPoints');
        if (trim($chkPoint) != '') {
            //update points to user credit
            mysqli_query($conn, "UPDATE " . TABLEPREFIX . "usercredits set nPoints=nPoints+" . $_SESSION['sess_PointSelected'] . " WHERE
											nUserId='" . $_SESSION["guserid"] . "'") or die(mysqli_error($conn));
        }//end if
        else {
            //add points to user credit
            mysqli_query($conn, "INSERT INTO " . TABLEPREFIX . "usercredits (nPoints,nUserId) VALUES ('" . $_SESSION['sess_PointSelected'] . "','" . $_SESSION["guserid"] . "')") or die(mysqli_error($conn));
        }//end else
        //added purchase date point and amount conversion status
        $vComments = CURRENCY_CODE . DisplayLookUp('PointValue') . '&nbsp;=&nbsp;' . DisplayLookUp('PointValue2') . '&nbsp;' . POINT_NAME;

        //add into user table
        mysqli_query($conn, "INSERT INTO " . TABLEPREFIX . "creditpayments (nUserId,nAmount,nPoints,vTxnId,vMethod,dDate,vCurrentTransaction,vStatus) VALUES
								('" . $_SESSION["guserid"] . "','" . $_SESSION['sess_PointAmount'] . "','" . $_SESSION['sess_PointSelected'] . "','" . $txnid . "',
								'pp',now(),'" . addslashes($vComments) . "','A')") or die(mysqli_error($conn));

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
           AND C.content_name = 'pointsPurchasedMailToUser'
           AND C.content_type = 'email'
           AND L.lang_id = '".$_SESSION["lang_id"]."'";

        $mailRs  = mysqli_query($conn, $mailSql) or die(mysqli_error($conn));
        $mailRw  = mysqli_fetch_array($mailRs);

        $mainTextShow   = $mailRw['content'];

        $arrTSearch     = array("{SITE_NAME}","{SITE_URL}","{SITE_EMAIL}","{point_val}","{point_name}","{POINT_NAME}","{payment_type}","{date}","{sess_PointAmount}","{guserFName}");
        $arrTReplace    = array(SITE_NAME,SITE_URL,SITE_EMAIL,$_SESSION['sess_PointSelected'],POINT_NAME,POINT_NAME,"Pay Pal",date('m/d/Y'),CURRENCY_CODE.$_SESSION["sess_PointAmount"],$_SESSION["guserFName"]);
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
           AND C.content_name = 'pointsPurchasedMailToAdmin'
           AND C.content_type = 'email'
           AND L.lang_id = '".$_SESSION["lang_id"]."'";

        $mailRs  = mysqli_query($conn, $mailSql) or die(mysqli_error($conn));
        $mailRw  = mysqli_fetch_array($mailRs);

        $mainTextShow   = $mailRw['content'];
        $mainTextShow    = str_replace("{POINT_NAME}", "{point_name}", $mainTextShow);

        $arrTSearch     = array("{SITE_NAME}","{SITE_URL}","{SITE_EMAIL}","{point_val}","{point_name}","{POINT_NAME}","{payment_type}","{date}","{sess_PointAmount}","{guserFName}");
        $arrTReplace    = array(SITE_NAME,SITE_URL,SITE_EMAIL,$_SESSION['sess_PointSelected'],POINT_NAME,POINT_NAME,"Pay Pal",date('m/d/Y'),CURRENCY_CODE.$_SESSION["sess_PointAmount"],$_SESSION["guserFName"]);
        $mainTextShow   = str_replace($arrTSearch,$arrTReplace,$mainTextShow);

        $mailcontent1   = $mainTextShow;

        $subject    = $mailRw['content_title'];
        $subject    = str_replace("{POINT_NAME}", POINT_NAME, $subject);

        $StyleContent = MailStyle($sitestyle, SITE_URL);
        $EMail        = $var_admin_email;


        $arrSearch = array("{TITLE}", "{STYLE}", "{SITE-URL}", "{NAME}", "{CONTENT}", "{SITE-LOGO}", "{DATE}", "{SITE-NAME}", "{HEAD}");
        $arrReplace = array(SITE_TITLE, $StyleContent, SITE_URL, 'Admin', $mailcontent1, $logourl, date('m/d/Y'), SITE_NAME, $subject);
        $msgBody    = file_get_contents('languages/'.$langRw["folder_name"].'/mail.html');
        $msgBody = str_replace($arrSearch, $arrReplace, $msgBody);
        send_mail($EMail, $subject, $msgBody, SITE_EMAIL, 'Admin');
      
        $flag = true;

        $message = str_replace('{amount}',CURRENCY_CODE . $_SESSION['sess_PointAmount'],str_replace('{point_name}',POINT_NAME,MESSAGE_SUCCESS_PURCHASED_POINTS));
        $_SESSION['payment_message'] = '';
        $_SESSION['payment_message'] = $message;
        //clear sessions
        $_SESSION['sess_PointSelected'] = "";
        $_SESSION['sess_PointAmount'] = "";
    }//end else
}//end if
    header("Location:credits_success.php?msg=success");
    exit;
}

include_once('./includes/title.php');
?>
<body onLoad="timersOne();">
<?php include_once('./includes/top_header.php'); ?>

<div class="homepage_contentsec">
	<div class="container">
		<div class="row">
			<div class="col-lg-3">
				<?php include_once ("./includes/usermenu.php"); ?>
			</div>
			<div class="col-lg-9">
				<div class="full-width">
					<div class="col-lg-12">
						<div class="innersubheader2">
							<h3><?php echo HEADING_PAYMENT_STATUS; ?></h3>
						</div>
					</div>
				</div>
				<div class="full-width">
					<div class="col-lg-12">
						<div class="full_width">
							<?php
							if (isset($_SESSION['payment_message']) && trim($_SESSION['payment_message']) != '') {
								?>
									<div class="row success"><?php echo $_SESSION['payment_message']; ?></div>
							<?php
							}//end if
							else {
								?>
								<?php
							}//end else
							?>
							</div>
					</div>
				</div>
				<div class="full-width subbanner">
					<div class="col-lg-12">
						<?php include('./includes/sub_banners.php'); ?>
					</div>
				</div>	
			</div>
		</div>
	</div>
</div>

<?php require_once("./includes/footer.php"); ?>