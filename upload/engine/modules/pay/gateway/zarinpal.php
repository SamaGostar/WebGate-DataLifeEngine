<?php
/*
=====================================================
 DLEFA Pay  Ver 1.1
-----------------------------------------------------
 Persian support site: https://dlefa.ir
-----------------------------------------------------
 FileName :  zarinpal.php
-----------------------------------------------------
 Copyright (c) 2021, All rights reserved.
=====================================================
*/

if ($pay_config['sandbox'] == 1) {
    $Mtest = 1;
} else {
    $Mtest = 0;
}

if ($pay_config['zaringate'] == 1) {
    $ZarinGate = '/ZarinGate';
} else {
    $ZarinGate = '';
}

$Url = array(
    array('0' => "https://www.zarinpal.com/pg/StartPay/"),
    array('0' => "https://sandbox.zarinpal.com/pg/StartPay/")
);

$error = array(
    '-1' => 'اطلاعات ارسال شده ناقص است',
    '-2' => 'IP و یا مرچنت کد پذیرنده صحیح نیست',
    '-3' => 'با توجه به محدودیت های شاپرک امکان پرداخت با رقم در خواست شده میسر نمی باشد',
    '-4' => 'سطح تایید پذیرنده یایین تر از سطح نقره ای است',
    '-11' => 'درخواست مورد نظر یافت نشد',
    '-12' => 'امکان ویرایش درخواست میسر نمی باشد',
    '-21' => 'هيچ نوع عمليات مالي براي اين تراكنش يافت نشد',
    '-22' => 'تراكنش ناموفق مي باشد.',
    '-33' => 'رقم تراكنش با رقم پرداخت شده مطابقت ندارد',
    '-34' => 'سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است',
    '-40' => 'اجازه دسترسي به متد مربوطه وجود ندارد',
    '-41' => 'اطلاعات ارسال شده مربوط به  AdditionalDataغيرمعتبر ميباشد',
    '-42' => 'مدت زمان معتبر طول عمر شناسه پرداخت بايد بين  30دقيه تا  45روز مي باشد',
    '-45' => 'درخواست مورد نظر آرشيو شده است',
    '100' => 'عمليات با موفقيت انجام گرديده است',
    '101' => 'عمليات پرداخت موفق بوده و قبلا PaymentVerification تراكنش انجام شده است'
);

function Request($MerchantID, $amount, $callback){
    global $Url, $Mtest, $ZarinGate, $error;
    $orderId = rand();
    $param_request = array(
        'merchant_id' => $MerchantID,
        'amount' => $amount,
        'description' => 'پرداخت آنلاین سایت',
        'callback_url' => $callback
    );
    $jsonData = json_encode($param_request);

    $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/request.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));

    $result = curl_exec($ch);
    $err = curl_error($ch);
    $result = json_decode($result, true, JSON_PRETTY_PRINT);
    curl_close($ch);

    if ($result['data']['code'] == 100) {
         array(
            'status' => true,
            'ref'    => $result['data']["authority"],
        );
             echo' <html><body>
                    <script type="text/javascript" src="https://cdn.zarinpal.com/zarinak/v1/checkout.js"></script>
                    <script type="text/javascript">
                    window.onload = function () {
                    Zarinak.setAuthority("' . $result['data']['authority'] . '");
                    Zarinak.showQR();
                    Zarinak.open();
    };
            </script></body></html>';
    }else
        return array(
            'status' => false,
            'msg'	 => $error[$result['errors']['code']]
        );
}

function Verify($MerchantID, $Authority, $amount){
    global $Url, $Mtest, $error;

    $param_verify = array("merchant_id" => $MerchantID, "authority" => $Authority, "amount" => $amount);
    $jsonData = json_encode($param_verify);
    $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/verify.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));

    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    $result = json_decode($result, true);

    if ($result['data']['code'] == 100) {
        return array(
            'status' => true,
            'ref'    => $result['data']['ref_id'],
            'msg'    => $error[$result['data']['code']]
        );
    }else
        return array(
            'status' => false,
            'msg'    => $error[$result['errors']['code']]
        );
}

?>