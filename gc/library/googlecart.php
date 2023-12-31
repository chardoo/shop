<?php

/**
 * Copyright (C) 2006 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

 /* This class is used to create a Google Checkout shopping cart and post it 
  * to the Sandbox or Production environment
  * A very useful function is the CheckoutButtonCode() which returns the HTML 
  * code to post the cart using the standard technique
  * Refer demo/cartdemo.php for different use case scenarios for this code
  */

  class GoogleCart {
    var $merchant_id;
    var $merchant_key;
    var $currency;
    var $server_url;
    var $schema_url;
    var $base_url;
    var $checkout_url;
    var $checkout_diagnose_url;
    var $request_url;
    var $request_diagnose_url;

    var $cart_expiration = "";
    var $merchant_private_data = "";
    var $edit_cart_url = "";
    var $continue_shopping_url = "";
    var $request_buyer_phone = "";
    var $merchant_calculated = "";
    var $merchant_calculations_url = "";
    var $accept_merchant_coupons = "";
    var $accept_gift_certificates = "";
    var $rounding_mode;
    var $rounding_rule;

    var $item_arr;
    var $shipping_arr;
    var $default_tax_rules_arr;
    var $alternate_tax_tables_arr;
    var $xml_data;

    //The Constructor method which requires a merchant id, merchant key
    //and the operation type(sandbox or checkout)
    function GoogleCart($id, $key, $server_type = "sandbox", $currency = "USD") {
      $this->merchant_id = $id;
      $this->merchant_key = $key;
      $this->currency = $currency;

      if(strtolower($server_type) == "sandbox") 
        $this->server_url = "https://sandbox.google.com/checkout/";
      else
        $this->server_url=  "https://checkout.google.com/";  

      $this->schema_url = "http://checkout.google.com/schema/2";
      $this->base_url = $this->server_url."cws/v2/Merchant/" .
          $this->merchant_id;
      $this->checkout_url =  $this->base_url . "/checkout";
      $this->checkout_diagnose_url = $this->base_url . 
          "/checkout/diagnose";
      $this->request_url = $this->base_url . "/request";
      $this->request_diagnose_url = $this->base_url . 
          "/request/diagnose";

      //The item, shipping and tax table arrays are initialized
      $this->item_arr = array();
      $this->shipping_arr = array(); 
      $this->alternate_tax_tables_arr = array();
    }

    function SetCartExpiration($cart_expire) {
      $this->cart_expiration = $cart_expire;
    }

    function SetMerchantPrivateData($data) {
      $this->merchant_private_data = $data;
    }

    function SetEditCartUrl($url) {
      $this->edit_cart_url= $url;
    }

    function SetContinueShoppingUrl($url) {
      $this->continue_shopping_url = $url;
    }

    function SetRequestBuyerPhone($req) {
      $this->_SetBooleanValue('request_buyer_phone', $req, "false");
    }

    function SetMerchantCalculations($url, $tax_option = "false",
        $coupons = "false", $gift_cert = "false") {
      $this->merchant_calculations_url = $url;
      $this->_SetBooleanValue('merchant_calculated', $tax_option, "false");
      $this->_SetBooleanValue('accept_merchant_coupons', $coupons, "false");
      $this->_SetBooleanValue('accept_gift_certificates', $gift_cert, "false");
    }

    function AddItem($google_item) {
      $this->item_arr[] = $google_item;
    }

    function AddShipping($ship) {
      $this->shipping_arr[] = $ship;
    }

    function AddDefaultTaxRules($rules) {
      $this->default_tax_table = true;
      $this->default_tax_rules_arr[] = $rules;
    }

    function AddAlternateTaxTables($tax) {
      $this->alternate_tax_tables_arr[] = $tax;
    }

    function AddRoundingPolicy($mode, $rule) {
      switch ($mode) {
        case "UP":
        case "DOWN":
        case "CEILING":
        case "HALF_UP":
        case "HALF_DOWN":
        case "HALF_EVEN":
            $this->rounding_mode = $mode;
            break;
        default:
            break;
      }
      switch ($rule) {
        case "PER_LINE":
        case "TOTAL":
            $this->rounding_rule = $rule;
            break;
        default:
            break;
      }
    }

    function GetXML() {
      require_once('xml-processing/xmlbuilder.php');

      $xml_data = new XmlBuilder();

      $xml_data->Push('checkout-shopping-cart',
          array('xmlns' => $this->schema_url));
      $xml_data->Push('shopping-cart');

      //Add cart expiration if set
      if($this->cart_expiration != "") {
        $xml_data->Push('cart-expiration');
        $xml_data->Element('good-until-date', $this->cart_expiration);
        $xml_data->Pop('cart-expiration');
      }

      //Add XML data for each of the items
      $xml_data->Push('items');
      foreach($this->item_arr as $item) {
        $xml_data->Push('item');
        $xml_data->Element('item-name', $item->item_name);
        $xml_data->Element('item-description', $item->item_description);
        $xml_data->Element('unit-price', $item->unit_price,
            array('currency' => $this->currency));
        $xml_data->Element('quantity', $item->quantity);
        if($item->merchant_private_item_data != '')
          $xml_data->Element('merchant-private-item-data',
              $item->merchant_private_item_data);
        if($item->merchant_item_id != '')
          $xml_data->Element('merchant-item-id', $item->merchant_item_id);
        if($item->tax_table_selector != '')
          $xml_data->Element('tax-table-selector', $item->tax_table_selector);
        $xml_data->Pop('item');
      }
      $xml_data->Pop('items');

     if($this->merchant_private_data != '')
        $xml_data->Element('merchant-private-data',
            $this->merchant_private_data);   
			
      $xml_data->Pop('shopping-cart');

      $xml_data->Push('checkout-flow-support');
      $xml_data->Push('merchant-checkout-flow-support');
      if($this->edit_cart_url != '')
        $xml_data->Element('edit-cart-url', $this->edit_cart_url);
      if($this->continue_shopping_url != '')
        $xml_data->Element('continue-shopping-url',
            $this->continue_shopping_url);

      if(count($this->shipping_arr) > 0)
        $xml_data->Push('shipping-methods');

      //Add the shipping methods
      foreach($this->shipping_arr as $ship) {
        //Pickup shipping handled in else part
        if($ship->type == "flat-rate-shipping" ||
            $ship->type == "merchant-calculated-shipping") {
          $xml_data->Push($ship->type, array('name' => $ship->name));
          $xml_data->Element('price', $ship->price,
              array('currency' => $this->currency));

          $shipping_restrictions = $ship->shipping_restrictions;
          if (isset($shipping_restrictions)) {
            $xml_data->Push('shipping-restrictions');

            if ($shipping_restrictions->allow_us_po_box === true) {
              $xml_data->Element('allow-us-po-box', "true");
            } else {
              $xml_data->Element('allow-us-po-box', "false");
            }

            //Check if allowed restrictions specified
            if($shipping_restrictions->allowed_restrictions) {
              $xml_data->Push('allowed-areas');
              if($shipping_restrictions->allowed_country_area != "")
                $xml_data->Element('us-country-area','',
                    array('country-area' =>
                    $shipping_restrictions->allowed_country_area));
              foreach($shipping_restrictions->allowed_state_areas_arr as $current) {
                $xml_data->Push('us-state-area');
                $xml_data->Element('state', $current);
                $xml_data->Pop('us-state-area');
              }
              foreach($shipping_restrictions->allowed_zip_patterns_arr as $current) {
                $xml_data->Push('us-zip-area');
                $xml_data->Element('zip-pattern', $current);
                $xml_data->Pop('us-zip-area');
              }
              if($shipping_restrictions->allowed_world_area === true) {
                $xml_data->EmptyElement('world-area');
              }
              for($i=0; $i<count($shipping_restrictions->allowed_country_codes_arr); $i++) {
                $xml_data->Push('postal-area');
                $country_code = $shipping_restrictions->allowed_country_codes_arr[$i];
                $postal_pattern = $shipping_restrictions->allowed_postal_patterns_arr[$i];
                $xml_data->Element('country-code', $country_code);
                if ($postal_pattern != "") {
                  $xml_data->Element('postal-code-pattern', $postal_pattern);
                }
                $xml_data->Pop('postal-area');
              }
              $xml_data->Pop('allowed-areas');
            }

            if($shipping_restrictions->excluded_restrictions) { 
              if (!$shipping_restrictions->allowed_restrictions) {
                $xml_data->EmptyElement('allowed-areas');
              }
              $xml_data->Push('excluded-areas');
              if($shipping_restrictions->excluded_country_area != "")
                $xml_data->Element('us-country-area','',
                    array('country-area' => 
                    $shipping_restrictions->excluded_country_area));
              foreach($shipping_restrictions->excluded_state_areas_arr as $current) {
                $xml_data->Push('us-state-area');
                $xml_data->Element('state', $current);
                $xml_data->Pop('us-state-area');
              }
              foreach($shipping_restrictions->excluded_zip_patterns_arr as $current) {
                $xml_data->Push('us-zip-area');
                $xml_data->Element('zip-pattern', $current);
                $xml_data->Pop('us-zip-area');
              }
              for($i=0; $i<count($shipping_restrictions->excluded_country_codes_arr); $i++) {
                $xml_data->Push('postal-area');
                $country_code = $shipping_restrictions->excluded_country_codes_arr[$i];
                $postal_pattern = $shipping_restrictions->excluded_postal_patterns_arr[$i];
                $xml_data->Element('country-code', $country_code);
                if ($postal_pattern != "") {
                  $xml_data->Element('postal-code-pattern', $postal_pattern);
                }
                $xml_data->Pop('postal-area');
              }
              $xml_data->Pop('excluded-areas');
            }
            $xml_data->Pop('shipping-restrictions');
          }

          if ($ship->type == "merchant-calculated-shipping") {
            $address_filters = $ship->address_filters;
            if (isset($address_filters)) {
              $xml_data->Push('address-filters');

              if ($address_filters->allow_us_po_box === true) {
                $xml_data->Element('allow-us-po-box', "true");
              } else {
                $xml_data->Element('allow-us-po-box', "false");
              }

              //Check if allowed restrictions specified
              if($address_filters->allowed_restrictions) {
                $xml_data->Push('allowed-areas');
                if($address_filters->allowed_country_area != "")
                  $xml_data->Element('us-country-area','',
                      array('country-area' =>
                      $address_filters->allowed_country_area));
                foreach($address_filters->allowed_state_areas_arr as $current) {
                  $xml_data->Push('us-state-area');
                  $xml_data->Element('state', $current);
                  $xml_data->Pop('us-state-area');
                }
                foreach($address_filters->allowed_zip_patterns_arr as $current) {
                  $xml_data->Push('us-zip-area');
                  $xml_data->Element('zip-pattern', $current);
                  $xml_data->Pop('us-zip-area');
                }
                if($address_filters->allowed_world_area === true) {
                  $xml_data->EmptyElement('world-area');
                }
                for($i=0; $i<count($address_filters->allowed_country_codes_arr); $i++) {
                  $xml_data->Push('postal-area');
                  $country_code = $address_filters->allowed_country_codes_arr[$i];
                  $postal_pattern = $address_filters->allowed_postal_patterns_arr[$i];
                  $xml_data->Element('country-code', $country_code);
                  if ($postal_pattern != "") {
                    $xml_data->Element('postal-code-pattern', $postal_pattern);
                  }
                  $xml_data->Pop('postal-area');
                }
                $xml_data->Pop('allowed-areas');
              }

              if($address_filters->excluded_restrictions) { 
                if (!$address_filters->allowed_restrictions) {
                  $xml_data->EmptyElement('allowed-areas');
                }
                $xml_data->Push('excluded-areas');
                if($address_filters->excluded_country_area != "")
                  $xml_data->Element('us-country-area','',
                      array('country-area' => 
                      $address_filters->excluded_country_area));
                foreach($address_filters->excluded_state_areas_arr as $current) {
                  $xml_data->Push('us-state-area');
                  $xml_data->Element('state', $current);
                  $xml_data->Pop('us-state-area');
                }
                foreach($address_filters->excluded_zip_patterns_arr as $current) {
                  $xml_data->Push('us-zip-area');
                  $xml_data->Element('zip-pattern', $current);
                  $xml_data->Pop('us-zip-area');
                }
                for($i=0; $i<count($address_filters->excluded_country_codes_arr); $i++) {
                  $xml_data->Push('postal-area');
                  $country_code = $address_filters->excluded_country_codes_arr[$i];
                  $postal_pattern = $address_filters->excluded_postal_patterns_arr[$i];
                  $xml_data->Element('country-code', $country_code);
                  if ($postal_pattern != "") {
                    $xml_data->Element('postal-code-pattern', $postal_pattern);
                  }
                  $xml_data->Pop('postal-area');
                }
                $xml_data->Pop('excluded-areas');
              }
              $xml_data->Pop('address-filters');
            }
          }
          $xml_data->Pop($ship->type);
        }
        else if ($ship->type == "pickup") {
          $xml_data->Push('pickup', array('name' => $ship->name));
          $xml_data->Element('price', $ship->price, 
              array('currency' => $this->currency));
          $xml_data->Pop('pickup');
        }
      }
      if(count($this->shipping_arr) > 0)
        $xml_data->Pop('shipping-methods');

      if($this->request_buyer_phone != "")
        $xml_data->Element('request-buyer-phone-number', 
            $this->request_buyer_phone);

      if($this->merchant_calculations_url != "") {
        $xml_data->Push('merchant-calculations');
        $xml_data->Element('merchant-calculations-url', 
            $this->merchant_calculations_url);
        if($this->accept_merchant_coupons != "")
          $xml_data->Element('accept-merchant-coupons', 
              $this->accept_merchant_coupons);
        if($this->accept_gift_certificates != "")
          $xml_data->Element('accept-gift-certificates', 
              $this->accept_gift_certificates);
        $xml_data->Pop('merchant-calculations');
      }

      //Set Default and Alternate tax tables
      if( (count($this->alternate_tax_tables_arr) != 0) || (count($this->default_tax_rules_arr) != 0)) {
        if($this->merchant_calculated != "")
          $xml_data->Push('tax-tables', array('merchant-calculated' => $this->merchant_calculated));
        else
          $xml_data->Push('tax-tables');

        if(count($this->default_tax_rules_arr) != 0) {
          $xml_data->Push('default-tax-table');
          $xml_data->Push('tax-rules');
          foreach($this->default_tax_rules_arr as $curr_rule) {

            if($curr_rule->country_area != "") {
              $xml_data->Push('default-tax-rule');
              $xml_data->Element('shipping-taxed', $curr_rule->shipping_taxed);
              $xml_data->Element('rate', $curr_rule->tax_rate);
              $xml_data->Push('tax-area');
              $xml_data->Element('us-country-area','', array('country-area' => $curr_rule->country_area));
              $xml_data->Pop('tax-area');
              $xml_data->Pop('default-tax-rule');
            }

            foreach($curr_rule->state_areas_arr as $current) {
              $xml_data->Push('default-tax-rule');
              $xml_data->Element('shipping-taxed', $curr_rule->shipping_taxed);
              $xml_data->Element('rate', $curr_rule->tax_rate);
              $xml_data->Push('tax-area');
              $xml_data->Push('us-state-area');
              $xml_data->Element('state', $current);
              $xml_data->Pop('us-state-area');
              $xml_data->Pop('tax-area');
              $xml_data->Pop('default-tax-rule');
            }

            foreach($curr_rule->zip_patterns_arr as $current) {
              $xml_data->Push('default-tax-rule');
              $xml_data->Element('shipping-taxed', $curr_rule->shipping_taxed);
              $xml_data->Element('rate', $curr_rule->tax_rate);
              $xml_data->Push('tax-area');
              $xml_data->Push('us-zip-area');
              $xml_data->Element('zip-pattern', $current);
              $xml_data->Pop('us-zip-area');
              $xml_data->Pop('tax-area');
              $xml_data->Pop('default-tax-rule');
            }

            for($i=0; $i<count($curr_rule->country_codes_arr); $i++) {
              $xml_data->Push('default-tax-rule');
              $xml_data->Element('shipping-taxed', $curr_rule->shipping_taxed);
              $xml_data->Element('rate', $curr_rule->tax_rate);
              $xml_data->Push('tax-area');
              $xml_data->Push('postal-area');
              $country_code = $curr_rule->country_codes_arr[$i];
              $postal_pattern = $curr_rule->postal_patterns_arr[$i];
              $xml_data->Element('country-code', $country_code);
              if ($postal_pattern != "") {
                $xml_data->Element('postal-code-pattern', $postal_pattern);
              }
              $xml_data->Pop('postal-area');
              $xml_data->Pop('tax-area');
              $xml_data->Pop('default-tax-rule');
            }

            if ($curr_rule->world_area === true) {
              $xml_data->Push('default-tax-rule');
              $xml_data->Element('shipping-taxed', $curr_rule->shipping_taxed);
              $xml_data->Element('rate', $curr_rule->tax_rate);
              $xml_data->Push('tax-area');
              $xml_data->EmptyElement('world-area');
              $xml_data->Pop('tax-area');
              $xml_data->Pop('default-tax-rule');
            }
          }
          $xml_data->Pop('tax-rules');
          $xml_data->Pop('default-tax-table');
        }

        if(count($this->alternate_tax_tables_arr) != 0) {
          $xml_data->Push('alternate-tax-tables');
          foreach($this->alternate_tax_tables_arr as $curr_table) {
            $xml_data->Push('alternate-tax-table', array('standalone' => $curr_table->standalone, 'name' => $curr_table->name));
            $xml_data->Push('alternate-tax-rules');

            foreach($curr_table->tax_rules_arr as $curr_rule) {
              if($curr_rule->country_area != "") {
                $xml_data->Push('alternate-tax-rule');
                $xml_data->Element('rate', $curr_rule->tax_rate);
                $xml_data->Push('tax-area');
                $xml_data->Element('us-country-area','', array('country-area' => $curr_rule->country_area));
                $xml_data->Pop('tax-area');
                $xml_data->Pop('alternate-tax-rule');
              }

              foreach($curr_rule->state_areas_arr as $current) {
                $xml_data->Push('alternate-tax-rule');
                $xml_data->Element('rate', $curr_rule->tax_rate);
                $xml_data->Push('tax-area');
                $xml_data->Push('us-state-area');
                $xml_data->Element('state', $current);
                $xml_data->Pop('us-state-area');
                $xml_data->Pop('tax-area');
                $xml_data->Pop('alternate-tax-rule');
              }

              foreach($curr_rule->zip_patterns_arr as $current) {
                $xml_data->Push('alternate-tax-rule');
                $xml_data->Element('rate', $curr_rule->tax_rate);
                $xml_data->Push('tax-area');
                $xml_data->Push('us-zip-area');
                $xml_data->Element('zip-pattern', $current);
                $xml_data->Pop('us-zip-area');
                $xml_data->Pop('tax-area');
                $xml_data->Pop('alternate-tax-rule');
              }

              for($i=0; $i<count($curr_rule->country_codes_arr); $i++) {
                $xml_data->Push('alternate-tax-rule');
                $xml_data->Element('rate', $curr_rule->tax_rate);
                $xml_data->Push('tax-area');
                $xml_data->Push('postal-area');
                $country_code = $curr_rule->country_codes_arr[$i];
                $postal_pattern = $curr_rule->postal_patterns_arr[$i];
                $xml_data->Element('country-code', $country_code);
                if ($postal_pattern != "") {
                  $xml_data->Element('postal-code-pattern', $postal_pattern);
                }
                $xml_data->Pop('postal-area');
                $xml_data->Pop('tax-area');
                $xml_data->Pop('alternate-tax-rule');
              }

              if ($curr_rule->world_area === true) {
                $xml_data->Push('alternate-tax-rule');
                $xml_data->Element('rate', $curr_rule->tax_rate);
                $xml_data->Push('tax-area');
                $xml_data->EmptyElement('world-area');
                $xml_data->Pop('tax-area');
                $xml_data->Pop('alternate-tax-rule');
              }
            }
            $xml_data->Pop('alternate-tax-rules');
            $xml_data->Pop('alternate-tax-table');
          }
          $xml_data->Pop('alternate-tax-tables');
        }
        $xml_data->Pop('tax-tables');
      }

      if (($this->rounding_mode != "") && ($this->rounding_rule != "")) {
        $xml_data->Push('rounding-policy');
        $xml_data->Element('mode', $this->rounding_mode);
        $xml_data->Element('rule', $this->rounding_rule);
        $xml_data->Pop('rounding-policy');
      }

      $xml_data->Pop('merchant-checkout-flow-support');
      $xml_data->Pop('checkout-flow-support');
      $xml_data->Pop('checkout-shopping-cart');

      return $xml_data->GetXML();  
    }

    //Code for generating Checkout button 
    function CheckoutButtonCode($size = "large", $variant = true, $loc = "en_US") {

      $size = strtolower($size);

      switch ($size) {
        case "large":
          $width = "180";
          $height = "46";
          break;

        case "medium":
          $width = "168";
          $height = "44";
          break;

        case "small":
          $width = "160";
          $height = "43";
          break;
        
        default:
          $width = "180";
          $height = "46";
          break;
      }

      switch ($variant) {
        case true:
            $variant = "text";
            break;
        case false:
            $variant = "disabled";
            break;
        default:
            $variant = "text";
            break;
      }

      $style = "trans";

      if ($variant == "text") {
        $data = "<form method=\"POST\" action=\"". $this->checkout_url . "\">
                <input type=\"hidden\" name=\"cart\" value=\"". base64_encode($this->GetXML()) ."\">
                <input type=\"hidden\" name=\"signature\" value=\"". base64_encode($this->CalcHmacSha1($this->GetXML())). "\"> 
                <input type=\"image\" name=\"Checkout\" alt=\"Checkout\" 
                src=\"". $this->server_url."buttons/checkout.gif?merchant_id=".$this->merchant_id."&w=".$width. "&h=".$height."&style=".$style."&variant=".$variant."&loc=".$loc."\" 
                height=\"".$height."\" width=\"".$width. "\" />
                </form>";
      } elseif ($variant == "disabled") {
        $data = "<img alt=\"Checkout\" 
                src=\"". $this->server_url."buttons/checkout.gif?merchant_id=".$this->merchant_id."&w=".$width. "&h=".$height."&style=".$style."&variant=".$variant."&loc=".$loc."\" 
                height=\"".$height."\" width=\"".$width. "\" />";
      }
      return $data;
    }

    //Method which returns the encrypted google cart to make sure that the carts are not tampered with
    function CalcHmacSha1($data) {
      $key = $this->merchant_key;
      $blocksize = 64;
      $hashfunc = 'sha1';
      if (strlen($key) > $blocksize) {
        $key = pack('H*', $hashfunc($key));
      }
      $key = str_pad($key, $blocksize, chr(0x00));
      $ipad = str_repeat(chr(0x36), $blocksize);
      $opad = str_repeat(chr(0x5c), $blocksize);
      $hmac = pack(
                    'H*', $hashfunc(
                            ($key^$opad).pack(
                                    'H*', $hashfunc(
                                            ($key^$ipad).$data
                                    )
                            )
                    )
                );
      return $hmac; 
    }

    //Method used internally to set true/false cart variables
    function _SetBooleanValue($string, $value, $default) {
      $value = strtolower($value);
      if($value == "true" || $value == "false")
        eval('$this->'.$string.'="'.$value.'";');
      else
        eval('$this->'.$string.'="'.$default.'";');
    }
  }
?>
