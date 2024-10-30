<?php

class PaymentDetailsRequest
{
    public $callback;
    public $amount;
    public $currency_from;
    public $currency_to;
    public $order_label;

    /**
     * PaymentDetailsRequest constructor.
     * @param string $callback
     * @param float $amount
     * @param string $currency_from
     * @param string $currency_to
     * @param string $order_label
     */
    public function __construct($callback, $amount, $currency_from, $currency_to, $order_label) // if you are changing parameters - don't forget about hash()! it must include all of them!
    {
        $this->callback    = $callback;
        $this->amount      = $amount;
        $this->currency_from    = $currency_from;
        $this->currency_to    = $currency_to;
        $this->order_label = $order_label;
    }

    /**
     * @return string
     */
    public function hash()
    {
        return md5(
            $this->callback
            . $this->amount
            . $this->currency_from
            . $this->currency_to
            . $this->order_label
        );
    }
}
