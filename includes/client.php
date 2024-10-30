<?php

/*
CoinPay.in.th API class
*/

include_once("PaymentDetailsRequest.php");
include_once("helpers.php");
include_once("BitcointhaiSignature.php");

class BitcointhaiApiClient
{
    /** @var string */
    protected $api_id;
    protected $forwarder_url = "https://api.coinpay.co.th/";
    protected $forwarder_test_url = "http://forwarder_api.test/";
    /** @var string */
    protected $error;

    /**
     * @param string $api_id
     * @return void
     */
    public function __construct($api_id)
    {
        $this->api_id = $api_id;
    }

    /**
     * @param string $callback
     * @return bool|PaymentDetailsResponse
     */
    public function getPaymentDetails(PaymentDetailsRequest $request)
    {
        return $this->apiFetch('get_forwarding_address', [
            'bxid'        => $this->api_id,
            'callback'    => $request->callback,
            'amount'      => $request->amount,
            'currency_from'    => $request->currency_from,
            'currency_to' => $request->currency_to,
            'order_label' => $request->order_label
        ]);
    }

    /**
     * @param string[] $address
     * @return PaymentReceived
     */
    public function checkPaymentReceived($addresses)
    {
      return $this->apiFetch("payment_received",[
        'addresses' => $addresses
      ]);
    }

    /**
     * @param string[] $addresses
     * @param $order_id
     * @return SaveOrderId
     */
    public function saveOrderId($addresses, $order_id)
    {
      return $this->apiFetch("save_order_id", [
        "addresses" => $addresses,
        "order_id" =>(string)$order_id,
        ]);
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    protected function apiFetch($endpoint, $params = null)
    {
        $forwarder_url = local_env()
            ? $this->forwarder_test_url
            : $this->forwarder_url;

        $ch = curl_init();


        if (!$ch) {
            $this->error = 'CURL is not installed';
            return false;
        }

        curl_setopt($ch, CURLOPT_URL, $forwarder_url . $endpoint);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Content-Type:application/json",
        ]);

        if ($params) {
            $encoded = json_encode($params);
            if ($encoded === false) {
                echo "Can't encode payload: "; dd($params);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
            curl_setopt($ch, CURLOPT_POST, count($encoded));
        }


        $str = curl_exec($ch);
        curl_close($ch);

        if (!$str) {
            $this->error = curl_error($ch) . '<br>' . print_r(curl_version(), true);
            return false;
        }

        $response = json_decode($str);

        if (!$response) {
            $this->error = 'Invalid JSON format: ' . $str;
            return false;
        }

        if ($response->success !== true) {
            $this->error = $response->error ? $response->error : "Unknown error";
            return false;
        }

        return $response->data;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if (strlen($this->api_id) < 12) {
            return false;
        }
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function validIPN($data)
    {
        return (new BitcointhaiSignature($this->api_id))
            ->check($data);
    }
}

?>
