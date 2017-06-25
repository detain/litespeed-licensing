<?php
/**
 * LiteSpeed Licensing
 * Last Changed: $LastChangedDate: 2017-05-31 04:54:09 -0400 (Wed, 31 May 2017) $
 * @author detain
 * @version $Revision: 24934 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * LiteSpeed
 *
 * @access public
 */
class LiteSpeed {

	public $login = '';
	public $password = '';
	public $usePost = TRUE;
	public $url = 'https://store.litespeedtech.com/reseller/LiteSpeed_eService.php';
	public $version = '1.1';
	public $params = [];
	public $response = [];
	public $validProducts = ['LSWS', 'LSLB'];
	public $validCpu = ['1', '2', '4', '8', 'V', 'U'];
	public $validPeriod = ['monthly', 'yearly', 'owned'];
	public $validPayment = ['credit', 'creditcard'];
	public $rawResponse;

	/**
	 * @param $login
	 * @param $password
	 */
	public function __construct($login, $password) {
		$this->login = $login;
		$this->password = $password;
		$this->resetParams();
		function_requirements('xml2array');
	}

	public function resetParams() {
		$this->params = [];
		$this->params['litespeed_store_login'] = rawurlencode($this->login);
		$this->params['litespeed_store_pass'] = rawurlencode($this->password);
		$this->params['eService_version'] = rawurlencode($this->version);
	}

	/**
	 * @return mixed
	 */
	public function ping() {
		return $this->req('Ping');
	}

	/**
	 * LiteSpeed Order License Return
	 * 		* 	If any errors occur during this process, <result> will contain “error” and <message>
	 * 	will contain a detailed description:
	 * 		<LiteSpeed_eService>
	 * 			<action>Order</action>
	 * 			<result>error</result>
	 * 			<message>Invalid cpu!</message>
	 * 		</LiteSpeed_eService>
	 * 	If the transaction cannot be completed, <result> will be “incomplete”. For example, if
	 * 	payment method is “credit”, but there is not enough credit in your account, or if payment method
	 * 	is “creditcard”, but the charge cannot go through, then the transaction will not be completed
	 * 	and <result> will display “incomplete”. <license_id> and <invoice> will be provided. You will
	 * 	need to login to LiteSpeed’s online store and pay the invoice to finish the order.
	 * 		If payment method is “credit”, but not enough credit is available to your account:
	 * 			<LiteSpeed_eService>
	 * 				<action>Order</action>
	 * 					<license_id>6066</license_id>
	 * 				<license_type>WS_L_V</license_type>
	 * 				<invoice_id>12466</invoice_id>
	 * 				<result>incomplete</result>
	 * 				<message>need to pay invoice first</message>
	 * 			</LiteSpeed_eService>
	 * 		If payment method is “creditcard”, but the attempted credit card payment failed:
	 * 			<LiteSpeed_eService>
	 * 				<action>Order</action>
	 * 				<license_id>9329</license_id>
	 * 				<license_type>WS_L_V</license_type>
	 * 				<invoice_id>20568</invoice_id>
	 * 				<result>incomplete</result>
	 * 				<message>need to pay invoice first, credit card payment failed</message>
	 * 			</LiteSpeed_eService>
	 * 	If the transaction is successful, which should happen for the majority of cases, you will get a
	 * 	serial number back. You can parse the message to get the serial number and create your own script
	 * 	for installation. You will still receive the same confirmation email and serial number emails as
	 * 	if you ordered online. There will be no <invoice_id> if the charge was paid with credit.
	 * 		<LiteSpeed_eService>
	 * 			<action>Order</action>
	 * 			< license_id>6067</ license_id>
	 * 			<license_type>WS_L_V</license_type>
	 * 			<invoice_id>12466</invoice_id>
	 * 			< license_serial>gv06-kXsU-SHBr-pL4N</license_serial>
	 * 			<result>success</result>
	 * 			<message>new order automatically accepted</message>
	 * 		</LiteSpeed_eService>
	 *
	 */

