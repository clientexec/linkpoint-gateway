<?php
/**
 *  @file
 *  clientexec linkpoint api plugin
 *  (c) 2005 Darrel O'Pry, thing.net communications
 *  email: dopry@darrelopry.com
 *
 *  -improved comments and code formatting before licensing to CE. -dopry 02/23/2007
 */

// include linkpoint API to  simplify processing, and maintenance
require_once 'class.lpxchange.php';
require_once 'modules/admin/models/GatewayPlugin.php';

class PluginLinkpoint extends GatewayPlugin
{
    /**
    * @package Plugins
    */
    function getVariables()
    {
        /* Specification
              itemkey     - used to identify variable in your other functions
              type        - text,textarea,yesno,password,hidden ( hiddens are not visable to the user )
              description - description of the variable, displayed in ClientExec
              value       - default value
        */
        $variables = array (
                /*T*/"Plugin Name"/*/T*/ =>      array (
                                          "type"        =>"hidden",
                                          "description" =>/*T*/"How CE sees this plugin ( not to be confused with the Signup Name )"/*/T*/,
                                          "value"       =>/*T*/"LinkPoint"/*/T*/
                                         ),
                // Required variables.
                /*T*/"Store"/*/T*/ => array (
                                          "type"        =>"text",
                                          "description" =>/*T*/"Please enter your LinkPoint store Name."/*/T*/,
                                          "value"       =>""
                                         ),
                /*T*/"Host"/*/T*/ => array (
                                          "type"        =>"text",
                                          "description" =>/*T*/"Please enter your linkpoint host."/*/T*/,
                                          "value"       =>"secure.linkpt.net"
                                         ),
                /*T*/"Port"/*/T*/ => array (
                                          "type"        =>"text",
                                          "description" =>/*T*/"Please enter your linkpoint port."/*/T*/,
                                          "value"       =>"1129"
                                         ),
                /*T*/"Cert"/*/T*/ => array (
                                          "type"        =>"text",
                                          "description" =>/*T*/"Please enter the path to your Linkpoint Digital Certificate."/*/T*/,
                                          "value"       =>"/path/to/linkpoint.pem"
                                         ),
                /*T*/"RiskThreshold"/*/T*/ => array(
                                          'type' => 'text',
                                          'description' => 'The LinkShield Risk Score at which you decline transactions.<br>(only used if LinkShield is available with your merchant account)',
                                          'value' => '',
                                         ),
                /*T*/"Live"/*/T*/ => array 	(
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select Yes to run live transactions, No to run Demo Transactions"/*/T*/,
                                          "value"       =>""
                                         ),
                /*T*/"Demo Approve"/*/T*/ => array (
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select yes to approve transactions in Demo Mode and no to decline transactions in DemoMode"/*/T*/,
                                          "value"       =>""
                                         ),
                /*T*/"Accept CC Number"/*/T*/ => array (
                                          "type"        =>"hidden",
                                          "description" =>/*T*/"Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"/*/T*/,
                                          "value"       =>"1"
                                         ),

                /*T*/"Visa"/*/T*/ => array (
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select YES to allow Visa card acceptance with this plugin.  No will prevent this card type."/*/T*/,
                                          "value"       =>"1"
                                         ),
                /*T*/"MasterCard"/*/T*/ => array (
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select YES to allow MasterCard acceptance with this plugin. No will prevent this card type."/*/T*/,
                                          "value"       =>"1"
                                         ),
                /*T*/"AmericanExpress"/*/T*/ => array (
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select YES to allow American Express card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                          "value"       =>"1"
                                         ),
                /*T*/"Discover"/*/T*/ => array (
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select YES to allow Discover card acceptance with this plugin. No will prevent this card type."/*/T*/,
                                          "value"       =>"0"
                                         ),
                /*T*/"Invoice After Signup"/*/T*/ => array (
                                          "type"        =>"yesno",
                                          "description" =>/*T*/"Select YES if you want an invoice sent to customer after signup is complete.  Only used for offline plugins"/*/T*/,
                                          "value"       =>"1"
                                         ),
                /*T*/"Signup Name"/*/T*/ => array (
                                          "type"        =>"text",
                                          "description" =>/*T*/"Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card."/*/T*/,
                                          "value"       =>"Credit Card"
                                         ),
                /*T*/"Dummy Plugin"/*/T*/ => array (
                                          "type"        =>"hidden",
                                          "description" =>/*T*/"1 = Only used to specify a billing type for a customer. 0 = full fledged plugin requiring complete functions"/*/T*/,
                                          "value"       =>"0"
                                         ),
                /*T*/"Auto Payment"/*/T*/ => array (
                                          "type"        =>"hidden",
                                          "description" =>/*T*/"No description"/*/T*/,
                                          "value"       =>"1"
                                         ),
                /*T*/"30 Day Billing"/*/T*/ => array (
                                          "type"        =>"hidden",
                                          "description" =>/*T*/"Select YES if you want ClientExec to treat monthly billing by 30 day intervals.  If you select NO then the same day will be used to determine intervals."/*/T*/,
                                          "value"       =>"0"
                                         ),
                /*T*/"Check CVV2"/*/T*/ => array (
                                        "type"          =>"hidden",
                                        "description"   =>/*T*/"Select YES if you want to accept CVV2 for this plugin."/*/T*/,
                                        "value"         =>"0"
                                       )
        );
        return $variables;
    }

