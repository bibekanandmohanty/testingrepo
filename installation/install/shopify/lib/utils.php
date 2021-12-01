<?php/*    Purpose: General set of utility functions    Author: Jonathan Schwartz*/

    /*        send no cache info to browser    */    function prevent_cache_headers(){
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");        header("Cache-Control: no-store, no-cache, must-revalidate");        header("Cache-Control: post-check=0, pre-check=0", false);        header("Pragma: no-cache");    }

	//simple function to validate webhook    function verify_webhook($data, $hmac_header){
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_CLIENT_SECRET, true));        return ($hmac_header == $calculated_hmac);    }

	function execInBackground($cmd){	    if (substr(php_uname(), 0, 7) == "Windows"){ 	        pclose(popen("start /B ". $cmd, "r"));  	    } 	    else { 	        exec($cmd . " > /dev/null &");   	    } 	} 

    function pounds2grams($pounds){
        return $pounds / 0.0022046;    }

    function grams2Pounds($grams){
        return $grams * 0.0022046;    }    
    function pass_encrypt($string, $key){        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));    }

    function pass_decrypt($string, $key){        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "\0");    }

    function get_data($url){        $ch = curl_init();        $timeout = 5;        curl_setopt($ch, CURLOPT_URL, $url);        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);        $data = curl_exec($ch);        curl_close($ch);        return $data;    }
        function get_page_head($url){
        try{            $content = get_data($url);            if($content !== false){
                $end_head = strpos(strtolower($content), '</head>');                return substr($content, 0, $end_head+7);            }        }        catch(Exception $e) {}        return '';    }

	function quotes_replace($str){		return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $str);	}

    function handleize($text){        $text = str_replace(' ','-',$text);	        $text = str_replace(',','-',$text);        $text = str_replace("'",'',$text);        $text = str_replace('+','-',$text);        $text = str_replace('/','-',$text);        $text = str_replace('.','-',$text);        $text = str_replace('&','-',$text);        $text = str_replace('--','-',$text);        $text = str_replace('--','-',$text);        $text = str_replace('--','-',$text);        $text = str_replace('--','-',$text);        $text = str_replace('--','-',$text);        $text = str_replace('(','',$text);        $text = str_replace('[','',$text);        $text = str_replace(')','',$text);        $text = str_replace(']','',$text);		$text = str_replace('"','',$text);        $text = strtolower($text);        $text = trim($text);
        return $text;    }

    function text_clean($text){        $text = str_replace(chr(10) . chr(13), '<br>', $text);        $text = str_replace(chr(13) . chr(10), '<br>', $text);        $text = str_replace(chr(10), '<br>', $text);        $text = str_replace(chr(13), '<br>', $text);        if(substr($text,0,1) == '"') $text = ltrim($text,'"');        if(substr($text,strlen($text)-1,1) == '"') $text = rtrim($text,'"');
        $text = str_replace(        array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),        array("'", "'", '"', '"', '-', '--', '...'),        $text);        $text = str_replace(        array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),        array("'", "'", '"', '"', '-', '--', '...'),        $text);        $text = preg_replace('/[^(\x20-\x7F)]*/','', $text);                return $text;    }

    function get_ip_address(){        if (isset($_SERVER)) {			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];			} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {				$ip = $_SERVER['HTTP_CLIENT_IP'];			} else {				$ip = $_SERVER['REMOTE_ADDR'];			}        } else {			if (getenv('HTTP_X_FORWARDED_FOR')) {				$ip = getenv('HTTP_X_FORWARDED_FOR');			} elseif (getenv('HTTP_CLIENT_IP')) {				$ip = getenv('HTTP_CLIENT_IP');			} else {				$ip = getenv('REMOTE_ADDR');			}        }        return $ip;    }
        //function to easily pull info from an array of params    function params_array_get($params, $param, $type = 'string', $strip_slashes = true, $strip_tags = true, $default = null){        if (isset($params[$param]) && $params[$param] !== NULL){            if($type == 'string'){                $ret = $params[$param];                if($strip_slashes) $ret = stripslashes($ret);                if($strip_tags) $ret = strip_tags($ret);                return trim($ret);            }            else if ($type == 'numeric')                return intval($params[$param]);            else if ($type == 'bool'){                if(is_bool($params[$param]))                    return $params[$param];                elseif (strtolower($params[$param]) == 'f' || strtolower($params[$param]) == '0' || strtolower($params[$param]) == 'false' || strtolower($params[$param]) == 'no' || strtolower($params[$param]) == 'n')                    return false;                elseif (strtolower($params[$param]) == 't' || strtolower($params[$param]) == '1' || strtolower($params[$param]) == 'true' || strtolower($params[$param]) == 'yes' || strtolower($params[$param]) == 'y')                    return true;                else                    return $default;            }            else if ($type == 'date'){                $ret = $params[$param];                $ret = stripslashes($ret);                $ret = strip_tags($ret);                if($ret != ""){                    $param_time = strtotime($params[$param]);                    return date('Y-m-d H:i:s', $param_time);                }                return '';            }            else if ($type == 'array'){                //validate type                if (!is_array($params[$param]))                    return $default;					$param_val = $params[$param];					//strip stuff if specfied					if($strip_slashes || $strip_tags){						foreach($param_val as &$param_item){							if($strip_slashes) $param_item = trim(stripslashes($param_item));							if($strip_tags) $param_item = trim(strip_tags($param_item));						}   					}                return $param_val;            }            else                return $params[$param];        }        else{            if($type == 'string'){                if($default) return $default;                return '';            }            else if($type == 'date'){                if($default) return $default;                return '';            }            else if ($type == 'numeric'){                if($default) return intval($default);                return 0;            }            else if ($type == 'bool'){                if($default === null) return null;                if(is_bool($default))                    return $default;                elseif (strtolower($default) == 'f' || strtolower($default) == '0' || strtolower($default) == 'false' || strtolower($default) == 'no' || strtolower($default) == 'n')                    return false;                elseif (strtolower($default) == 't' || strtolower($default) == '1' || strtolower($default) == 'true' || strtolower($default) == 'yes' || strtolower($default) == 'y')                    return true;                else                    return $default;            }            else                return $default;        }        return null;    }
    //function to easily pull info from request array    function req_get($param, $type = 'string', $strip_slashes = true, $strip_tags = true, $default = null){        return params_array_get($_REQUEST, $param, $type, $strip_slashes, $strip_tags, $default);    }

    function states_array(){        return array('AL'=>"Alabama",  			'AK'=>"Alaska",  			'AZ'=>"Arizona",  			'AR'=>"Arkansas",  			'CA'=>"California",  			'CO'=>"Colorado",  			'CT'=>"Connecticut",  			'DE'=>"Delaware",  			'DC'=>"District Of Columbia",  			'FL'=>"Florida",  			'GA'=>"Georgia",  			'HI'=>"Hawaii",  			'ID'=>"Idaho",  			'IL'=>"Illinois",  			'IN'=>"Indiana",  			'IA'=>"Iowa",  			'KS'=>"Kansas",  			'KY'=>"Kentucky",  			'LA'=>"Louisiana",  			'ME'=>"Maine",  			'MD'=>"Maryland",  			'MA'=>"Massachusetts",  			'MI'=>"Michigan",  			'MN'=>"Minnesota",  			'MS'=>"Mississippi",  			'MO'=>"Missouri",  			'MT'=>"Montana",			'NE'=>"Nebraska",			'NV'=>"Nevada",			'NH'=>"New Hampshire",			'NJ'=>"New Jersey",			'NM'=>"New Mexico",			'NY'=>"New York",			'NC'=>"North Carolina",			'ND'=>"North Dakota",			'OH'=>"Ohio",  			'OK'=>"Oklahoma",  			'OR'=>"Oregon",  			'PA'=>"Pennsylvania",  			'RI'=>"Rhode Island",  			'SC'=>"South Carolina",  			'SD'=>"South Dakota",			'TN'=>"Tennessee",  			'TX'=>"Texas",  			'UT'=>"Utah",  			'VT'=>"Vermont",  			'VA'=>"Virginia",  			'WA'=>"Washington",  			'WV'=>"West Virginia",  			'WI'=>"Wisconsin",  			'WY'=>"Wyoming");            }

    function createRandomPassword($len = 5){        $chars = "abcdefghijkmnopqrstuvwxyz023456789";        srand((double)microtime()*1000000);        $i = 0;        $pass = '' ;
        while ($i <= $len) {            $num = rand() % 33;            $tmp = substr($chars, $num, 1);            $pass = $pass . $tmp;            $i++;        }        return $pass;    }

    function encrypt_string($input){        $inputlen = strlen($input);// Counts number characters in string $input        $randkey = rand(1, 9); // Gets a random number between 1 and 9
        $i = 0;        while ($i < $inputlen){            $inputchr[$i] = (ord($input[$i]) - $randkey);//encrpytion             $i++; // For the loop to function        }

        //Puts the $inputchr array togtheir in a string with the $randkey add to the end of the string        $encrypted = implode('.', $inputchr) . '.' . (ord($randkey)+50);        return $encrypted;    }

    function decrypt_string($input){		$input_count = strlen($input);		$dec = explode(".", $input);// splits up the string to any array		$x = count($dec);		$y = $x-1;// To get the key of the last bit in the array 
		$calc = $dec[$y]-50;		$randkey = chr($calc);// works out the randkey number
		$i = 0;		while ($i < $y){			$array[$i] = $dec[$i]+$randkey; // Works out the ascii characters actual numbers			$real .= chr($array[$i]); //The actual decryption     			$i++;		};
		$input = $real;		return $input;    }
    function mysql_date($date = '', $time = '', $all_day = false, $end_of_day = false){        //setup dates depending upon if this is an all day event or not        if($all_day == 1){            if ($end_of_day)                $date .= ' 23:59:59';            else                $date .= ' 00:00:00';        }        else            $date .= ' ' . $time;
        //turn date into MYSQL appropriate string        $date = strtotime($date);        $date = Date('Y-m-d H:i', $date);        return $date;    }

    function isValidEmail($value){        $pattern = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/";        return preg_match($pattern, $value);    }

    function page_load_end($params = array(), $page_load_start) {        if(count($params) > 0)            $page_load_start = $params['page_load_start'];        $load_time = microtime();        $load_time = explode(' ',$load_time);        $load_time = $load_time[1] + $load_time[0];        $page_end = $load_time;        $final_time = ($page_end - $page_load_start);        $page_load_time = number_format($final_time, 4, '.', '');        return $page_load_time;    }
    
    /**    * xml2array() will convert the given XML text to an array in the XML structure.    * Link: http://www.bin-co.com/php/scripts/xml2array/    * Arguments : $contents - The XML text    *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.    *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.    * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.    * Examples: $array =  xml2array(file_get_contents('feed.xml'));    *              $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));    */   function xml2array($contents, $get_attributes=1, $priority = 'tag') {       if(!$contents) return array();       if(!function_exists('xml_parser_create')) {           //print "'xml_parser_create()' function not found!";           return array();       }
       //Get the XML parser of PHP - PHP must have this module for the parser to work       $parser = xml_parser_create('');       xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss       xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);       xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);       xml_parse_into_struct($parser, trim($contents), $xml_values);       xml_parser_free($parser);
          if(!$xml_values) return;//Hmm...       //Initializations       $xml_array = array();       $parents = array();       $opened_tags = array();       $arr = array();   
       $current = &$xml_array; //Refference       //Go through the tags.       $repeated_tag_index = array();//Multiple tags with same name will be turned into an array       foreach($xml_values as $data) {           unset($attributes,$value);//Remove existing values, or there will be trouble
           //This command will extract these variables into the foreach scope           // tag(string), type(string), level(int), attributes(array).           extract($data);//We could use the array by itself, but this cooler.           $result = array();           $attributes_data = array();                      if(isset($value)) {               if($priority == 'tag') $result = $value;               else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode           }
           //Set the attributes too.           if(isset($attributes) and $get_attributes) {               foreach($attributes as $attr => $val) {                   if($priority == 'tag') $attributes_data[$attr] = $val;                   else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'               }           }
           //See tag status and do the needed.           if($type == "open") {//The starting of the tag '<tag>'               $parent[$level-1] = &$current;               if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag                   $current[$tag] = $result;                   if($attributes_data) $current[$tag. '_attr'] = $attributes_data;                   $repeated_tag_index[$tag.'_'.$level] = 1;
                   $current = &$current[$tag];               } else { //There was another element with the same tag name                   if(isset($current[$tag][0])) {//If there is a 0th element it is already an array                       $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;                       $repeated_tag_index[$tag.'_'.$level]++;                   } else {//This section will make the value an array if multiple tags with the same name appear together                       $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array                       $repeated_tag_index[$tag.'_'.$level] = 2;
                       if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well                           $current[$tag]['0_attr'] = $current[$tag.'_attr'];                           unset($current[$tag.'_attr']);                       }                   }
                   $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;                   $current = &$current[$tag][$last_item_index];               }           } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'               //See if the key is already taken.               if(!isset($current[$tag])) { //New Key                   $current[$tag] = $result;                   $repeated_tag_index[$tag.'_'.$level] = 1;                   if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
               } else { //If taken, put all things inside a list(array)                   if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
                       // ...push the new element into that array.                       $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;                                              if($priority == 'tag' and $get_attributes and $attributes_data) {                           $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;                       }
                       $repeated_tag_index[$tag.'_'.$level]++;                   } else { //If it is not an array...                       $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value                       $repeated_tag_index[$tag.'_'.$level] = 1;                       if($priority == 'tag' and $get_attributes) {                           if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well                               $current[$tag]['0_attr'] = $current[$tag.'_attr'];                               unset($current[$tag.'_attr']);                           }
                           if($attributes_data) {                               $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;                           }                       }                       $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken                   }               }           } elseif($type == 'close') { //End of tag '</tag>'               $current = &$parent[$level-1];           }       }       return($xml_array);    }

    function html2rgb($color){        if ($color[0] == '#')            $color = substr($color, 1);
        if (strlen($color) == 6)            list($r, $g, $b) = array($color[0].$color[1],                                     $color[2].$color[3],                                     $color[4].$color[5]);        elseif (strlen($color) == 3)            list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);        else            return false;
        $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);        return array($r, $g, $b);    }?>