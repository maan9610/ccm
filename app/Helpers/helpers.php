<?php 

use App\Models\Creators;

if(!function_exists('sendMessage')){
	function sendMessage($mobile,$message)
	{
		$api_key = '563C78DD92E750';
        $from = 'GLDSMD';
        $template_id = '1507166736715276618';
        $pe_id = '1501616890000052669';
        $sms_text = urlencode('Welcome...!
        
    Your OTP is '.$message.'
        
    Please do not share with anyone.
        
    Thank you
    GLDSMD');
       
        
        //Submit to server
        
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, "http://103.182.103.247/app/smsapi/index.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&campaign=12417&routeid=3&type=text&contacts=".$mobile."&senderid=".$from."&msg=".$sms_text."&template_id=".$template_id."&pe_id=".$pe_id);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       
        curl_close($ch);
       
       
        //Submit to server
      
        $err = curl_error($ch);

		curl_close($ch);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
		  return "1";
		}

	}
	
	function countItemsInCommaSeparatedString($values)
	{
		// Convert the string into an array
		$items = explode(',', $values);

		// Count the number of items in the array
		return count($items);
	}
	
	function returnAvgValueColumnName($creatorId){
		$creatorsData = Creators::find($creatorId);
		
		echo "Creator Category Name = ".$creatorsData->creatorName;
	}
}