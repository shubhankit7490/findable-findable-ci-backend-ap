<?php

class Stripe {
	public $charge = false;
	public $customer = false;
	public $invoice = false;
	public $invoiceItems = [];
	public $subscriptions = null;

	protected $last_error = '';

	/**
	 * __construct
	 *
	 * @access    public
	 *
	 * @return    Stripe instance
	 */
	function __construct() {
		$this->config->load( 'stripe' );
		$this->load->helper( 'array' );
		$this->lang->load( 'stripe' );
		$key = $this->config->item( 'stripe' );
		\Stripe\Stripe::setApiKey( $key['secret_key'] );
	}

	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * @access    public
	 *
	 * @param    $var
	 *
	 * @return    mixed
	 */
	public function __get( $name ) {
		if ( isset( $this->$name ) ) {
			return $this->$name;
		} else {
			return get_instance()->$name;
		}
	}

	/**
	 * get_last_error
	 *
	 * Get the last api error
	 *
	 * @access    public
	 *
	 * @return    boolean
	 */
	public function get_last_error() {
		return $this->last_error;
	}

	/**
	 * set_customer
	 *
	 * Set the customer id property if not set
	 *
	 * @access    public
	 *
	 * @return    null
	 */
	public function set_customer( $customer_id = false ) {
		if ( ! $this->customer && $customer_id !== false ) {
			$this->customer = (object) array(
				'id' => $customer_id
			);
		}
	}

	/**
	 * update_customer
	 *
	 * Update the customer with a set of new parameters (description, email, source)
	 *
	 * @access    public
	 *
	 * @return    null
	 */
	public function update_customer( $customer_id = null, $customer = [] ) {
		if ( ! $this->customer && ! is_null( $customer_id ) ) {
			$this->get_customer( $customer_id );
		}

		if ( ! $this->customer ) {
			$this->last_error = $this->lang->line( 'undefined_customer' );

			return false;
		} else {
			return $this->_execute( function () use ( $customer ) {
				foreach($customer as $k => $prop) {
					$this->customer->$k = $prop;
				}
				$this->customer->save();

				return true;
			} );
		}
	}

	/**
	 * create_charge
	 *
	 * Charge a customer
	 *
	 * @access    public
	 *
	 * @param array $options
	 *
	 * @return    boolean
	 */
	public function create_charge( $options = array() ) {
		return $this->_execute( function () use ( $options ) {
			$this->charge = \Stripe\Charge::create( $options );

			return true;
		} );
	}

	/**
	 * create_customer
	 *
	 * Create a customer
	 *
	 * @access    public
	 *
	 * @param array $options
	 *
	 * @return    boolean
	 */
	public function create_customer( $options = array() ) {
		return $this->_execute( function () use ( $options ) {
			$this->customer = \Stripe\Customer::create( $options );

			return true;
		} );
	}

	/**
	 * get_customer
	 *
	 * Create a customer
	 *
	 * @access    public
	 *
	 * @param string $customer_id
	 *
	 * @return    boolean
	 */
	public function get_customer( $customer_id = false ) {
		return $this->_execute( function () use ( $customer_id ) {
			$this->customer = \Stripe\Customer::retrieve( [
				'id'     => $customer_id,
				'expand' => array( "default_source" )
			] );

			return true;
		} );
	}

	/**
	 * update_card
	 *
	 * Update a credit card of the customer
	 *
	 * @access    public
	 *
	 * @param string $stripe_token
	 *
	 * @return    boolean
	 */
	public function update_card( $stripe_token ) {
		if ( ! $this->customer ) {
			$this->last_error = $this->lang->line( 'undefined_customer' );

			return false;
		} else {
			return $this->_execute( function () use ( $stripe_token ) {
				$this->customer->source = $stripe_token;
				$this->customer->save();

				return true;
			} );
		}
	}

	/**
	 * create_invoice_item
	 *
	 * Create an invoice item for a customer and save it in the invoiceItems array
	 *
	 * @access    public
	 *
	 * @param array $options
	 *
	 * @return    $this
	 */
	public function create_invoice_item( $options ) {
		$this->invoiceItems[] = \Stripe\InvoiceItem::create( $options );

		return $this;
	}

