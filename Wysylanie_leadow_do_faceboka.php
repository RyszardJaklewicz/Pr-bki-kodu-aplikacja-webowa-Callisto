<pre>
	$SQL = "SELECT
              m.id,
              m.cli_first,
              m.cli_first_2,
              m.cli_last,
              m.cli_pesel,
              m.cli_do,
              m.cli_address,
              m.cli_house_no,
              m.cli_flat_no,
              m.cli_postal_code,
              m.cli_city, 
              m.cli_mobile_home,
              m.cli_email,
              m.kredytok_id,
              m.loanme_id,
              m.proficredit_id,
              m.cr_sales_channel
                        FROM multiwniosek m
                        WHERE (m.kredytok_id IS NULL || m.loanme_id IS NULL || m.proficredit_id IS NULL) AND m.cr_sales_channel='lead' AND m.cr_insert_date > '".$data."'";

$go = @mysql_query($SQL);
if(!$go){
    @mail("jaklewicz.r@gmail.com", "Błąd - automatyczne wysyłanie leadów. Nie powiódł się odczyt danych multiwniosku.", 
	"Błąd - automatyczne wysyłanie leadów. Nie powiódł się odczyt danych multiwniosku.\n\n");
    die('Nie powiódł się odczyt danych multiwniosku. Koniec pracy.');
}else{
    print("\n\n");
    $licznik=0;
    while($ass = mysql_fetch_assoc($go)){
        $licznik++;
        print("\nlicznik: ".$licznik."\n");
        
        $array_zawartosc_leada = array(
            'cli_first' 		=> $ass['cli_first'],
            'cli_last' 			=> $ass['cli_last'],
            'cli_pesel' 		=> $ass['cli_pesel'],
            'cli_postal_code' 	=> $ass['cli_postal_code'],
            'cli_email' 		=> (!empty($ass['cli_email'])?$ass['cli_email']:'brak@maila.com'),
            'cli_mobile_home' 	=> (!empty($ass['cli_mobile_home'])?$ass['cli_mobile_home']:'123456789')
        );
		
</pre>