<?php

/**
 * Class Shroff_Model_Shroff
 * Converts currencies by using cbr.ru
 * @author aleksandrzen@gmail.com
 */
final class Shroff_Model_Shroff{
    // Possible currencies (ISO 4217)
    private static $possibleCurrencies = array('AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN','BAM','BBD','BDT','BGN','BHD','BIF','BMD','BND','BOB','BRL','BSD','BTN','BWP','BYR','BZD','CAD','CDF','CHF','CLP','CNY','COP','CRC','CUC','CUP','CVE','CZK','DJF','DKK','DOP','DZD','EGP','ERN','ETB','EUR','FJD','FKP','GBP','GEL','GGP','GHS','GIP','GMD','GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS','IMP','INR','IQD','IRR','ISK','JEP','JMD','JOD','JPY','KES','KGS','KHR','KMF','KPW','KRW','KWD','KYD','KZT','LAK','LBP','LKR','LRD','LSL','LTL','LYD','MAD','MDL','MGA','MKD','MMK','MNT','MOP','MRO','MUR','MVR','MWK','MXN','MYR','MZN','NAD','NGN','NIO','NOK','NPR','NZD','OMR','PAB','PEN','PGK','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SDG','SEK','SGD','SHP','SLL','SOS','SPL','SRD','STD','SVC','SYP','SZL','THB','TJS','TMT','TND','TOP','TRY','TTD','TVD','TWD','TZS','UAH','UGX','USD','UYU','UZS','VEF','VND','VUV','WST','XAF','XCD','XDR','XOF','XPF','YER','ZAR','ZMW','ZWD');
    /**
     * Higher-order function for validation post parameters.
     * @param string $type type of validation
     * @return callable
     */
    public static function getValidator($type){
        $possibleCurrencies = self::$possibleCurrencies;
        return function($value) use ($type, $possibleCurrencies){
            $result = false;
            switch ($type){
                case 'amount':
                    $result = filter_var($value, FILTER_VALIDATE_INT) || filter_var($value, FILTER_VALIDATE_FLOAT);
                    break;
                case 'currency':
                    // no need use regexp in this case, I can use white list based on ISO 4217, this is faster and cheaper
                    $result = in_array($value, $possibleCurrencies);
                    break;
            }
            return $result;
        };
    }
    /**
     * @param Application_Model_SccRedis $redis
     * @return callable
     */
    public static function getWorker(Application_Model_SccRedis $redis){
        // Get exchange rate by $currency
        return function($currency) use ($redis){
            return $redis->get('rates:'.$currency) ?: static::askCB($currency,$redis);
        };
    }
    /**
     * Delete all exchange rates
     */
    public function flushRates($redis){
        $keys = $redis->getKeysByPattern('rates:*');
        foreach ($keys as $key){
            $this->delete($key);
        }
    }
    /**
     * Get information about exchange rates from cbr.ru
     * @param $currency
     * @param $redisForCash
     * @return bool|float
     */
    protected function askCB($currency,$redisForCash){
        $rate = false;
        if ($currency != 'RUB'){
            $url = "http://www.cbr.ru/scripts/XML_daily.asp?date_req=" . date("d/m/Y");
            $xml = @simplexml_load_file($url);
            if ($xml) {
                foreach ($xml->Valute as $valute) {
                    if ($valute->CharCode == $currency){
                        $value = (float) str_replace(",", ".", (string) $valute->Value);
                        $nominal = (int) $valute->Nominal;
                        $rate = $value / $nominal;
                        // cashing exchange rate to 24 hours
                        $redisForCash->setAndExpire('rates:' . $currency, $rate, 1*60*60*24);
                        break;
                    }
                }
            }
        }else{
            $rate = 1;
        }
        return $rate;
    }
}