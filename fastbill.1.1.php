<?php

	/* ************************************************ */
	/*  	Copyright: DIGITALSCHMIEDE	            	*/
	/*  	http://www.digitalschmiede.de	         	*/
    /*      https://github.com/Digitalschmiede/fastbill */
	/* ************************************************ */
	
	class fastbill
	{
	    private $email = '';
	    private $apiKey = '';
	    private $apiUrl = '';
	    private $debug = false;
	
	    public function __construct($email, $apiKey, $apiUrl = 'https://my.fastbill.com/api/1.0/api.php')
	    {
	        if($email != '' && $apiKey != '')
	        {
	            $this->email = $email;
	            $this->apiKey = $apiKey;
	            $this->apiUrl = $apiUrl;
	        }
	        else
	        {
	            return false;
	        }
	    }
	
	    public function setDebug($bool = false)
	    {
	        if($bool != '')
	        {
	            $this->debug = $bool;
	        }
	        else
	        {
	            if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Übergabeparameter 1 ist leer!"))); }
	            else { return false; }
	        }
	    }
	
	    public function request($data, $file = NULL)
	    {
	        if($data)
	        {
	            if($this->email != '' && $this->apiKey != '' && $this->apiUrl != '')
	            {
	                $ch = curl_init();
	
	                $data_string = json_encode($data);
	                if($file != NULL) { $bodyStr = array("document" => "@".$file, "httpbody" => $data_string); }
	                else { $bodyStr = array("httpbody" => $data_string); }
	
	                curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
	                curl_setopt($ch, CURLOPT_HTTPHEADER, array('header' => 'Authorization: Basic ' . base64_encode($this->email.':'.$this->apiKey)));
	                curl_setopt($ch, CURLOPT_POST, 1);
	                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
	                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
	
	                $exec = curl_exec($ch);
	                $result = json_decode($exec,true);
	
	                curl_close($ch);
	
	                return $result;
	            }
	            else
	            {
	                if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Email und/oder APIKey und/oder APIURL Fehlen!"))); }
	                else { return false; }
	            }
	        }
	        else
	        {
	            if($this->debug == true) { return array("RESPONSE" => array("ERROR" => array("Übergabeparameter 1 ist leer!"))); }
	            else { return false; }
	        }
	    }
	}

?>