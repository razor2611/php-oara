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
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   SportCoverDirect
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class SportCoverDirect extends \Oara\Network
{
    /**
     * Export client.
     * @var \Oara\Curl\Access
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $cartrawler
     * @return Tv_Export
     */
    public function login($credentials)
    {

        $user = $credentials['user'];
        $password = $credentials['password'];

        $valuesLogin = array(
            new \Oara\Curl\Parameter('Username', $user),
            new \Oara\Curl\Parameter('Password', $password),
        );

        $dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

        if (!\Oara\Utilities::mkdir_recursive($dir, 0777)) {
            throw new Exception ('Problem creating folder in Access');
        }
        $cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
        unlink($cookies);
        $this->_options = array(
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_COOKIEJAR => $cookies,
            CURLOPT_COOKIEFILE => $cookies,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: es,en-us;q=0.7,en;q=0.3', 'Accept-Encoding: gzip, deflate', 'Connection: keep-alive', 'Cache-Control: max-age=0'),
            CURLOPT_ENCODING => "gzip",
            CURLOPT_VERBOSE => false
        );
        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, "https://www.sportscoverdirect.com/promoters/account/login");
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);

        $dom = new Zend_Dom_Query($html);
        $hidden = $dom->query('input[type="hidden"]');

        foreach ($hidden as $values) {
            $valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
        }
        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, "https://www.sportscoverdirect.com/promoters/account/login");
        $options [CURLOPT_POST] = true;
        $arg = array();
        foreach ($valuesLogin as $parameter) {
            $arg [] = $parameter->getKey() . '=' . urlencode($parameter->getValue());
        }
        $options [CURLOPT_POSTFIELDS] = implode('&', $arg);
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["user"]["description"] = "User Log in";
        $parameter["user"]["required"] = true;
        $credentials[] = $parameter;

        $parameter = array();
        $parameter["password"]["description"] = "Password to Log in";
        $parameter["password"]["required"] = true;
        $credentials[] = $parameter;

        return $credentials;
    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, 'https://www.sportscoverdirect.com/promoters/account/update');
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);

        if (preg_match("/You're logged in as/", $html, $matches)) {
            $connection = true;
        }

        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = Array();
        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'SportCoverDirect';
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();

        $rch = curl_init();
        $options = $this->_options;
        curl_setopt($rch, CURLOPT_URL, 'https://www.sportscoverdirect.com/promoters/earn');
        curl_setopt_array($rch, $options);
        $html = curl_exec($rch);
        curl_close($rch);

        $dom = new Zend_Dom_Query($html);
        $results = $dom->query('.performance');
        if (count($results) > 0) {
            $exportData = \Oara\Utilities::htmlToCsv(\Oara\Utilities::DOMinnerHTML($results->current()));
            $num = count($exportData) - 1; //the last row is show-more show-less
            for ($i = 1; $i < $num; $i++) {
                $overviewExportArray = str_getcsv($exportData[$i], ";");

                $transaction = Array();

                $transaction['merchantId'] = 1;

                $date = new \DateTime($overviewExportArray[0], "dd/MM/yyyy");
                $transaction['date'] = $date->format!("yyyy-MM-dd HH:mm:ss");
                $transaction ['amount'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $overviewExportArray[1]));
                $transaction['commission'] = \Oara\Utilities::parseDouble(preg_replace('/[^0-9\.,]/', "", $overviewExportArray[1]));
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;

                $totalTransactions[] = $transaction;
            }
        }

        return $totalTransactions;

    }
}