	/**
	 * Order a LiteSpeed License
	 *
	 * @param mixed $product  Product type. Available values: “LSWS” or “LSLB”.
	 * @param mixed $cpu What kind of license. Available values: “1”: 1-CPU license, “2”: 2-CPU license,  “4”: 4-CPU license, “8”: 8-CPU license, “V”: VPS license, “U”: Ultra-VPS license (Available LSWS 4.2.2 and above.), If <order_product> is “LSLB”, <order_cpu> is not required.
	 * @param mixed $period  Renewal period. Available values: “monthly”, “yearly”, “owned”.
	 * @param mixed $payment Payment method. Available values: “credit”: Use account credit. User can utilize “Add funds” function to pre-deposit money, which will show up as account credit.      “creditcard”: Use credit card to pay. The credit card is pre-defined in the account.  If there is available credit in the account, credit will be applied first, even when the payment method is set to “creditcard”.
	 * @param mixed $cvv  (optional) Credit card security code. Try not to set this field. Only if your bank requires this (meaning that the transaction will fail without it) should you then supply this field. CVV code is not stored in the system, so if you need to set it, you have to set this field every time. Other information from your credit card will be taken from your user account.
	 * @param mixed $promocode  (optional) Promotional code. If you have a pre-assigned promotional code registered to your account, then you can set it here. Promotional codes are exclusive to each client. If your account is entitled to discounts at the invoice level, you do not need a promotional code.
	 * @return array array with the output result. see above for description of output.
	 * 		array (
	 * 			'LiteSpeed_eService' => array (
	 * 				'action' => 'Order',
	 * 				'license_id' => '36514',
	 * 				'license_type' => 'WS_L_1',
	 * 				'invoice_id' => '86300',
	 * 				'result' => 'incomplete',
	 * 				'message' => 'Invoice 86300 not paid. ',
	 * 			),
	 * 		)
	 */
	public function order($product, $cpu = FALSE, $period = 'monthly', $payment = 'credit', $cvv = FALSE, $promocode = FALSE) {
		if (!in_array($product, $this->validProducts)) {
			return array('error' => 'Invalid Product');
		}
		if ($product == 'LSWS' && !in_array($cpu, $this->validCpu)) {
			return array('error' => 'Invalid CPU');
		}
		if (!in_array($period, $this->validPeriod)) {
			return array('error' => 'Invalid Billing Period');
		}
		if (!in_array($payment, $this->validPayment)) {
			return array('error' => 'Invalid Payment Method');
		}
		$this->params['order_product'] = $product;
		if ($product != 'LSLB') {
			$this->params['order_cpu'] = $cpu;
		}
		$this->params['order_period'] = $period;
		$this->params['order_payment'] = $payment;
		if ($cvv !== FALSE) {
			$this->params['order_cvv'] = $cvv;
		}
		if ($promocode !== FALSE) {
			$this->params['order_promocode'] = $promocode;
		}
		return $this->req('Order');
	}

	/**
	 * @param bool   $serial
	 * @param bool   $ipAddress
	 * @param string $now
	 * @param bool   $reason
	 * @return mixed
	 */
	public function cancel($serial = FALSE, $ipAddress = FALSE, $now = 'Y', $reason = FALSE) {
		$this->params['license_serial'] = $serial;
		$this->params['server_ip'] = $ipAddress;
		$this->params['cancel_now'] = $now;
		$this->params['cancel_reason'] = $reason;
		return $this->req('Cancel');
	}

	/**
	 * @param $serial
	 * @param $ipAddress
	 * @return mixed
	 */
	public function release($serial, $ipAddress) {
		$this->params['license_serial'] = $serial;
		$this->params['server_ip'] = $ipAddress;
		return $this->req('ReleaseLicense');
	}

	/**
	 * Suspend a license.   This is a tool to temporarily suspend a particular user's license in special cases,
	 *  like nonpayment or policy violation. The web server checks in with the license server at least once
	 *  every 24 hours. It will shut down when it sees the license has been suspended. As a consequence, your
	 *  client's web site will go down. Please note, though, that this license will continue to appear on
	 *  your invoices. Once the issue is resolved, you can use an “unsuspend” action to reactivate the license;
	 *  or you can request cancellation to permanently cancel it. Only requesting cancellation will take the
	 *  license off your future invoices.
	 *
	 * @param mixed $serial optional (if you specify IP , but this is preferred) serial of the license
	 * @param mixed $ipAddress optional (if you specify serial) ip of the license, specifying bothserial and ip gives extra validation
	 * @param mixed $reason optional reason for suspend/unsuspend
	 * @return mixed
	 */
	public function suspend($serial = FALSE, $ipAddress = FALSE, $reason = FALSE) {
		if ($serial !== FALSE) {
			$this->params['license_serial'] = $serial;
		}
		if ($ipAddress !== FALSE) {
			$this->params['server_ip'] = $ipAddress;
		}
		if ($reason !== FALSE) {
			$this->params['reason'] = $reason;
		}
		return $this->req('Suspend');
	}

	/**
	 * Unsuspend a license.
	 *
	 * @param mixed $serial optional (if you specify IP , but this is preferred) serial of the license
	 * @param mixed $ipAddress optional (if you specify serial) ip of the license, specifying bothserial and ip gives extra validation
	 * @param mixed $reason optional reason for suspend/unsuspend
	 * @return mixed
	 */
	public function unsuspend($serial = FALSE, $ipAddress = FALSE, $reason = FALSE) {
		if ($serial !== FALSE) {
			$this->params['license_serial'] = $serial;
		}
		if ($ipAddress !== FALSE) {
			$this->params['server_ip'] = $ipAddress;
		}
		if ($reason !== FALSE) {
			$this->params['reason'] = $reason;
		}
		return $this->req('Unsuspend');
	}