    function singlePayment($params)
    { // when set to non recurring
        //Function used to provide users with the ability
        //Plugin variables can be accesses via $params["plugin_[pluginname]_[variable]"] (ex. $params["plugin_paypal_UserID"])
        return $this->autopayment($params);
    }

    // when set to non recurring
    function credit($params)
    {
        // used for callback
        $transType = 'credit';

        // Identify our self to linkoint.setup linkpoint connection/auth vars
        $myorder['configfile'] = $params['plugin_linkpoint_Store'];


        // * To Void a transaction is to cancel a payment transaction.
        //   Merchants can void transactions prior to settlement.
        //   Once the transaction has settled, the merchant has to perform a return or credit to reverse
        //   the charges and credit the customer's card.
        //$myorder['ordertype']  = 'VOID';

        // * A Return transaction returns funds to a customer�s credit card for an existing order on the system.
        //   To perform a return, you need the order number (which you can find in your reports).
        //   After you perform a Return for the full order amount, the order will appear in your reports with a
        //   transaction amount of 0.00.
        $myorder['ordertype']  = 'RETURN';

        // * A Credit transaction returns funds to a customer�s credit card on orders without an order number.
        //   This transaction is intended for returns against orders processed outside the system.
        //   Credit transactions are marked as Returns in your reports.
        //$myorder['ordertype']  = 'CREDIT';


        // Check if the plugin is configure to run in live mode and the
        // control panel is not in demo demo mode.
        if ($params['plugin_linkpoint_Live'] && !DEMO)  {
            $myorder['result'] = 'LIVE';
        }
        else {
            if(!isset($params['plugin_linkpoint_Demo Mode'])){
                if($params['plugin_linkpoint_Demo Approve']){
                    $params['plugin_linkpoint_Demo Mode'] = 1;
                }else{
                    $params['plugin_linkpoint_Demo Mode'] = 3;
                }
            }
            switch($params['plugin_linkpoint_Demo Mode']) {
              case 1: $myorder['result']="GOOD"; 	break;
              case 2: $myorder['result']="DUPLICATE";	break;
              case 3: $myorder['result']="DECLINE"; 	break;
              default: $myorder['result']="GOOD"; //assume good
            }
        }

        // Setup transaction options in a structured array that
        // parallels linkpoints XML format.

        // Harcode Electronic Commerce Initiated.
        $myorder['transactionorigin'] = "ECI";

        // The Order ID to be assigned to the transaction. This field must be a valid Order ID from a prior Sale
        $myorder['oid']               = $params['invoiceRefundTransactionId'];

        // I'm not quite sure what the spec was on this anymore.
        $myorder['terminaltype']      = "UNSPECIFIED";

        // Get IP of person initiating transaction.
        if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip   = getenv('HTTP_X_FORWARDED_FOR');
        } else {
        $ip   = getenv('REMOTE_ADDR');
        }
        $myorder['ip'] = $ip; //needed for ip blocking/fraud protection

