<pre>
/**
 * Klasa Smeskom
 * do obsługi web-serwisu smeskom.pl
 * Szczegóły i dokumentacja web-serwisu są dostępne tutaj: 
 * @link http://www.smeskom.pl/api
 * 
 * @author Krzysztof Wiśniewski
 * @author Ryszard Jaklewicz
 * @copyright bazy.danych
 * @version 1.01
 */
class Smeskom {
	
	/**
     * Instancja klasy
     */
	protected static $__oInstance;
	
	/**
	 * Login do web-serwisu
	 *
	 * @var string
	 * @access private
	 */
    private $login = '';
    
    /**
     * Hasło do web-serwisu:
     *
     * @var string
     * @access private
     */
    private $password = '';

    /**
     * Adres web-serwisu
     *
     * @var string
     * @access private
     */
    private $address = '';

</pre>
(...)
<pre>
protected function sendRequest($xml_request){
    	
    	$result = true;
    	$arr_response = array();
    	
        if(!$this->isConfigured()){
            $this->addWarning('Nie podano parametrów niezbędnych do komunikacji z web-serwisem.');
            $result = false;    
        }       	
    	
        if($result){
        	/**
        	 * Zapamiętaj wysyłany xml w globalnej zmiennej:
        	 */
        	$this->outgoingXML = $xml_request;
        	
        	/**
        	 * Połącz i wyślij:
        	 */
	        $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->address.':'.$this->port.'/smesx');
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=".urlencode($xml_request));
			$ret = curl_exec($ch);
</pre>