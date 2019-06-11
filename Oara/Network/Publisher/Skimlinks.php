<?php
namespace Oara\Network\Publisher;



/**
 * Export Class
 *
 * @author     Nastase Lucian Alexandru
 * @category   Skimlinks
 * @copyright  Fubra Limited
 * @version    Release: 1.1
 *
 */
 
 
 class Skimlinks extends \Oara\Network
{
    
    protected $_sitesAllowed = array();
    /**
     *  Account id - user
     * @var string
     */
    private $_account_id = null;
    /**
     * Public API Key
     * @var string
     */
    private $_apikey = null;
    /**
     * Private API Key
     * @var string
     */
    private $_privateapikey = null;    
    
    /**
     * @param $credentials
     */    
    public function login($credentials)
    {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://authentication.skimapis.com/access_token');
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);        
        $post = [
            'client_id' => $credentials['user'],
            'client_secret' => $credentials['password'],
            'grant_type'   => 'client_credentials',
        ];         
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));        
        $curl_results = curl_exec($ch); 
        curl_close($ch);        
        
        $response = json_decode($curl_results);
        $this->_access_token = $response->access_token;
        //print_r($response->access_token);
        
        $this->_client = new \Oara\Curl\Access($credentials);
        $this->_client_id = $credentials['user'];
        $this->_apikey = $credentials['password'];
        $this->_publisher_id = $credentials['publisher_id'];
        $this->_country = $credentials['country'];
    }    
    
    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "Client ID";
        $parameter["required"] = true;
        $parameter["name"] = "User";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Client Secret";
        $parameter["required"] = true;
        $parameter["name"] = "Password";
        $credentials["password"] = $parameter;
        
        $parameter = array();
        $parameter["description"] = "Publisher Id";
        $parameter["required"] = true;
        $parameter["name"] = "Publisher Id";
        $credentials["_publisher_id"] = $parameter;        
        

        return $credentials;
    }    
    
    
    public function getMerchantList()
    {

    $a_merchants = Array();
    $country = $this->_country;
    
    $valuesFromExport = array(
        new \Oara\Curl\Parameter('access_token', $this->_access_token),
        new \Oara\Curl\Parameter('country', $country)
    );
    
    $a_merchants = $this->getMerchantsSkimlinks($valuesFromExport);
    
    return $a_merchants;
    } 
    
    /**
     * @param $a_params Parameters
     * @return array Merchants
     */       
    
    public function getMerchantsSkimlinks($a_params):array {
        
        $a_merchants = Array();
        $limit = 200; //default 25
        $offset = 0;
        $max = 100;
        

       for ($x=1;$x <=$max;$x++) {
            
            $offset = $offset + $limit;
            array_push($a_params,
                new \Oara\Curl\Parameter('limit', $limit),
                new \Oara\Curl\Parameter('offset', $offset)
            );
            
            $n_records = 0;
            $urls = array();        
            
            $urls[] = new \Oara\Curl\Request("https://merchants.skimapis.com/v4/publisher/".$this->_publisher_id."/merchants?", $a_params);
            
            try {
            $exportReport = $this->_client->get($urls);
            $jsonArray = json_decode($exportReport[0], true);

                if(isset($jsonArray["merchants"])){
                    foreach ($jsonArray["merchants"] as $i) {
                        $n_records++;
                        $merchant = Array();

                        $merchant['id'] = $i["id"];
                        $merchant['name'] = $i["name"];
                        $merchant['domains'] = $i["domains"];

                        $a_merchants[] = $merchant;
                    } 
                }  
             
                if($jsonArray['has_more'] === false)    
                break;         
            }
            catch(\Exception $e){
                if ($limit == 1){
                    $offset += $limit;
                }
                else{
                    $limit = (int)($limit / 2);
                }
                $n_records = $limit;
            }            
            
                
        }      
                 
        return $a_merchants;
    }       

    
    /**
     * @param string $idSite
     */
    public function addAllowedSite(string $idSite){
        $this->_sitesAllowed[]=$idSite;
    }  
     
    
}