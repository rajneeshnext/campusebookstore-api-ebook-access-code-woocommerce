add_action( 'woocommerce_order_status_processing', 'custom_wc_order_generate_access_code', 10, 2 );
function custom_wc_order_generate_access_code( $order_id, $order ) {
  $order = wc_get_order( $order_id ); 
  $total_quantity = $order->get_item_count();
  $count_digital_items=0;
  // Iterating through each "line" items in the order      
  foreach ($order->get_items() as $item_id => $item ) {
      if (class_exists('FS_WC_licenses_Manager')) {
            $license_manager = new FS_WC_licenses_Manager();
            $_POST['fslm_order_id'] = $order_id;
            $_POST['fslm_item_id']  = $item_id;
            $license_key = $license_manager->fslm_new_item_key_callback();
      };
      // Fetch WooProductID
      $sku = "";
      $redemptioncode="";
      $campusbookstore_productID;
      $product_id = wc_get_order_item_meta( $item_id, '_product_id', true );   
      $_product_access_code_exist = wc_get_order_item_meta( $item_id, '_product_access_code', true );   
      $product=wc_get_product($product_id);

      if( $product->is_type( 'simple' ) && $_product_access_code_exist ==""){          
          // Fetch related campusbookstore productID
          $campusbookstore_productID = get_post_meta( $product_id, 'campusbookstore_sku' ,true );      
          
          if($campusbookstore_productID!=""){
             $url = "https://campusebookstore.com/services.asmx/PurchaseProduct?APIKey=xxxxxx&ProductID=$campusbookstore_productID";

              //$xml = simplexml_load_string($data); 
              $xml_data = file_get_contents($url);
              $dom = new domdocument();
              if($xml_data!=""){
                  $dom->loadhtml($xml_data);
                  $nodes = $dom->getElementsByTagName('redemptioncode');
                  foreach( $nodes as $node ) {
                      $redemptioncode = $node->nodeValue;
                  }
                  if($redemptioncode!=""){   
                    $count_digital_items++;             
                    wc_update_order_item_meta($item_id, '_product_access_code', $redemptioncode);
                  }    
              }          
          }
      }else if( $product->is_type( 'variable' )  && $_product_access_code_exist ==""){
            $sku="";
            $variation_id = ( !empty( $item['variation_id'] ) ) ? $item['variation_id'] : '';
            if($variation_id == ""){
                continue;
            }
            $campusbookstore_productID = get_post_meta( $variation_id , 'campusbookstore_variation_sku', true );
            if($campusbookstore_productID!=""){
                 $url = "https://campusebookstore.com/services.asmx/PurchaseProduct?APIKey=UWOHVPELGEYZBOYYYASYFVHCMFMVRNFKJQGBXWKBQMGCELXYKC&ProductID=$campusbookstore_productID";

                  //$xml = simplexml_load_string($data); 
                  $xml_data = file_get_contents($url);
                  $dom = new domdocument();
                  if($xml_data!=""){
                      $dom->loadhtml($xml_data);
                      $nodes = $dom->getElementsByTagName('redemptioncode');
                      foreach( $nodes as $node ) {
                          $redemptioncode = $node->nodeValue;
                      }
                      if($redemptioncode!=""){
                        $_product_access_code_exist = wc_get_order_item_meta( $item_id, '_product_access_code', true ); 
                        if($_product_access_code_exist ==""){
                            $count_digital_items++;
                            wc_update_order_item_meta($item_id, '_product_access_code', $redemptioncode);
                        }
                      }    
                  }          
            }
      }else if($_product_access_code_exist !=""){
         $count_digital_items++;
      }        
  }

  if($count_digital_items == $total_quantity){
        $order->update_status( 'completed' );
  }
}
