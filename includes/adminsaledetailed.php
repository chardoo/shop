<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | PHP version 4/5                                                      |
// +----------------------------------------------------------------------+
// | This source file is a part of iScripts eSwap                     |
// +----------------------------------------------------------------------+
// | Authors: Programmer<simi@armia.com>        		              |
// +----------------------------------------------------------------------+
// | Copyrights Armia Systems, Inc and iScripts.com � 2005                |
// | All rights reserved                                                  |
// +----------------------------------------------------------------------+
// | This script may not be distributed, sold, given away for free to     |
// | third party, or used as a part of any internet services such as      |
// | webdesign etc.                                                       |
// +----------------------------------------------------------------------+
function func_sale_detailed($uid = 0,$fid = 0)
{
  global $conn;
?>
<table width="100%"  border="0" cellspacing="1" cellpadding="4" class="withoutlink admin_tble_2">
<form name="frmSale" id="frmSale" ACTION="<?php echo $_SERVER['PHP_SELF']?>" method="post">
<?php
$title="Sale Diary";

$txtSearch="";
$cmbSearchType="";
$var_rf="";
$var_no="";


//Set Featured Items 

if(isset($_REQUEST["feature"])){
if($_REQUEST["feature"] == "Y")
{
	$sql = "Update ".TABLEPREFIX."sale set vFeatured='Y' where nSaleId='" . addslashes($_REQUEST["saleid"]) . "'";
}//end if
else if($_REQUEST["feature"] == "N")
{
	$sql = "Update ".TABLEPREFIX."sale set vFeatured='N' where nSaleId='".  addslashes($_REQUEST["saleid"])  . "'";
}//end else if

mysqli_query($conn, $sql) or die(mysqli_error($conn));

header("Location:sales.php");
exit;
}
// End Feature Update
if($_GET["txtSearch"] != "")
{
  $txtSearch = $_GET["txtSearch"];
  $cmbSearchType =  $_GET["cmbSearchType"];
}//end if
elseif($_POST["txtSearch"] != "")
{
    $txtSearch = $_POST["txtSearch"];
    $cmbSearchType =  $_POST["cmbSearchType"];
}//end else if

$qryopt="";
if($txtSearch != "")
{
    if($cmbSearchType == "category")
	{
        $qryopt .= "  AND L.vCategoryDesc like '%" . addslashes($txtSearch) . "%'";
    }//end if
	elseif($cmbSearchType == "title")
	{
        $qryopt .= " AND vtitle like '%" . addslashes($txtSearch) . "%'";
    }//end else if
    elseif($cmbSearchType == "user")
	{
        $qryopt .= "  AND vLoginName like '%" . addslashes($txtSearch) . "%'";
    }//end else if
}//end if

$targetfile="";
$detailfile="";
 $sql = "SELECT s.nSaleId,s.vTitle,date_format(s.dPostDate,'%m/%d/%Y') as 'dPostDate',
                     L.vCategoryDesc,u.vLoginName as 'UserName',s.vDelStatus,s.vFeatured,s.nQuantity
            FROM ".TABLEPREFIX."sale s
                  left join ".TABLEPREFIX."category c on s.nCategoryId = c.nCategoryId 
                  left join ".TABLEPREFIX."users u on s.nUserId=u.nUserId
                  LEFT JOIN " . TABLEPREFIX . "category_lang L on c.nCategoryId = L.cat_id and L.lang_id = '" . $_SESSION['lang_id'] . "' 
            where s.vDelStatus='0' ";

if($uid === 0)
{
    $targetfile="salelistdetailed.php";
    $detailfile="swapitem.php";
}//end if
elseif($uid > 0)
{
  $targetfile="usersaledetailed.php";
  $detailfile="swapitem.php";
}//end else if


if($_REQUEST['num']!='') { 
$page   = $_REQUEST['num'];
}
else {
    $page   =   1 ;
}
$sql .= $qryopt . " ORDER BY s.dPostDate DESC ";

$sess_back= $targetfile .  "?begin=" . $begin . "&num=" . $num . "&numBegin=" . $numBegin . "&cmbSearchType=" . $cmbSearchType . "&txtSearch=" . $txtSearch . "&source=" . $var_source . "&no=" . $var_no;

//get the total amount of rows returned
$totalrows = mysqli_num_rows(mysqli_query($conn, $sql));

/*
Call the function:

I've used the global $_GET array as an example for people
running php with register_globals turned 'off' :)
*/

$navigate = pageBrowser($totalrows,10,10,"&cmbSearchType=$cmbSearchType&txtSearch=" . urlencode($txtSearch) . "&",$_GET[numBegin],$_GET[start],$_GET[begin],$_GET[num]);

//execute the new query with the appended SQL bit returned by the function
$sql = $sql.$navigate[0];
$rs = mysqli_query($conn, $sql);

$message=($message!='')?$message:$_SESSION['sessionMsg'];
unset($_SESSION['sessionMsg']);

if(isset($message) && $message!='')
					      {
?>
                              <tr bgcolor="#FFFFFF" class="maintext2">
                                <td colspan="7" align="center" class="warning"><?php echo $message;?></td>
                              </tr>
<?php  }//end if?>			
<tr bgcolor="#FFFFFF"><input NAME="rf" TYPE="hidden" id="rf" VALUE="<?php echo $var_rf?>">
<input NAME="no" TYPE="hidden" id="no" VALUE="<?php echo $var_no?>">
<input name="uname" TYPE="hidden" id="uname" VALUE="<?php echo htmlentities($var_uname)?>">
<input name="postback" type="hidden" id="postback">
                                <td colspan="7" align="center">
			
<table border="0" width="100%" class="maintext">
                                        <tr>
                                                <td valign="top" align="right">
                                                Search
                                         &nbsp; <select name="cmbSearchType" class="textbox2">
                                                                                <option value="category"  <?php if($cmbSearchType == "category" || $cmbSearchType == ""){ echo("selected"); } ?>>Category</option>
                                                                                <option value="title" <?php if($cmbSearchType == "title"){ echo("selected"); } ?>>Title</option>
                                                                                <option value="user" <?php if($cmbSearchType == "user"){ echo("selected"); } ?>>User Name</option>
                                                                          </select>

                                        &nbsp;<input type="text" name="txtSearch" size="20" maxlength="50" value="<?php echo(htmlentities($txtSearch)); ?>"  onKeyPress="if(window.event.keyCode == '13'){ return false; }" class="textbox2">

                                                </td>
                                                <td align="left" valign="baseline">
                                                <a class="link_style2" href="javascript:javascript:document.frmSale.submit();">Go</a>
                                                </td>
                                        </tr>
                                </table></td>
                      </tr>  
                              <tr align="center" bgcolor="#FFFFFF" class="gray">
                                <td width="7%" align="center" valign="middle">Sl No. </td>
                                <td width="21%" align="center" valign="middle">Category</td>
                                <td width="18%" align="center" valign="middle">Title</td>
                                <td width="6%" align="center" valign="middle">Featured</td>
                                <td width="21%" align="center" valign="middle">Date</td>
                                <td width="14%" align="center" valign="middle">User Name</td>
                                <td width="13%" align="center" valign="middle">Status</td>
                      </tr>
					  <?php
					     if(mysqli_num_rows($rs)>0)
						 {
                                                $i=1;
                                                if ($page == 1) {
                                                       $i = 1;
                                                   } else {
                                                       $i = (($page - 1) * 10) + 1;
                                                   }
                                            while ($arr = mysqli_fetch_array($rs))
                                            {  
                                                    //checking status
                                                $chageTo = 'Y';
                                                    switch($arr["vFeatured"])
                                                    {
                                                            case "N":
                                                                    $ref_string='<img src="../images/nonfeatrd.gif" border="0" title="This is a non-featured item.">';
                                                                $chageTo    = "Y";
                                                            break;

                                                            case "Y":
                                                                    $ref_string='<img src="../images/featrd.gif" border="0" title="This is a featured item.">';
                                                                $chageTo    = "N";
                                                            break;
                                                    }//end switch

                                            $username = htmlentities($arr['UserName']);
                                            if(strlen($arr['UserName'])>10)
					                        {
					                        	$username = substr(htmlentities($arr['UserName']),0,10)."...";
					                        }
					                        
					                        $item_title = htmlentities($arr['vTitle']);
					                        if(strlen($arr['vTitle'])>20)
					                        {
					                        	$item_title = substr(htmlentities($arr['vTitle']),0,20)."...";
					                        }
					                       
                                                    
                                                      if($arr["vDelStatus"] == "1") { $status = "Deleted"; }
                                                      else if($arr["nQuantity"] == "0") { $status = "Sold Out"; }
                                                      else {  $status = "Active"; }
					  ?>
                              <tr bgcolor="#FFFFFF">
                                <td align="center" valign="middle" class="maintext2"><?php echo $i;?></td>
                                <td align="center" valign="middle" class="maintext2"><?php echo '<a href="'.$detailfile.'?saleid='.$arr["nSaleId"].'&source=sa" title="Click Here to Edit/Delete">'.htmlentities($arr["vCategoryDesc"]).'</a>';?></td>
                                <td align="center" valign="middle" class="maintext2"><?php echo '<a href="'.$detailfile.'?saleid='.$arr["nSaleId"].'&source=sa" title="Click Here to Edit/Delete">'.$item_title.'</a>';?></td>
                                <td align="center" valign="middle"><?php echo '<a href="sales.php?saleid='.$arr["nSaleId"].'&source=sa&feature='.$chageTo.'" title="Click Here to Edit/Delete">'.$ref_string.'</a>';?></td>
                                <td align="center" valign="middle" class="maintext2"><?php echo '<a href="'.$detailfile.'?saleid='.$arr["nSaleId"].'&source=sa" title="Click Here to Edit/Delete">'.date('F d, Y',strtotime($arr["dPostDate"])).'</a>';?></td>
                                <td align="center" valign="middle" class="maintext2"><?php echo '<a href="'.$detailfile.'?saleid='.$arr["nSaleId"].'&source=sa" title="Click Here to Edit/Delete">'.$username.'</a>';?></td>
                                <td align="center" valign="middle" class="maintext2"><?php echo '<a href="'.$detailfile.'?saleid='.$arr["nSaleId"].'&source=sa" title="Click Here to Edit/Delete">'.$status.'</a>';?></td>
                              </tr>
					<?php 
								$i++;
							}//end while
						}//end if
				  ?>
                              <tr bgcolor="#FFFFFF" class="maintext2">
                                <td colspan="7" align="left"><table width="100%"  border="0" cellspacing="1" cellpadding="5">
  <tr class="maintext2">
    <td align="left"><?php echo($navigate[2]);?></td>
    <td align="right"><?php echo("Listing $navigate[1] of $totalrows results.");?></td>
  </tr>
</table>
</td>
                      </tr>
							  </form>
                            </table>
<?php }//end function?>							