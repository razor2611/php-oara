<?php
namespace Oara\Network\Publisher;
    /**
     * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
     * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
     *
     * Copyright (C) 2016  Fubra Limited
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Affero General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or any later version.
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU Affero General Public License for more details.
     * You should have received a copy of the GNU Affero General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     * Contact
     * ------------
     * Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
     **/
/**
 * Api Class
 *
 * @author Carlos Morillo Merino
 * @category VigLink
 * @copyright Fubra Limited
 * @version Release: 01.00
 *
 *
 */
class VigLink extends \Oara\Network
{
    private $_apiKey = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_apiKey = $credentials["apiKey"];
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;
               
        $now = new \DateTime();
        $apiURL = "https://www.viglink.com/service/v1/cuidRevenue?lastDate={$now->format("Y/m/d")}&period=month&secret={$this->_apiKey}";
        $response = self::call($apiURL);
        if (\is_array($response)) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Api Key";
        $parameter["required"] = true;
        $parameter["name"] = "ApiKey";
        $credentials["apiKey"] = $parameter;

        return $credentials;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();
        $n_records = 0;
 
        $apiURL = "https://publishers.viglink.com/api/merchant/search"; 
        $response = self::call($apiURL);
        $total_pages = $response['totalPages'];
       
        if(isset($response["merchants"])){
                    foreach ($response["merchants"] as $i) {
                        $n_records++;
                        $merchant = Array();
        
                        $merchant['id'] = $i["id"];
                        $merchant['name'] = $i["name"];
                        $merchant['domains'] = $i["domains"];
                        $merchants[] = $merchant;
                    }                     
        }
        
        for ($x = 2; $x <= $total_pages; $x++) {
                
                $response = self::call($apiURL."?page=".$x);
        
                if(isset($response["merchants"])){
                            foreach ($response["merchants"] as $i) {
                                $n_records++;
                                $merchant = Array();

                                $merchant['id'] = $i["id"];
                                $merchant['name'] = $i["name"];
                                $merchant['domains'] = $i["domains"];
                                $merchants[] = $merchant;
                            }                     
                }

        }
        
        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();
        $apiURL = "https://www.viglink.com/service/v1/cuidRevenue?lastDate={$dEndDate->format("Y/m/d")}&period=month&secret={$this->_apiKey}";
        $response = self::call($apiURL);
        foreach ($response as $date => $transactionApi) {
            foreach ($transactionApi[1] as $sale) {
                if ($sale != 0) {
                    $transaction = Array();
                    $transaction['merchantId'] = "1";
                    $transactionDate = \DateTime::createFromFormat("Y/m/d H:i:s", $date. " 00:00:00");
                    $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    $transaction['amount'] = $sale;
                    $transaction['commission'] = $sale;
                    $totalTransactions[] = $transaction;
                }
            }
        }
        return $totalTransactions;
    }

    private function call($apiUrl)
    {

        // Initiate the REST call via curl
        $ch = \curl_init($apiUrl);
        \curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0");
        \curl_setopt($ch, CURLOPT_FAILONERROR, true);
        \curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        \curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($ch, CURLOPT_VERBOSE, false);
        // Set the HTTP method to GET
        \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        // Don't return headers
        \curl_setopt($ch, CURLOPT_HEADER, false);
        // Return data after call is made
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // set the authorization header
        if($this->_apiKey)
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: secret '.$this->_apiKey));
        
        
        // Execute the REST call
        $response = \curl_exec($ch);
        $array = \json_decode($response, true);
        // Close the connection
        \curl_close($ch);
        return $array;
    }
}