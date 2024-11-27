<?php
namespace Neel\GstApi;

use Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\GuzzleException;

class GstApi{

    const BASE_URI = 'https://api.mastergst.com/';
    
    const CLIENT_ID = '80ca845d-4164-474d-b542-f35f8ff4d767';
    const CLIENT_SECRET = '058c5791-25d1-46ff-9676-aae38340f2c1';

    private $IP_ADDRESS;
    const TXN = 'ad8e8e92d3d84cbb92c09f0626206e52';

    //private $responseFactory;
    private $client;

    public function __construct() {
        $this->IP_ADDRESS = Request::ip();
        //$this->responseFactory = new Response();
        
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'auth' => [self::CLIENT_ID, self::CLIENT_SECRET]
        ]);
    }

    public function ret1aSave($args = []){
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr1a/retsave',['query' => $params,'headers' => $headers,'body' => $body],'PUT');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function ret1aSubmit($args = []){
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr1a/retsubmit',['query' => $params,'headers' => $headers,'body' => $body],'POST');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function ret1aFile($args = []){
        $params = ['email' => 'irriion@gmail.com','pan' => $args['pan'],'evcotp' => $args['evcotp']];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr1a/retevcfile',['query' => $params,'headers' => $headers,'body' => $body],'POST');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function retSave($args = []){
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr1/retsave',['query' => $params,'headers' => $headers,'body' => $body],'PUT');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function retSubmit($args = []){
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr1/retsubmit',['query' => $params,'headers' => $headers,'body' => $body],'POST');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function retFile($args = []){
        $params = ['email' => 'irriion@gmail.com','pan' => $args['pan'],'evcotp' => $args['evcotp']];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr1/retevcfile',['query' => $params,'headers' => $headers,'body' => $body],'POST');
        $data = $response->getBody()->getContents();
        return $data;
    }
    public function searchTaxpayer($params){
        return $this->doRequest('public/search',['query' => $params]);
    }

    public function otpRequest($args = []){
        //dd($args);
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
        ];

        $response = $this->doRequest('authentication/otprequest',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function otpVerify($args = []){
        $params = ['email' => 'irriion@gmail.com','otp' => $args['otp']];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('authentication/authtoken',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

  
    public function sendEvc($args = []){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => $args['gstin'],
            'pan' => $args['pan'],
            'form_type' => $args['form_type'],
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('authentication/otpforevc',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function refreshToken(){
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('authentication/refreshtoken',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getViewTrackReturns(){
        $params = [
            'gstin' => '27AAGFI0474G1ZG',
            'returnperiod' => '042020',
            'type' => 'R1',
            'email' => 'irriion@gmail.com',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr/rettrack',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getGSTR1ASummary($args = []){
        $params = [
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
            'email' => 'irriion@gmail.com',
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr1a/retsum',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function getGSTR1Summary($args = []){
        $params = [
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
            'email' => 'irriion@gmail.com',
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr1/retsum',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function getGSTR3bSummary($args = []){
        $params = [
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
            'email' => 'irriion@gmail.com',
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr3b/retsum',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function getGSTR3bAutolia($args = []){
        $params = [
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
            'email' => 'irriion@gmail.com',
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr3b/autoliab',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function saveGSTR3b($args = []){
        
        $params = [
            'email' => 'irriion@gmail.com',
        ];

        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr3b/retsave',['query' => $params,'headers' => $headers,'body' => $body], 'PUT');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function saveGSTR3bOffset($args = []){
        $params = [
            'email' => 'irriion@gmail.com',
        ];
        
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];

        $body = $args['body'];
        $response = $this->doRequest('gstr3b/retoffset',['query' => $params,'headers' => $headers,'body' => $body], 'PUT');
        $data = $response->getBody()->getContents();

        return $data;
    }

    public function retSubmit3B($args = []){
        
        $params = ['email' => 'irriion@gmail.com'];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];

        $response = $this->doRequest('gstr3b/retsubmit',['query' => $params,'headers' => $headers,'body' => $body],'POST');
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function retFile3B($args = []){
        $params = ['email' => 'irriion@gmail.com','pan' => $args['pan'],'evcotp' => $args['evcotp']];
        $headers = [
            'gstin' => $args['gstin'],
            'ret_period' => $args['ret_period'],
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $body = $args['body'];
        $response = $this->doRequest('gstr3b/retevcfile',['query' => $params,'headers' => $headers,'body' => $body],'POST');
        $data = $response->getBody()->getContents();
        return $data;
    }

    // Action_required = Y, fetches the invoices where tax payer need to take action (Accept/Reject)
    // Action_required = N, Already accepted or uploaded invoices can be fetched by using action_required=N filter
    
    // TYPE
    // b2b
    // b2ba
    // b2cl
    // b2cla
    // b2cs
    // b2csa
    // cdnr
    // cdnra
    // cdnur
    // cdnura
    // exp
    // expa
    // nil
    // hsnsum

    // NOT
    // at
    // ata
    // txp
    // isd //txpda
    // dociss

    public function getInvoices($args , $type){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr1/'.$type,['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function get1AInvoices($args , $type){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr1a/'.$type,['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function get2AInvoices($args , $type){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => $args['gstin'],
            'retperiod' => $args['retperiod'],
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr2a/'.$type,['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function getReturnStatus($args = []){
        $params = [
            'gstin' => $args['gstin'],
            'returnperiod' => $args['returnperiod'],
            'refid' => $args['refid'],
            'email' => 'irriion@gmail.com',
        ];
        $headers = [
            'gst_username' => $args['gst_username'],
            'state_cd' => str_pad($args['state_cd'],2,'0',STR_PAD_LEFT),
            'ip_address' => $this->IP_ADDRESS,
            'txn' => $args['txn'],
        ];
        $response = $this->doRequest('gstr/retstatus',['query' => $params,'headers' => $headers]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    public function saveGSTR1Data(){
        $body = '{
  "gstin": "27AAGFI0474G1ZG",
  "fp": "052020",
  "b2b": [
    {
      "ctin": "20AAFCG4392P1ZB",
      "inv": [
        {
          "inum": "S008400",
          "idt": "10-05-2020",
          "val": 729248.16,
          "pos": "06",
          "rchrg": "N",
          "inv_typ": "R",
          "diff_percent": 0.65,
          "itms": [
            {
              "num": 1,
              "itm_det": {
                "rt": 5,
                "txval": 10000,
                "iamt": 325,
                "csamt": 500
              }
            }
          ]
        },
        {
          "inum": "S008401",
          "idt": "11-05-2020",
          "val": 729248.16,
          "pos": "06",
          "rchrg": "N",
          "inv_typ": "R",
          "diff_percent": 0.65,
          "itms": [
            {
              "num": 1,
              "itm_det": {
                "rt": 5,
                "txval": 10000,
                "iamt": 325,
                "csamt": 500
              }
            }
          ]
        }
      ]
    }
  ]
}';
        $params = [
            'body' => $body,
            'query' => ['email' => 'irriion@gmail.com'],
            'headers' => [
                'gstin' => '27AAGFI0474G1ZG',
                'ret_period' => '052020',
                'gst_username' => 'aagfi0474g1',
                'state_cd' => '27',
                'ip_address' => $this->IP_ADDRESS,
                'content-type' => 'application/json',
                'txn' => self::TXN,
            ],
        ];

        try {
            $response = $this->client->put(
                'gstr1/retsave',
                $params
            );
            
        } catch (\Exception $e) {
            echo $e->getMessage();
        }


        $data = $response->getBody();
        echo $data;
    }

    public function getB2BInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/b2b',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getB2BAInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/b2ba',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getB2CLInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/b2cl',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getB2CLAInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/b2cla',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getB2CSInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/b2cs',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getB2CSAInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/b2csa',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getCDNRInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/cdnr',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getCDNRAInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/cdnra',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getCDNURInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/cdnur',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getCDNURAInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/cdnura',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getEXPInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/exp',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getEXPAInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/expa',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getNILInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/nil',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getHSNInvoices(){
        $params = [
            'email' => 'irriion@gmail.com',
            'gstin' => '27AAGFI0474G1ZG',
            'retperiod' => '052020',
        ];
        $headers = [
            'gst_username' => 'aagfi0474g1',
            'state_cd' => '27',
            'ip_address' => $this->IP_ADDRESS,
            'txn' => self::TXN,
        ];
        $response = $this->doRequest('gstr1/hsnsum',['query' => $params,'headers' => $headers]);

        $data = $response->getBody();
        echo $data;
    }

    public function getRetTrack($args){
        $params = [
            'gstin' => $args['gstin'],
            'fy' => $args['fy'],
            // 'type' => $args['type'],
            'email' => 'irriion@gmail.com'
        ];

        $response = $this->doRequest('public/rettrack',['query' => $params]);
        $data = $response->getBody()->getContents();
        return $data;
    }

    private function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = 'GET'
    ): Response {
        try {
            $response = $this->client->request($requestMethod,$uriEndpoint,$params);
        } catch (GuzzleException $e) {
            \Log::error('GST API Guzzle Exception ERROR : '.$e->getMessage());
            $response = new Response($e->getCode(),[],$e->getMessage());
        } catch (\Exception $e) {
            \Log::error('GST API ERROR : '.$e->getMessage());
            $response = new Response($e->getCode(),[],$e->getMessage());
        }
        return $response;
    }
}