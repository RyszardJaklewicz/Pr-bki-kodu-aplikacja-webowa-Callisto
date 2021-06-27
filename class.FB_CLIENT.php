<?php 
/**
 * Klasa do pobierania leadów z FB.
 *
 * @author Krzysztof Wiśniewski
 * @author Ryszard Jaklewicz
 * @copyright bazy.danych All Rights Reserved
 * @version 1.0
 */
class FB_CLIENT {	
	/**
	 * Kolekcja ostrzeżeń o błędach
	 *
	 * @var array
	 * @access private
	 */
	private $warnings = array();
	
	/**
	 * Dodaje ostrzeżenie do kolekcji
	 *
	 * @param string $string  Treść ostrzeżenia
	 * @access public
	*/
	public function addWarning($string) {
		$this->warnings[] = $string;
	}

	/**
	 * Parsuje otrzymany JSON i zwraca tablicę lub false + komunikat błędu.
	 *
	 * @param string $json			String zawierający JSON
	 *
	 * @return mixed
	 * @access private
	 */
	private function parsujJSON($json){
	    
	    if($json == null || empty($json)){
	        $this->addWarning("Nie otrzymano odpowiedzi.");
	        return false;
	    }
	    $arr = json_decode($json, true);
	    if(json_last_error()!=JSON_ERROR_NONE){
	        switch (json_last_error()){
	            case JSON_ERROR_DEPTH:
	                $this->addWarning('Błąd `Message`: przekroczono maksymalny poziom zagnieżdżenia danych.');
	                return false;
	                break;
	            case JSON_ERROR_CTRL_CHAR:
	                $this->addWarning('Błąd znaku kontrolnego. Prawdopodobnie został nieprawidłowo zakodowany.');
	                return false;
	                break;
	            case JSON_ERROR_STATE_MISMATCH:
	                $this->addWarning('Niepoprawny składniowo lub zniekształcony JSON.');
	                return false;
	                break;
	            case JSON_ERROR_SYNTAX:
	                $this->addWarning('Błąd składni.');
	                return false;
	                break;
	            case JSON_ERROR_UTF8:
	                $this->addWarning('Nieprawidłowe znaki UTF-8. Możliwe, że nieprawidłowo zakodowane.');
	                return false;
	                break;
	        }
	    }
	    return $arr;
	}
	
	
	public function pobierzIZapiszLeady($print = true){
	    
	    $SQL = "SELECT
					u.IDUzytkownika,
					u.IDPlacowki,
					a.Wartosc AS facebook_form_id
				FROM ekf_Uzytkownicy u
				INNER JOIN ekf_Uzytkownicy_Atrybuty a ON a.IDUzytkownika = u.IDUzytkownika
				INNER JOIN dic_Uzytkownicy_Atrybuty d ON d.id = a.IDAtrybutu
				WHERE d.kod = 'FACEBOOK_FORM_ID'
			   ";
	    $go = @mysql_query($SQL);
	    
	    if(!$go){
	        $this->addWarning('Nie powiodło się zapytanie o formularze: '.mysql_error());
	        return false;
	    } 
	    
	    if($print) print "\n\nZnaleziono formularzy: ".mysql_num_rows($go)."\n\n";
	    
	    $date = new DateTime();
	    $date->modify('-'.constant("CO_ILE_URUCHAMIANIE_MINUTY").' minute');
	    $timestamp = $date->format('U');
	    
	    while ($ass = mysql_fetch_assoc($go)){
	     
	        /**
	         * Moment wysłania komunikacji:
	         */
	        $date_sent = date('Y-m-d H:i:s');
	     
	        /**
	         * Można wysłac bez przekazywania daty - pominąc element filtering
	         */
    	    $curl = curl_init();
    	    $request = "https://graph.facebook.com/v".constant("FACEBOOK_VERSION")."/".$ass['facebook_form_id']."/leads";
    	    $request .= "?access_token=".constant("FACEBOOK_PAGE_ACCESS_TOKEN")."&filtering=[{'field':'time_created','operator':'GREATER_THAN','value':$timestamp}]";
    	   
    	    curl_setopt($curl, CURLOPT_URL, $request);
    	    curl_setopt($curl, CURLOPT_HEADER, 0);
    	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    	    $response = curl_exec($curl);
    	    $info = curl_getinfo($curl);
    	    
    	    $curl_error = curl_error($curl);
    	    $curl_getinfo = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    	 
    	    /**
    	     * Moment odebrania komunikacji:
    	     */
    	    $date_received = date('Y-m-d H:i:s');
    	    
    	    if(curl_errno($curl)!=0){
    	        /**
    	         * Wystąpił błąd CURL'a:
    	         */
    	        if($print) print " - błąd CURL! ".curl_error($curl);
    	        $this->addWarning('Błąd pobierania Lead z serwisy Facebook: '.curl_error($curl).'. Kod odpowiedzi HTTP: '.$info['http_code'].'.');
    	        curl_close($curl);
    	 
    	        $this->zapiszLog($request, 'Błąd pobierania Lead z serwisy Facebook: '.curl_error($curl).'. Kod odpowiedzi HTTP: '.$info['http_code'].'.', $date_sent, $date_received);
    	    } elseif ($info['http_code'] != 200){
    	        if($print) print " - kod odpowiedzi HTTP: ".$info['http_code']."\n";
    	        if($print) print "url = ".$url."\n";
    	        $this->addWarning('Kod odpowiedzi HTTP: '.$info['http_code'].'.');
    	        if($print) print('Wystąpił błąd odpowiedź: '.$response);
    	        if(!empty($response)) {
    	            $arr_ret = $this->parsujJSON($response);
    	        }
    	        curl_close($curl);
    	     
    	        $this->zapiszLog($request, $response, $date_sent, $date_received);
    	    } elseif ($info['http_code'] == 200){
    	        if(!empty($response)) {
    	            $this->zapiszLog($request, $response, $date_sent, $date_received);
    	            $arr_ret = $this->parsujJSON($response);
    	        }else{
    	            $this->addWarning('Odpowiedź jest pusta.');
    	            $this->zapiszLog($request, 'Odpowiedź jest pusta.', $date_sent, $date_received);
    	        }
    	    }
    	    
    	    /**
    	     * Zamknij połączenie:
    	     */
    	    curl_close($curl);
    	 
    	    /**
    	     * Wykonujemy INSERT do tabeli multiwniosek
    	     */
    	    $i=0;
    	    if(!empty($arr_ret)) {
        	    foreach ($arr_ret['data'] as $k => $v) {
        	        $i++;
        	        $arr_leady_z_formularzy[$ass['facebook_form_id']."@".$i] = $v['field_data']['0']['name']."/".$v['field_data']['0']['values']['0']."#".$v['field_data']['1']['name']."/".$v['field_data']['1']['values']['0']."#".$v['field_data']['2']['name']."/".$v['field_data']['2']['values']['0'];
        	    }
    	    }else{
    	        $this->addWarning('Błąd parsowania JSON z Facebook');
    	        return false;
    	    }
    	
        	/**
        	* Kazde przejscie tej petli to 
        	* Dla każdego elementu tablicy wykonaj zapis do bazy (multiwnioski):
        	*/
    	    
    	    if(!empty($v['field_data']['0']['values']['0']) && !empty($v['field_data']['1']['values']['0']) && !empty($v['field_data']['2']['values']['0'])){
    	
            	$SQL = "INSERT INTO multiwniosek (
                            	     cli_first,
                            	     cli_last,
                            	     cli_email,
                                     cr_sales_channel,
                                     cr_insert_date
                            	     ) VALUES (
                                	     '".$v['field_data']['0']['values']['0']."',
                                	     '".$v['field_data']['1']['values']['0']."',
                                	     '".$v['field_data']['2']['values']['0']."',
                                         'lead',
                                         '".date('Y-m-d H:i:s')."'
                            	     )";
            	     
            	$go_insert = @mysql_query($SQL);
            	if(!$go_insert){
                	$this->addWarning('Nie udało się zapisać leadów do multiwniosku');
            	}
	       }
	    }
	    return true;
	}
	
	/**
	 * Zwraca tablicę komunikatów o błędach.
	 *
	 * @return array
	 * @access public
	 */
	public function returnWarnings(){
		return $this->warnings;
	}

	/**
	 * Zapisuje komunikację do bazy danych.
	 *
	
	 * @param string $request			Wysłany request
	 * @param string $response			Zwrócony JSON
	 * @param string $date_sent			Moment wysłania
	 * @param string $date_received		Moment odebrania
	 *
	 * @access public
	 */
	public function zapiszLog($request, $response, $date_sent, $date_received){
	  
	    /**
	     * Zapis komunikacji do bazy:
	     */
	    @mysql_query("INSERT INTO facebook_ws_messages (
			
			DateSent,
			DateReceived,
			WSRequest,
			WSResponse,
			RemoteIP

		  ) VALUES (
	
			'".mysql_real_escape_string($date_sent)."',
			'".mysql_real_escape_string($date_received)."',
			'".mysql_real_escape_string($request)."',
			'".mysql_real_escape_string($response)."',
			'".$_SERVER['REMOTE_ADDR']."'
		  )");
	    
	}
	
}
?>