	/**
	 * create_invoice
	 *
	 * Create an invoice for a customer
	 *
	 * @access    public
	 *
	 * @return    $this
	 */
	public function create_invoice() {
		$this->invoice = \Stripe\Invoice::create( array(
			"customer" => $this->customer->id
		) );

		return $this;
	}

	/**
	 * pay_invoice
	 *
	 * Pay the created invoice (status = closed)
	 *
	 * @access    public
	 *
	 * @return    $this
	 */
	public function pay_invoice() {
		$invoice       = \Stripe\Invoice::retrieve( $this->invoice->id );
		$this->invoice = $invoice->pay();

		return $this;
	}

	/**
	 * create_and_pay
	 *
	 * Create an invoice from a number of items and pay the invoice for a customer (Transactional model)
	 *
	 * @access    public
	 *
	 * @param array $items
	 *
	 * @return    boolean
	 */
	public function create_and_pay( $items = array() ) {
		return $this->_execute( function () use ( $items ) {
			// Create the invoice items
			if ( ! is_assoc( $items ) ) {
				foreach ( $items as $item ) {
					$this->create_invoice_item( $item );
				}
			} else {
				$this->create_invoice_item( $items );
			}

			// Create the invoice and pay
			$this->create_invoice()->pay_invoice();

			return true;
		} );
	}

	/**
	 * create_and_pay
	 *
	 * Create an invoice from a number of items and pay the invoice for a customer (Transactional model)
	 *
	 * @access    private
	 *
	 * @param callable $process
	 *
	 * @return    boolean
	 */
	private function _execute( $process ) {
		try {
			return call_user_func( $process );
		} catch ( \Stripe\Error\Card $e ) {
			$body             = $e->getJsonBody();
			$this->last_error = $body['error']['message'];

			return false;
		} catch ( \Stripe\Error\RateLimit $e ) {
			$this->last_error = $this->lang->item( 'API_RATE_LIMIT' );

			return false;
		} catch ( \Stripe\Error\InvalidRequest $e ) {
			$body             = $e->getJsonBody();
			$this->last_error = $body['error']['message'];

			return false;
		} catch ( \Stripe\Error\Authentication $e ) {
			$this->last_error = $this->lang->item( 'API_AUTH_ERROR' );

			return false;
		} catch ( \Stripe\Error\ApiConnection $e ) {
			$this->last_error = $this->lang->item( 'API_CONN_ERROR' );

			return false;
		} catch ( \Stripe\Error\Base $e ) {
			$this->last_error = $this->lang->item( 'API_GNRL_ERROR' );

			return false;
		} catch ( Exception $e ) {
			if ( ENVIRONMENT != 'development' ) {
				$this->last_error = $this->lang->item( 'API_GNRL_ERROR' );

				return false;
			} else {
				$body             = $e->getJsonBody();
				$this->last_error = $body['error']['message'];

				return false;
			}
		}
	}

	public function get_subscription( $subscriptions_stripe_id ){
		return $this->_execute( function () use ( $subscriptions_stripe_id ) {
			$this->subscriptions = \Stripe\Subscription::retrieve( [
				'id'     => $subscriptions_stripe_id
			] );

			return $this;
		} );
	}


	public function create_subscription($package_id){
		return $this->_execute( function () use ( $package_id ) {
			$this->subscriptions = \Stripe\Subscription::create( [
				'customer' => $this->customer,
				"items" => [
					[
						"plan" => $package_id,
					],
				]
			] );

			return $this;
		} );
	}

	public function update_subscription( $subscriptions_stripe_id, $status ){
		return $this->_execute( function () use ( $subscriptions_stripe_id, $status ) {
			$this->subscriptions = \Stripe\Subscription::retrieve( [
				'id'     => $subscriptions_stripe_id
			] );

			$this->subscriptions->cancel_at_period_end = $status;

			$this->subscriptions->save();
			return true;
		} );
	}
}

?>