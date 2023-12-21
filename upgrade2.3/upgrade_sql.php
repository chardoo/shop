<?php
include ("../includes/config.php");
function execute_sql($sql){//just execute the query
    $res = mysqli_query($connection, $sql) or die($sql."<br />".mysqli_error($connection));
    return $res;
}
function sql_safe($str){
     $str = stripslashes($str);
    return addslashes($str);
}

//Temp comment starts here
//Banner Table
//$sql = "select nPlanId,vPlanName from `".TABLEPREFIX."Plan`";//getting the plan name and id from the plan table
//$res = execute_sql($sql);
//while ($row = mysqli_fetch_object($res)){
    execute_sql("ALTER TABLE `".TABLEPREFIX."category` ADD `cat_image` VARCHAR( 300 ) NULL AFTER `nPosition`");//new field cat_image
    execute_sql("UPDATE `".TABLEPREFIX."lookup` SET `vLookUpDesc` = '0' WHERE `".TABLEPREFIX."lookup`.`nLookUpCode` = '15' AND `".TABLEPREFIX."lookup`.`vLookUpDesc` = '1' LIMIT 1
            ");
    execute_sql("INSERT INTO `".TABLEPREFIX."content` ( `content_id` ,`content_name` ,`content_type` ,`content_status`)
                VALUES (NULL , 'paypalEmailRequestToSeller', 'email', 'y'");

    execute_sql("INSERT INTO `".TABLEPREFIX."content_lang` (`content_lang_id` ,`content_id` ,`lang_id` ,`content` ,`content_title`)
                VALUES (NULL , '42', '1', '<table class=\"maintext2\" border=\"0\" cellspacing=\"1\" cellpadding=\"4\" width=\"100%\">                              
                <tbody>                              
                <tr bgcolor=\"#ffffff\">                              
                <td class=\"maintext2\" align=\"left\"><b>Payment Information</b></td> </tr>                              
                <tr bgcolor=\"#ffffff\">                              
                <td width=\"21%\" align=\"left\">Sale of your items have been discontinued temporarily. Kindly update your Profile contact details with \"PayPal EmailId\"</td>  </tr>                              
                <tr bgcolor=\"#ffffff\">                              
                <td colspan=\"2\" align=\"left\">Thank You,</td> </tr>                              
                <tr bgcolor=\"#ffffff\">                              
                <td align=\"left\">{SITE_NAME} Crew | {SITE_URL}</td> </tr> </tbody></table>        

                ', '{SITE_NAME} - Sale of your items have been discontinued temporarily'
                )");
    execute_sql(" INSERT INTO `".TABLEPREFIX."content_lang` (
                `content_lang_id` ,
                `content_id` ,
                `lang_id` ,
                `content` ,
                `content_title`
                )
                VALUES (
                '167', '42', '2', ' <b> conditions de vente</b> <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"> <tbody> <tr> <td colspan=\"3\" style=\"width: 576px; height: 0px;\"> <p>Vente de vos articles ont &eacute;t&eacute; supprim&eacute;s temporairement. Veuillez mettre &agrave; jour vos coordonn&eacute;es avec le profil \"Id Email PayPal\"</p> <p>Merci!</p> </td></tr> <tr> <td colspan=\"3\" style=\"width: 576px; height: 0px;\"> <p>{SITE_NAME} Crew. | {SITE_URL}</p> </td></tr></tbody></table> ', '{SITE_NAME} - Vente de vos articles ont été supprimés temporairement'
                )");
    execute_sql("
                INSERT INTO `".TABLEPREFIX."content_lang` (
               `content_lang_id` ,
               `content_id` ,
               `lang_id` ,
               `content` ,
               `content_title`
               )
               VALUES (
               '168', '42', '5', ' <table border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"586\"> <tbody> <tr> <td colspan=\"2\"> <b>condiciones de venta</b> <p>La venta de sus art&iacute;culos se han suspendido temporalmente. Por favor actualice sus datos de contacto con perfil \"Identificaci&oacute;n del email PayPal\"</p> <p>Gracias.</p> </td></tr> <tr> <td colspan=\"2\"> <p>{SITE_NAME} Tripulacion. | {SITE_URL}</p> <div><br /></div></td></tr></tbody></table> ', '{SITE_NAME} - La venta de sus artículos se han suspendido temporalmente'
               )");
    execute_sql("INSERT INTO `".TABLEPREFIX."content_lang` (
                `content_lang_id` ,
                `content_id` ,
                `lang_id` ,
                `content` ,
                `content_title`
                )
                VALUES (
                '169', '42', '6', ' <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"> <tbody> <tr> <td colspan=\"3\" style=\"width: 576px; height: 0px;\"><b>Zahlungsinformationen</b><br /><br />Verkauf Ihrer Artikel wurden vor&uuml;bergehend eingestellt. Bitte aktualisieren Sie Ihr Profil Kontaktdaten mit \"PayPal Email Id\"<br /><br />Vielen Dank</td></tr> <tr> <td colspan=\"3\" style=\"width: 576px; height: 0px;\"> <p>{SITE_NAME} Crew | {SITE_URL}</p> </td></tr></tbody></table> ', '{SITE_NAME} - Verkauf Ihrer Artikel wurden vorübergehend eingestellt'
                )
                ");

 ;

//    ;
//}

?>