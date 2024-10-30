<?php

class BitcointhaiWoocommerce extends WC_Payment_Gateway
{
    /** @var string */
    public $api_id;
    /** @var BitcointhaiApiClient */
    public $api;
    /** @var string */
    public $notify_url;
    /** @var WC_Session|WC_Session_Handler */
    protected $session;
    /** @var WC_Cart */
    protected $cart;
    protected $cryptocurrencies;

    public function __construct()
    {
        $this->id            = 'coinpay';
        $this->medthod_title = __('BX CoinPay', 'woocommerce');
        $this->has_fields    = true;

        $this->init_form_fields();

        $this->init_settings();

        $this->title       = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->api_id      = $this->settings['api_id'];
        $this->cryptocurrencies = $this->settings['cryptocurrencies'];

        $this->notify_url = add_query_arg('wc-api', 'WC_CoinPay', home_url('/'));

        $this->session = WC()->session;
        $this->cart    = WC()->cart;

        $this->api = new BitcointhaiApiClient($this->api_id);


        // Payment listener/API hook
        add_action('woocommerce_api_wc_coinpay', [$this, 'check_ipn_response']);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

    }

    public function admin_options()
    {
        ?>
        <h3><?php _e('BX CoinPay', 'woocommerce'); ?></h3>
        <p><?php _e('Accept BX CoinPay payments with your CoinPay.in.th merchant account', 'woocommerce'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table> <?php
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'       => __('Enable Coinpay', 'woocommerce'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ],
            'title'   => [
                'title'       => __('Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Payment method title that the customer will see on your website.', 'woocommerce'),
                'default'     => __('BX CoinPay', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'api_id'  => [
                'title'       => __('API Key', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Get your API Key from <a href="https://coinpay.in.th/api-access/" target="_blank">https://coinpay.in.th/api-access/</a>', 'woocommerce')
            ],
            'cryptocurrencies' => [
              'title'         => __('List of Crypto Currencies'),
              'type'          => 'text',
              'description'   => __('Example: BTC, BCH, DAS, DOG, LTC', 'woocommerce'),
              ]
        ];
    }

    public function is_available()
    {
        if ($this->settings['enabled'] != 'yes') {
            return false;
        }
        if (!$this->api->valid()) {
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    public function payment_fields()
    {
        $request = new PaymentDetailsRequest(
            $this->notify_url,
            $this->cart->total,
            get_woocommerce_currency(),
            $this->cryptocurrencies,
            "Payment for order on " . get_bloginfo('name')
        );

        if ($this->paymentDetailsMustBeRefreshed($request)) {
            $payment_details = $this->api->getPaymentDetails($request);

            $this->session->payment_details      = $payment_details;
            $this->session->payment_details_hash = $request->hash();
        } else {
          /** @var PaymentDetailsResponse $payment_details */
          $payment_details = $this->session->payment_details; // cached version
        }

        if (!$payment_details) {
            $this->getPaymentDetailsFailed();
            return;
        }

        // Loop through payment_details and push all addresses to array
        $addresses_arr = [];
        foreach($payment_details as $key => $value) {
          foreach($value as $key => $item) {
            array_push($addresses_arr, $item->address);
          }
        }


        $this->session->set("bx_payment_addresses", $addresses_arr);
        $this->session->set("bx_paid_by", false);
        $this->session->set("bx_payment_types", $payment_details->addresses);
        $this->session->set("bx_paid", false);
        $this->session->set("order_id", false);
        $this->session->set("expected_payment_note_sent", false);

        include "payment_fields.php";

    }

    // PLACE ORDER button is clicked.
    function process_payment($order_id)
    {
      $this->session->set("order_id",$order_id);
      $order = new WC_Order($order_id);

      if( $this->session->get("expected_payment_note_sent") == false ) {
        $this->add_note_expected_payment($order);
        $this->session->set("expected_payment_note_sent", true);
      }

        // Check if order has been paid
        if (!$this->orderPaid($order_id, $report_errors = false)) {
            sleep(2);
            if (!$this->orderPaid($order_id)) { // one retry
                return;
            }
        }

        $this->add_note_after_payment($order);

        // Mark as on-hold (we're awaiting for confirmations)
        $order->update_status('on-hold', __('Payment awaiting confirmation', 'woocommerce'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        $this->cart->empty_cart();

        unset($this->session->bx_payment_addresses);
        unset($this->session->payment_details);
        unset($this->session->payment_details_hash);
        unset($this->session->bx_paid_by);
        unset($this->session->bx_paid);
        unset($this->session->bx_payment_types);
        unset($this->session->order_id);
        unset($this->session->expected_payment_note_sent);

        // Return thank-you redirect
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order)
        ];
    }

    public function check_ipn_response()
    {
        @ob_clean();

        $data = json_decode(file_get_contents('php://input'), true);

        if (!$this->api->validIPN($data)) {
            header("HTTP/1.0 403 Forbidden");
            echo 'IPN Failed. Signature invalid.';
            exit();
        }

        try {
            $order = new WC_Order($data["order_id"]);
        } catch (\Exception $e) {
            header("HTTP/1.0 403 Forbidden");
            echo 'IPN Failed: Order not found';
            exit();
        }

        if ($data["confirmed_in_full"] !== true) {
            $order->add_order_note(__('BX Coinpay IPN: ' . $data["message"], 'woocommerce'));
            echo 'IPN Done. Status still on-hold.';
            exit();
        }

        // Payment completed
        $order->add_order_note(__('BX Coinpay IPN: ' . $data["message"], 'woocommerce'));
        $order->payment_complete();

        echo 'IPN Done. Payment complete.';
        exit();
    }

    protected function getPaymentDetailsFailed()
    {
      echo '<p class="woocommerce-error">'
        . __('Sorry BX CoinPay payments are currently unavailable: ', 'woocommerce')
        . $this->api->getError() . '</p>';
    }

    protected function notEnoughError($result)
    {
      $order = new WC_Order($this->session->get("order_id"));
      $this->add_note_after_payment($order, $is_enough = false);

      foreach($result->paid as $paid) {
        if( $paid->amount > 0 ) {
          wc_add_notice(__('Payment amount is not enough.<br> Got:', 'woocommerce')
                    . ' ' . $paid->amount
                    . ' ' . $paid->cryptocurrency . '<br>'
          , 'error');
        }
      }

    }

    protected function notPaidError()
    {
      wc_add_notice(
        __('Did you already pay it? We still did not see your payment!', 'woocommerce')
        . '<br>'
        . __('It can take a few seconds for your payment to appear. \
          If you already paid - press PLACE ORDER button again.', 'woocommerce')
        , 'error');
    }

    protected function requestFailed()
    {
      wc_add_notice(__('Payment error:', 'woocommerce')
        . ' ' . $this->api->getError(), 'error');
    }

    /**
     * @param $request
     * @return bool
     */
    protected function paymentDetailsMustBeRefreshed($request)
    {
      return $this->session->payment_details_hash != $request->hash()
        OR !$this->session->payment_details;
        // hash will change if cart has changes significant to payment
    }

    protected function orderPaid($order_id, $report_errors = true)
    {
        $result = $this->api->checkPaymentReceived(
          $this->session->get("bx_payment_addresses")
        );

        if ($result === false) {
            if ($report_errors) $this->requestFailed();
            return false;
        }

        if ($result->payment_received === false) {
            if ($report_errors) $this->notPaidError();
            return false;
        }

        if ($result->is_enough === false) {
          $this->session->set("bx_paid", $result->paid);
          if ($report_errors) $this->notEnoughError($result);
          return false;
        }

        // Put result into session
        $this->session->set("bx_paid_by", $result->paid_by);

        // If ok, then send Order_ID back to API
        $order_id_saved = $this->api->saveOrderId(
          $this->session->get("bx_payment_addresses"),
          $order_id
        );

        if($order_id_saved === false) {
           $this->requestFailed($this->getError());
          return false;
        }
        return true;
    }

    protected function add_note_after_payment($order, $is_enough = true)
    {
      if($this->session->get("bx_paid_by")) {
        // Render partial
        $this->paid_by($order, $this->session->get("bx_paid_by"),$is_enough);
      }else if($this->session->get("bx_paid") ) {
        // Render partial
        $this->paid($order, $this->session->get("bx_paid"), $is_enough);
      }else{
        $order->requestFailed();
      }
    }

    // Partial to $this->add_note_after_payment()
    protected function paid_by($order,$paid, $is_enough)
    {
      $order_id = $this->session->get("order_id");

      update_post_meta( $order_id, '_payment_method_title', "{$paid->name}" );
      $after_payment_note = "Paid via: {$paid->name}; Amount: {$paid->amount}; To address: <a target='_blank' href='{$paid->proof_link}'>{$paid->address}</a>";
      $order->add_order_note($after_payment_note);
    }

    // Partial to $this->add_note_after_payment()
    protected function paid($order, $paid, $is_enough)
    {
      update_post_meta( $order_id, '_payment_method_title', "BX CoinPay" );
      $after_payment_note = ($is_enough == false ? "NOT ENOUGH ERROR" : "")." ";
      foreach($paid as $key => $item) {
        $after_payment_note .= "Paid via: {$item->cryptocurrency}; Amount: {$item->amount}; To address: <a target='_blank' href='{$item->proof_link}'>{$item->address}</a>; ";
      }
      $order->add_order_note($after_payment_note);
    }

    // Run after generate order and before check mempool for transactions
    protected function add_note_expected_payment($order)
    {
      // Add comment: All available payment methods
      $order_note_str = "Expecting: ";
      $types = $this->session->get("bx_payment_types");
      $index = 0;
      foreach( $types as $type) {
        $order_note_str .= ($index != 0 ? " OR " : "");
        $order_note_str .= "<strong>".$type->amount
          . "</strong> in <strong>" . $type->name
          . "</strong> to address <strong>" . $type->address ."</strong>;";
        $index++;
      }

      $order->add_order_note($order_note_str);
    }
}