        $myorder['chargetotal'] = $params['invoiceTotal'];

        // card info
        $myorder['cardnumber']   = $params['userCCNumber'];
        $myorder['cardexpmonth'] = mb_substr($params['userCCExp'], 0, 2);
        $expLen = strlen($params['userCCExp']);
        $myorder['cardexpyear']  = mb_substr($params['userCCExp'], $expLen - 2, $expLen);

        // commented out code for CVV2 values. You could use a client profile field to get
        // that data here to save a % per transaction.
        /*
        $myorder['cvmvalue'] = $params[??]
        if ($myorder['cvmvalue']) {
        	$myorder['cvmindicator']     = "provided";
        }
        else {
        */
        $myorder['cvmindicator']     = 'not_provided';
        /*
        }
        */

        // lpxchange class will use this path for curl.
        if ($params['pathCurl'] != '') {
        $myorder['pathCurl'] = $params['pathCurl'];
        }

        // Set up setup linkpoint connection vars
        // from plugin settings
        $host       = $params['plugin_linkpoint_Host'];
        $port       = $params['plugin_linkpoint_Port'];
        $keyfile    = $params['plugin_linkpoint_Cert'];

        // Instantiate the lpxchange class.
        $lpx = new lpxchange($host, $port, $keyfile, false, false);

        // Send payment data to the linkpoint gateway.
        $linkpointResult = $lpx->process_payment($myorder);

        // need to tell gateway plugin class not to reset docroot since we are calling directly
        $bolLeavePath=true;
        require_once 'modules/billing/models/class.gateway.plugin.php';

        // Create plug in class to interact with CE.
        $cPlugin = new Plugin($params['invoiceNumber'], "linkpoint", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);
        $cPlugin->setAction('refund');

        $cPlugin->setTransactionID($myorder['oid']);
        $cPlugin->setLast4(mb_substr($params['userCCNumber'], -4));

        CE_Lib::log(4, "LinkPoint response: ".print_r($linkpointResult, true));
        $result = $linkpointResult['r_approved'];