	/**
	 * @param bool   $serial
	 * @param bool   $ipAddress
	 * @param        $cpu
	 * @param string $payment
	 * @param bool   $cvv
	 * @return array|mixed
	 */
	public function upgrade($serial = FALSE, $ipAddress = FALSE, $cpu, $payment = 'credit', $cvv = FALSE) {
		if ($serial !== FALSE) {
			$this->params['license_serial'] = $serial;
		}
		if ($ipAddress !== FALSE) {
			$this->params['server_ip'] = $ipAddress;
		}
		if (!in_array($cpu, $this->validCpu)) {
			return array('error' => 'Invalid CPU');
		}
		if (!in_array($payment, $this->validPayment)) {
			return array('error' => 'Invalid Payment Method');
		}
		$this->params['upgrade_cpu'] = $cpu;
		$this->params['order_payment'] = $payment;
		if ($cvv !== FALSE) {
			$this->params['order_cvv'] = $cvv;
		}
		return $this->req('Upgrade');
	}

	/**
	 * @param $field
	 * @return mixed
	 */
	public function query($field) {
		/**
		 * query_field – Currently supported values:
		 *	 	“AllActiveLicenses”
		 * 		“LicenseDetail_IP:IP Address”
		 * 	LicenseDetail_IP:xx.xxx.xxx.xxx (Please replace “IP Address” above with the IP address
		 * 	you would like to look up. No space between tag, colon and IP address.)
		 * 	If there is more than one active license associated with the query IP, “error” will be
		 * 	returned.
		 * 		“LicenseDetail_Serial:Serial Number” (Since Dec 16, 2011)
		 * 	LicenseDetail_Serial:ccccccccccccc (Please replace “Serial Number” above with the
		 * 	serial number you would like to look up. No space between tag, colon and serial
		 * 	number.)
		 * 	If there is no active license with the query serial number (including licenses that have
		 * 	been canceled or terminated), “error” will be returned.
		 * 	(If you have a specific function you'd like to see us implement, let us know. We'll do our
		 * 	best to make the system more useful.)
		 */
		$this->params['query_field'] = $field;
		return $this->req('Query');
	}

	/**
	 * sets whether or not to use POST for the request or GET (false)
	 *
	 * @param mixed $post TRUE for POST , FALSE for GET requests		*
	 */
	public function usePost($post = TRUE) {
		$this->usePost = $post;
	}

	/**
	 * performs a request to LiteSpeed
	 *
	 * @param string $action Can be one of Ping, Order, Cancel, ReleaseLicense, Suspend, Unsuspend, Upgrade, or Query
	 * @return mixed
	 */
	public function req($action) {
		$this->params['eService_action'] = rawurlencode($action);
		// Set the curl parameters.
		$ch = curl_init();
		$url = $this->url;
		if ($this->usePost !== FALSE) {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			$pstring = '';
			foreach ($this->params as $param => $value) {
				$pstring .= '&'.$param.'='.$value.'';
			}
			$pstring = mb_substr($pstring, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $pstring);
		} else {
			curl_setopt($ch, CURLOPT_POST, FALSE);
			$pstring = '';
			foreach ($this->params as $param => $value) {
				$pstring .= '&'.$param.'='.$value.'';
			}
			$pstring = mb_substr($pstring, 1);
			$url .= '?'.$pstring;
		}
		myadmin_log('licenses', 'info', "LiteSpeed URL: $url\npstring: $pstring\n", __LINE__, __FILE__);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// Get response from the server.
		$this->rawResponse = curl_exec($ch);
		if (!$this->rawResponse) {
			$this->error[] = 'There was some error in connecting to Softaculous. This may be because of no internet connectivity at your end.';
			return FALSE;
		}

		// Extract the response details.
		$this->response = xml2array($this->rawResponse);
		myadmin_log('licenses', 'info', 'LiteSpeed Response '.var_export($this->response, TRUE), __LINE__, __FILE__);
		if (empty($this->response['error'])) {
			unset($this->response['error']);
			return $this->response;
		} else {
			$this->error = array_merge($this->error, $this->response['error']);
			return FALSE;
		}

	}

	/**
	 * LiteSpeed::r()
	 *
	 * @param mixed $r
	 * @return void
	 */
	public function displayResponse($r) {
		if (empty($r))
			$r = $this->error;
		echo '<pre>'.json_encode($r, JSON_PRETTY_PRINT).'</pre>';
	}

}
