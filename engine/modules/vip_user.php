<?PHP

/*
Masoud Amini
*/

if( ! defined('DATALIFEENGINE') ) {
	die( "Hacking attempt!" );
}

  $this_time = time() + ($config['date_adjust'] * 60);


/*//   پرداخت
####################################################################################################################
*/


	if ( $member_id['vip_approve'] == 0 ) {


  if ( $doaction == "ok") {

	  $au = $_GET['Authority'];
	  
	 // $price = $_GET['price'];
	  $rezarinpal = $db->super_query("SELECT * FROM ".PREFIX."_vip_zarinpal where au = '$au'");
	  $res_plan = $db->super_query("SELECT * FROM ".PREFIX."_vip_panel where id='$rezarinpal[vip_panel]'");
   	@require_once ROOT_DIR . '/engine/classes/nusoap/nusoap.php';
	  $MID = $setting_res['marchentid'];
	  $time_end = strtotime("+6 month");
	  $this_time = time() + ($config['date_adjust'] * 60);
      $price = $rezarinpal['price'];
	  $date_zarinpal = jdate('Y/m/d H:m');

	  $view_endTIME = jdate('Y/m/d', $time_end);
	  
	$setting_res = $db->super_query("SELECT * FROM ".PREFIX."_vip_setting where id = '1'");
	
	$client = new nusoap_client('https://de.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
	$result = $client->call("PaymentVerification", array(
		array(
					'MerchantID'	 => $MID ,
					'Authority' 	 => $au ,
					'Amount'	 => $price
				)));
	if ($rezarinpal['res'] == 1 )
	$result = '-5';
$RefID = $result['RefID '];
	switch($result['Status']){

		case '100' :
			$prompt="فرايند بازگشت با موفقيت انجام شد".;
			break;
		
		case '-1' :
			$prompt="اطلاعات ناقص است";
			break;
		case '-2' :
			$prompt="وبسرويس نامعتبر";
			break;
		case '0' :
			$prompt="عمليات پرداخت به طور کامل طی نشده";
			break;
		case '-5' :
			$prompt="تراکنش تکراری";
			break;
		case '-11' :
			$prompt="سند قبلا برگشت کامل يافته است.";
			break;
		case '-12' :
			$prompt="زمان فعال جهت پرداخت صورت حساب طی شده و کاربر عمليات پرداخت را تکميل نکرده";
			break;
			DEFAULT :
			$prompt="فرایند توسط خریدار منقضی گردید.".$result['Status'];
			break;
	}

	

	  if ( $result['Status'] == 100 ) {

		  $result_payment = "<div class=\"success\">
		  	پرداخت و عضویت VIP شما با موفقیت انجام گردید.
			<br>

			<table width=\"100%\">
				<tr>
				 	<td> کد پیگیری  : </td>
					<td> <strong> $RefID </strong> </td>
				</tr>
				<tr>
				 	<td> پلان انتخابی: </td>
					<td> $res_plan[name] </td>
				</tr>
				<tr>
				 	<td> تاریخ شروع عضویت: </td>
					<td> $date_zarinpal </td>
				</tr>

				<tr>
				 	<td> تاریخ اتمام عضویت: </td>
					<td> $view_endTIME </td>
				</tr>


				<tr>
				 	<td> مبلغ واریزی :  </td>
					<td> $rezarinpal[price] </td>
				</tr>


			</table>

		  </div>";



  	$db->query( "UPDATE " . PREFIX . "_vip_zarinpal set `au`='".$au."',`res`='".$result['Status']."', `date`='".$date_zarinpal."', `vip_time`='".$time_end."', `show`='1' where userid='$member_id[user_id]'  limit 1");
	
	$db->query( "UPDATE " . PREFIX . "_users set `viptime_plan`='".$time_end."', `viptime_start`='".$this_time."' where user_id='$member_id[user_id]' limit 1");

	$db->query( "UPDATE " . PREFIX . "_users set `user_group`='".$setting_res['group_id']."' where user_id='$member_id[user_id]' limit 1");



	  } else {
		$result_payment = "  <div class=\"success\">
			خطا در پرداخت :  &nbsp;&nbsp; $prompt $result['Status']
			<br>
			لطفا مجددا تلاش نمایید.

		  </div>";

		  //$db->query( "DELETE FROM " . PREFIX . "_vip_zarinpal WHERE userid='$member_id[user_id]' and au='$au' and res!='1' limit 1" );

	  }






	$tpl->set( '{result}', $result_payment);
	$tpl->load_template( 'vip_success.tpl' );
	$tpl->compile( 'content' );
	$tpl->clear();


/*//   پرداخت
####################################################################################################################
*/

  } elseif ( $doaction == "payment" ) {

	  	if ( empty( $_POST['vipradio'])) {
			msgbox("خطا !"," گزینه ای برای پرداخت انتخاب نشده است.");
		} else {
		$id = intval($_POST['vipradio']);

	  	$select_row = $db->super_query("SELECT * FROM ".PREFIX."_vip_panel where id = '$id' limit 1");
	  	$setting_res = $db->super_query("SELECT * FROM ".PREFIX."_vip_setting where id = '1'");

	  @require_once ROOT_DIR . '/engine/classes/nusoap/nusoap.php';
	  $GLOBALS["RedirectURL"] = "".$config['http_home_url']."index.php?do=vip_user&doaction=ok&price=". $select_row['price']."";
	  $MID = $setting_res['marchentid'];
	  $price = $select_row['price'];
	  
	$client = new nusoap_client('https://de.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
	$desc = 'نام کاربری '.$member_id[name].' پلان '.$select_row[name]; 
	$res = $client->call('PaymentRequest', array(
			array(
					'MerchantID' 		=> $MID ,
					'Amount' 		=> $price ,
					'Description' 		=> $desc ,
					'Email' 		=> '' ,
					'Mobile' 		=> '' ,
					'CallbackURL' 		=> $GLOBALS["RedirectURL"]

					) ));
	
	//Redirect to URL You can do it also by creating a form
if($res[Status]==100){
	Header('Location: https://www.zarinpal.com/pg/StartPay/" . $res['Authority']');

	
	  


	  echo <<<HTML
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<center>

<br />
<br />
<br />
<br />

	<h2> در حال اتصال به بانک</h2>


</center>


HTML;
}else{
	echo'ERR: '.res['Status']
}
	


		}

	  $db->query( "INSERT INTO " . PREFIX . "_vip_zarinpal set `userid`='".$member_id['user_id']."', `vip_panel`='".$id."', `au` = '".$res['Authority']."', `price`='".$price."', `show`='0'");



/*//   خروجی پلان ها
####################################################################################################################
*/

  } else {







    $query = $db->query("SELECT * FROM ".PREFIX."_vip_panel order by id desc");
	while ( $row = $db->get_row($query))  {
		$price = number_format($row['price']);
		$list_panel .= "<label for=\"da$row[id]\"><li><input type=\"radio\" id='da$row[id]' name=\"vipradio\" value=\"".$row['id']."\"> $row[name] &nbsp; $price تومان </li>";

	}

/*
	@$db->query("ALTER TABLE `dle_users` ADD `viptime_start` INT( 11 ) NOT NULL AFTER `news_num`,
	ADD `viptime_plan` INT( 11 ) NOT NULL AFTER `news_num`");

*/




	$ON_FORM = "<form method=\"post\" action=\"".$config['http_home_url']."index.php?do=vip_user&doaction=payment\" enctype=\"multipart/form-data\">";  /* شروع فرم */
	$END_FORM = "</form>"; /* اتمام فرم */


	$tpl->set( '{form start}', $ON_FORM);
	$tpl->set( '{end form}', $END_FORM);
	$tpl->set( '{لیست پنل‏ها}', $list_panel);
	$tpl->load_template( 'vip_user.tpl' );
	$tpl->compile( 'content' );
	$tpl->clear();
  }
  } else {
  msgbox("خطا", "شما عضو VIP بوده و قادر به پرداخت و عضويت VIP مجدد نميباشيد. در صورت هرگونه مشکل با مديريت تماس حاصل نماييد."
  );
  }

?>
