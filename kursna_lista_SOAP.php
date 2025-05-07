<?php
/**
 * Created by PhpStorm.
 * User: x
 * Date: 26.4.25.
 * Time: 11.45
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $client = new SoapClient('https://webservices.nbs.rs/CommunicationOfficeService1_0/ExchangeRateXmlService.asmx?WSDL', [
        'trace' => 1,
        'exceptions' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE
    ]);
    $client->decode_utf8 = 0;
    // OVDE ispravno UserName (sa velikim N)
    $auth_params = [  // kredencijali dobijeni kod registrovanja usluge NBS
        'UserName' => '**********',
        'Password' => '**********',
        'LicenceID' => '**********'
    ];

    $header = new SoapHeader(
        'http://communicationoffice.nbs.rs', // namespace iz WSDL-a
        'AuthenticationHeader',
        $auth_params,
        false
    );

    $client->__setSoapHeaders($header);

    // Poziv metode

// 1. Preuzmi odgovor

    $result = $client->GetCurrentExchangeRate(['exchangeRateListTypeID' => 1]);  // 1,2,3

/*    print_r($result);

    stdClass Object
    (
        [GetCurrentExchangeRateResult] => <ExchangeRateDataSet>
  <ExchangeRate>
    <ExchangeRateListNumber>79</ExchangeRateListNumber>
    <Date>30.04.2025</Date>
    <CreateDate>30.04.2025</CreateDate>
    <DateTo>31.12.4172</DateTo>
    <ExchangeRateListTypeID>1</ExchangeRateListTypeID>
    <CurrencyGroupID>2</CurrencyGroupID>
    <CurrencyCode>978</CurrencyCode>
    <CurrencyCodeNumChar>978</CurrencyCodeNumChar>
    <CurrencyCodeAlfaChar>EUR</CurrencyCodeAlfaChar>
    <CurrencyNameSerCyrl>Евро</CurrencyNameSerCyrl>
    <CurrencyNameSerLat>Evro</CurrencyNameSerLat>
    <CurrencyNameEng>Euro</CurrencyNameEng>
    <CountryNameSerCyrl>ЕМУ</CountryNameSerCyrl>
    <CountryNameSerLat>EMU</CountryNameSerLat>
    <CountryNameEng>EMU</CountryNameEng>
    <Unit>1</Unit>
    <BuyingRate>116.8408</BuyingRate>
    <MiddleRate>0.0000</MiddleRate>
    <SellingRate>117.5440</SellingRate>
    <FixingRate>0.000000</FixingRate>
  </ExchangeRate>
  <ExchangeRate> ...*/

    if ($result) {
        $xml = simplexml_load_string($result->GetCurrentExchangeRateResult);
//        $xml = $result->GetCurrentExchangeRateResult;

// Tražene valute
        $servername = "localhost";
        $username = "********";
        $password = "********";
        $dbname = "**********";

// Konektovanje na bazu
        $conn = mysqli_connect($servername, $username, $password, $dbname);
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // 1. Učitaj sve valute iz oc_currency
        $sql = "SELECT code FROM oc_currency";
        $result = mysqli_query($conn, $sql);

        $targetCurrencies = [];
//        $targetCurrencies = ['EUR', 'GBP', 'USD'];
        while ($row = mysqli_fetch_assoc($result)) {
            $targetCurrencies[] = $row['code'];
        }

        // Parsiranje kursne liste i filtriranje po potrebnim valutama
        $found = [];

        foreach ($xml->ExchangeRate as $rate) {
            $code = (string) $rate->CurrencyCodeAlfaChar;
            if (in_array($code, $targetCurrencies)) {
                $unit = (int) $rate->Unit;
                $value = (float) str_replace(',', '.', (string) $rate->SellingRate);
                $found[$code] = [
                    'rate' => $value,
                    'unit' => $unit
                ];
            }
        }

/*        echo "<pre>";
        print_r($found);
        echo "</pre>";*/

/*Array
(
    [EUR] => Array
        (
            [rate] => 117.544
            [unit] => 1
        )

    [GBP] => Array
        (
            [rate] => 138.3685
            [unit] => 1
        )

    [USD] => Array
        (
            [rate] => 103.3444
            [unit] => 1
        )

)*/

        // 3. Update
        foreach ($found as $code => $data) {
            $rate = $data['rate'];
            $unit = $data['unit'];
            if ($rate > 0 && $unit > 0) {
                $value = round((1 / $rate) * $unit, 8);
//                echo "$code => $value\n";
                if ((float)$value) {
                    $sql = "UPDATE oc_currency SET value = '" . (float)$value . "', date_modified = '" . date('Y-m-d H:i:s') . "' WHERE code = '" . $code . "'";
                    $update = mysqli_query($conn, $sql);
                }
            }
        }
        if ($update)
            echo 'sve OK ' . date('Y-m-d H:i:s');
        else
            echo 'NOK ' . date('Y-m-d H:i:s');
        echo "<br>" . "Valute su uspešno ažurirane:\n";
// Provera rezultata

        $sql = "SELECT * FROM oc_currency";
        $result = mysqli_query($conn, $sql);
        $kurs = 'prodajni';
        if (mysqli_num_rows($result) > 0) {
            echo ' *** ' . $kurs .' *** ' . "<br>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "id: " . $row["code"] . " - value: " . $row["value"] . " - date_modified: " . $row["date_modified"] . PHP_EOL;
            }
        }

// Zatvaranje konekcije
        mysqli_close($conn);
}
    else {
        echo "Nema podataka.";
    }


} catch (SoapFault $fault) {
    echo "SOAP Greška:<br/>";
    echo "Poruka: " . $fault->getMessage() . "<br/><br/>";
    echo "Poslati zahtev (RAW Request):<br/>";
    echo htmlentities($client->__getLastRequest()) . "<br/><br/>";
    echo "Primljeni odgovor (RAW Response):<br/>";
    echo htmlentities($client->__getLastResponse()) . "<br/>";
}