        switch ($result) {
        case "APPROVED":
          // Handle approved transactions
          //$cPlugin->PaymentAccepted($params['invoiceTotal'], $linkpointResult['r_message'] .' (LP Approval: '. $linkpointResult['r_code'] .'; Ref: '. $linkpointResult['r_ref'] .'; Time: '. $linkpointResult['r_time']. ')',0);
          $cPlugin->PaymentAccepted($params['invoiceTotal'], $linkpointResult['r_message'] .' (LP Approval: '. $linkpointResult['r_code'] .'; Ord: '. $linkpointResult['r_ordernum'] .'; Time: '. $linkpointResult['r_time']. ')',0);
          $tReturn = array('AMOUNT' => $myorder['chargetotal']);
          break;

        case "DECLINED":
          // Handle declined transactions
          $tReturn = 'DECLINED '. $linkpointResult['r_error'] .';AVS: '. $linkpointResult['r_avs'] .'; '. $linkpointResult['r_message'] .' (Ord: '. $linkpointResult['r_ordernum'] .')';
          $cPlugin->PaymentRejected($tReturn);
          break;

        case 'FRAUD':
          // Handle fraud transactions similar to declined transactions, but return a different
          // string.
          $tReturn = 'FRAUD '. $linkpointResult['r_error'] .' (Ord: '. $linkpointResult['r_ordernum'] .')';
          $cPlugin->PaymentRejected($tReturn);
          break;

        default:
          // update error string.
          if(isset($linkpointResult['r_error']) && $linkpointResult['r_error'] != ''){
              $tReturn .= 'DECLINED '.$linkpointResult['r_error'];
              if(isset($linkpointResult['r_ordernum']) && $linkpointResult['r_ordernum'] != ''){
                  $tReturn .= ' (Ord: '. $linkpointResult['r_ordernum'] .')';
              }
          }else{
              $tReturn = "DECLINED: Your payment was declined for an unknown reason. Check the information that you entered and try again. If the problem continues please contact support.";
          }
          $cPlugin->PaymentRejected($tReturn);
          break;
        }
        return $tReturn;
    }

    function autopayment($params)
    {
        // used for callback
        $transType = 'charge';

        // Identify our self to linkoint.setup linkpoint connection/auth vars
        $myorder['configfile'] = $params['plugin_linkpoint_Store'];

        // commented out since, I was unable to figure out how to merge a payment a fraud plugin
        // at the time of writing this code, I do not think fruad plugins were supported.
        // left for future adventurers to think about or remove.
        /*
        if ($params['isSignup']==1) {
        	$myorder['order_type'] = "PREAUTH";     //do a pre auth on signup so we can get fraud data if available
        	$bolInSignup = true;
        }
        else {
        */
        $myorder['ordertype']  = 'SALE'; //we're gonna process w/o fraud protection
        /*
        $bolInSignup = false;
        }
        */

        // Check if the plugin is configure to run in live mode and the
        // control panel is not in demo demo mode.
        if ($params['plugin_linkpoint_Live'] && !DEMO)  {
            $myorder['result'] = 'LIVE';
        }
        else {
            if(!isset($params['plugin_linkpoint_Demo Mode'])){
                if($params['plugin_linkpoint_Demo Approve']){
                    $params['plugin_linkpoint_Demo Mode'] = 1;
                }else{
                    $params['plugin_linkpoint_Demo Mode'] = 3;
                }
            }
            switch($params['plugin_linkpoint_Demo Mode']) {
              case 1: $myorder['result']="GOOD"; 	break;
              case 2: $myorder['result']="DUPLICATE";	break;
              case 3: $myorder['result']="DECLINE"; 	break;
              default: $myorder['result']="GOOD"; //assume good
            }
        }

        // Setup transaction options in a structured array that
        // parallels linkpoints XML format.

        // Harcode Electronic Commerce Initiated.
        $myorder['transactionorigin'] = "ECI";

        // Add a time stamp to the oid to help prevent duplicate transactions
        // and allow us to potentially track/refund multiple payments against
        // an invoice.
        $myorder['oid']               = $params['invoiceNumber'].date('dmHis');

        // I'm not quite sure what the spec was on this anymore.
        $myorder['terminaltype']      = "UNSPECIFIED";

        // Get IP of person initiating transaction.
        if (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip   = getenv('HTTP_X_FORWARDED_FOR');
        } else {
        $ip   = getenv('REMOTE_ADDR');
        }
        $myorder['ip'] = $ip; //needed for ip blocking/fraud protection


        $myorder['chargetotal'] = $params['invoiceTotal'];

        // card info
        $myorder['cardnumber']   = $params['userCCNumber'];
        $myorder['cardexpmonth'] = mb_substr($params['userCCExp'], 0, 2);
        $expLen = strlen($params['userCCExp']);

        $myorder['cardexpyear']  = mb_substr($params['userCCExp'], $expLen - 2, $expLen);
        // commented out code for CVV2 values. You could use a client profile field to get
        // that data here to save a % per transaction.
        /*
        $myorder['cvmvalue'] = $params[??]
        if ($myorder['cvmvalue']) {
        	$myorder['cvmindicator']     = "provided";
        }
        else {
        */
        $myorder['cvmindicator']     = 'not_provided';
        /*
        }
        */

        // BILLING INFO
        $myorder['name']     = $params['userFirstName'] .' '. $params['userLastName'];

        // Set a company name.  IIRC a value is required and NA was linkpoints recommended placeholder.
        if ($params['userOrganization']=='') {
        $myorder['company'] = "NA";
        }
        else {
        $myorder['company'] = $params['userOrganization'];
        }


        $myorder['address1'] = $params['userAddress'];
        $myorder['city']     = $params['userCity'];
        $myorder['state']    = $params['userState'];
        $myorder['country']  = $params['userCountry'];
        $myorder['phone']    = $params['userPhone'];
        $myorder['email']    = $params['userEmail'];

        // Get numeric portion of address for AVS.
        preg_match('/(^\d*)\s/', $params['userAddress'], $matches);
        //we need to have a fail here if we don't have nums in userAddress
        //TODO4.1
        $myorder['addrnum'] = '';
        if(isset($matches[0])){
            $myorder['addrnum'] = $matches[0];
        }

        $myorder['zip']      = $params['userZipcode'];

        // Order item detail for linkpoint.
        // It would be nice if all package data for the invoice was passed
        // then you could itemize you transactions in linkpoint as well.
        $myorder['items']['item']['id']          = $params['invoiceNumber'];
        $myorder['items']['item']['description'] = $params['invoiceDescription'];
        $myorder['items']['item']['price']       = $params['invoiceTotal'];
        $myorder['items']['item']['quantity']    = '1';


        // lpxchange class will use this path for curl.
        if ($params['pathCurl'] != '') {
        $myorder['pathCurl'] = $params['pathCurl'];
        }


        // Set up setup linkpoint connection vars
        // from plugin settings
        $host       = $params['plugin_linkpoint_Host'];
        $port       = $params['plugin_linkpoint_Port'];
        $keyfile    = $params['plugin_linkpoint_Cert'];

        // Instantiate the lpxchange class.
        $lpx = new lpxchange($host, $port, $keyfile, false, false);

        // Send payment data to the linkpoint gateway.
        $linkpointResult = $lpx->process_payment($myorder);


        // need to tell gateway plugin class not to reset docroot since we are calling directly
        $bolLeavePath=true;
        require_once 'modules/billing/models/class.gateway.plugin.php';

        // Create plug in class to interact with CE.
        $cPlugin = new Plugin($params['invoiceNumber'], "linkpoint", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);
        $cPlugin->setAction('charge');

        $cPlugin->setTransactionID($myorder['oid']);
        $cPlugin->setLast4(mb_substr($params['userCCNumber'], -4));

        CE_Lib::log(4, "LinkPoint response: ".print_r($linkpointResult, true));
        $result = $linkpointResult['r_approved'];

        switch ($result) {
        case "APPROVED":
          // Handle approved transactions
          //$cPlugin->PaymentAccepted($params['invoiceTotal'], $linkpointResult['r_message'] .' (LP Approval: '. $linkpointResult['r_code'] .'; Ref: '. $linkpointResult['r_ref'] .'; Time: '. $linkpointResult['r_time']. ')',0);
          $cPlugin->PaymentAccepted($params['invoiceTotal'], $linkpointResult['r_message'] .' (LP Approval: '. $linkpointResult['r_code'] .'; Ord: '. $linkpointResult['r_ordernum'] .'; Time: '. $linkpointResult['r_time']. ')',0);
          // updated to return an empty string for CE 2.8.2 compatability by Modified by Ryan Wilson, ClanBaseLive.com
          $tReturn = "";
          break;

        case "DECLINED":
          // Handle declined transactions
          $tReturn = 'DECLINED '. $linkpointResult['r_error'] .';AVS: '. $linkpointResult['r_avs'] .'; '. $linkpointResult['r_message'] .' (Ord: '. $linkpointResult['r_ordernum'] .')';
          $cPlugin->PaymentRejected($tReturn);
          break;

        case 'FRAUD':
          // Handle fraud transactions similar to declined transactions, but return a different
          // string.
          $tReturn = 'FRAUD '. $linkpointResult['r_error'] .' (Ord: '. $linkpointResult['r_ordernum'] .')';
          $cPlugin->PaymentRejected($tReturn);
          break;

        default:
          // update error string.
          if(isset($linkpointResult['r_error']) && $linkpointResult['r_error'] != ''){
              $tReturn .= 'DECLINED '.$linkpointResult['r_error'];
              if(isset($linkpointResult['r_ordernum']) && $linkpointResult['r_ordernum'] != ''){
                  $tReturn .= ' (Ord: '. $linkpointResult['r_ordernum'] .')';
              }
          }else{
              $tReturn = "DECLINED: Your payment was declined for an unknown reason. Check the information that you entered and try again. If the problem continues please contact support.";
          }
          $cPlugin->PaymentRejected($tReturn);
          break;
        }
        return $tReturn;
    }
}
?>