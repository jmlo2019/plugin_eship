    <?php
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/BasicAuth.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/OAuth.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/Options.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/Request.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/Response.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/HttpClientException.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/HttpClient/HttpClient.php";
    require_once ESHIP_PLUGIN_DIR_PATH . "helpers/wc-api/src/WooCommerce/Client.php";

    use Automattic\WooCommerce\Client;

    /**
     * La funcionalidad específica de administración del plugin.
     *
     * @link       https://eship.com
     * @since      1.0.0
     *
     * @package    ESHIP_blank
     * @subpackage ESHIP_blank/admin
     */

    /**
     * Define el nombre del plugin, la versión y dos métodos para
     * Encolar la hoja de estilos específica de administración y JavaScript.
     * 
     * @since      1.0.0
     * @package    ESHIP
     * @subpackage ESHIP/admin
     * @author     Juan Manuel Leal <jleal@segmail.co>
     * 
     * @property string $plugin_name
     * @property string $version
     */
    class ESHIP_Admin {
        
        private $plugin_name;
        private $version;
        private $build_menupage;
        private $db;

        public function __construct( $plugin_name, $version ) 
        {
            global $wpdb;
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            $this->build_menupage = new ESHIP_Build_Menupage();
            $this->db = $wpdb;
        }

        public function enqueue_styles($hook) 
        {
            //toplevel_page_toplevel_page_ 
            if( $hook ) {
                $hook;
            }
            /**
             * Framework Bootstrap
             * https://getbootstrap.com/
             * Bootstrap
             */
            wp_enqueue_style( 'eship_bootstrap_css', ESHIP_PLUGIN_DIR_URL . 'helpers/bootstrap/css/bootstrap.min.css', array(), '5.1.3', '' );

            wp_enqueue_style( 'eship_fontawesome_free_css', ESHIP_PLUGIN_DIR_URL . 'helpers/fontawesome-free-5.15.4-web/css/all.min.css', array(), '5.15.4', '' );

            wp_enqueue_style( 'eship_bootstrap_table_admin_css', ESHIP_PLUGIN_DIR_URL . 'helpers/bootstrap-table/css/bootstrap-table.min.css', array(), '1.19.1', '' );


            /**
             * eship-admin.css
             * Archivo de hojas de estilos principales
             * de la administración
             */
            wp_enqueue_style( $this->plugin_name, ESHIP_PLUGIN_DIR_URL . 'admin/css/eship-admin.css', array(), $this->version, '' );
        }
        
        public function enqueue_scripts($hook) 
        {
            if( $hook ) {
                $hook;
            } 

            /**
             * Framework Bootstrap
             * https://getbootstrap.com/
             * Bootstrap
             */
            wp_enqueue_script( 'eship_bootstrap_admin_js', ESHIP_PLUGIN_DIR_URL . 'helpers/bootstrap/js/bootstrap.min.js', array(), '5.1.3', TRUE );

            wp_enqueue_script( 'eship_fontawesome_free_js', ESHIP_PLUGIN_DIR_URL . 'helpers/fontawesome-free-5.15.4-web/js/all.min.js', array(), '5.15.3', TRUE );

            /**
             * Extension Bootstrap Table
             * https://bootstrap-table.com/
             * Bootstrap Table
             */
            wp_enqueue_script( 'eship_bootstrap_table_admin_js', ESHIP_PLUGIN_DIR_URL . 'helpers/bootstrap-table/js/bootstrap-table.min.js', array(), '1.19.1', TRUE );

            wp_enqueue_script( $this->plugin_name, ESHIP_PLUGIN_DIR_URL . 'admin/js/eship-admin.js', array(), $this->version, TRUE );
            
            wp_localize_script(
                $this->plugin_name,
                'eshipData',
                array(
                    'url'      => admin_url('admin-ajax.php'),
                    'security' => wp_create_nonce('eship_sec')
                )
            );
        }

        public function add_menu() {
            $this->build_menupage->add_menu_page(
                'orders',//__( 'ESHIP', 'eship-textdomain' ),
                'eship',//__( 'ESHIP', 'eship-textdomain' ),
                'manage_options',
                'eship_dashboard',
                [ $this, 'controlador_display_menu' ],
                '',
                65
            );

            if (! empty($this->get_token_eship())) {
                $this->build_menupage->add_submenu_page(
                    'eship_dashboard',
                    'configuration',
                    'configuration',
                    'manage_options',
                    'eship_configuration',
                    [ $this, 'controlador_display_submenu_configuration' ]
                );

                $this->build_menupage->add_submenu_page(
                    'eship_dashboard',
                    'Tracking Guide',
                    'tracking guide',
                    'manage_options',
                    'eship_tracking_guide',
                    [ $this, 'controlador_display_submenu_tracking_guide' ]
                );
            }

            $this->build_menupage->run();
        }

        public function add_metabox_eship(){
            if (empty($this->get_token_eship())) {
                $register_view = [$this, 'view_register_eship'];
                $register_title = "<img src='" .ESHIP_PLUGIN_DIR_URL . 'admin/img/eshipw.png' . "' class='w-30 h-30'>";
            }  else {
                $register_view = [$this, 'view_buttons_eship'];
                $register_title = "<img class='img-thumbnail' style='background-color: #1f61ad; width:80%;' src='" .ESHIP_PLUGIN_DIR_URL . 'admin/img/eshipw.png' . "'>";
            }

            add_meta_box(
                'woocommerce-order-eship',
                __($register_title, 'woocommerce'),
                $register_view,
                'shop_order',
                'side',
                'high'
            );
        }

        public function view_buttons_eship()
        {
            $post   = '';
            $res_wc = FALSE;
            $res_wc_settings = FALSE;
            if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit') {
                $post   = $_GET['post'];
                $parameters = array(
                    'id' => $post
                );
                $res_wc = $this->woocommerce_conn_eship('order_get', $parameters);
                $res_wc_settings = $this->wc_settings_eship();
            }

            $modal_custom = ESHIP_PLUGIN_DIR_PATH . 'admin/partials/buttons_modals/modal_custom.php';
            require_once ESHIP_PLUGIN_DIR_PATH . 'admin/partials/buttons_modals/buttons.php';
        }

        private function wc_settings_eship()
        {
            $res_wc_settings = $this->woocommerce_conn_eship('settings_general');
            $data = array();
            foreach ($res_wc_settings as $key) {
                $address = FALSE;
                if ($key->id  == 'woocommerce_store_address') {
                    $address = $key->value;
                }

                $address_2 = FALSE;
                if ($key->id == 'woocommerce_store_address_2') {
                    $address_2 = $key->value;
                }

                $city = FALSE;
                if ($key->id == 'woocommerce_store_city') {
                    $city = $key->value;
                }

                $postcode = FALSE;
                if ($key->id == 'woocommerce_store_postcode') {
                    $postcode = $key->value;
                }

                $country = FALSE;
                if ($key->id == 'woocommerce_default_country') {
                    $country = $key->value;
                }

                $new_wc_data = array(
                    'address'   => $address,
                    'address2'  => $address_2,
                    'city'      => $city,
                    'postcode'  => $postcode,
                    'country'   => $country,
                );
                array_push($data, $new_wc_data);

            }
            return $data;
        }

        private function view_register_eship()
        {
            return ESHIP_PLUGIN_DIR_PATH . 'admin/partials/connection/connection.php';
        }

        public function controlador_display_submenu_quotes() 
        {
            $res_quotation = wp_remote_get( 'https://api.myeship.co/rest/quotation', array(
                'headers' => array(
                    'content-Type' => 'Application/json',
                    'api-key' => 'eship_prod_835261c341f8465b2'
                )
            ));
            $res_shipment = wp_remote_get( 'https://api.myeship.co/rest/shipment', array(
                'headers' => array(
                    'content-Type' => 'Application/json',
                    'api-key' => 'eship_prod_835261c341f8465b2'
                )
            ));
            $res_batch_shipment = wp_remote_get( 'https://api.myeship.co/rest/batch_shipment', array(
                'headers' => array(
                    'content-Type' => 'Application/json',
                    'api-key' => 'eship_prod_835261c341f8465b2'
                )
            ));
            $res_label_settings = wp_remote_get( 'https://api.myeship.co/rest/label_settings', array(
                'headers' => array(
                    'content-Type' => 'Application/json',
                    'api-key' => 'eship_prod_835261c341f8465b2'
                )
            ));
            $res_pickup = wp_remote_get( 'https://api.myeship.co/rest/pickup', array(
                'headers' => array(
                    'content-Type' => 'Application/json',
                    'api-key' => 'eship_prod_835261c341f8465b2'
                )
            ));
        }

        public function get_orders_wc_eship() {
            $list_orders = $this->woocommerce_conn_eship('list_orders');
            $new_list_orders = array();

            if (! empty($list_orders)) {
                foreach ($list_orders as $key_orders) {
                    $shipping = '';
                    $line_items = '';
                    if(isset($key_orders->shipping->first_name) && !empty($key_orders->shipping->first_name)) {
                        $shipping .= $key_orders->shipping->first_name . " ";
                    }

                    if (isset($key_orders->shipping->last_name) && !empty($key_orders->shipping->last_name)) {
                        $shipping .= $key_orders->shipping->first_name . " ";
                    }

                    if (isset($key_orders->shipping->company) && !empty($key_orders->shipping->company)) {
                        $shipping .= $key_orders->shipping->company . " ";
                    }

                    if (isset($key_orders->shipping->address_1) && !empty($key_orders->shipping->address_1)) {
                        $shipping .= $key_orders->shipping->address_1 . " ";
                    }

                    if (isset($key_orders->shipping->address_1) && !empty($key_orders->shipping->address_1)) {
                        $shipping .= $key_orders->shipping->address_1 . " ";
                    }

                    if (isset($key_orders->shipping->address_2) && !empty($key_orders->shipping->address_2)) {
                        $shipping .= $key_orders->shipping->address_2 . " ";
                    }

                    if (isset($key_orders->shipping->city) && !empty($key_orders->shipping->city)) {
                        $shipping .= $key_orders->shipping->city . " ";
                    }

                    if (isset($key_orders->shipping->state) && !empty($key_orders->shipping->state)) {
                        $shipping .= $key_orders->shipping->state . " ";
                    }

                    if (isset($key_orders->shipping->postcode) && !empty($key_orders->shipping->postcode)) {
                        $shipping .= $key_orders->shipping->postcode . " ";
                    }

                    if (isset($key_orders->shipping->country) && !empty($key_orders->shipping->country)) {
                        $shipping .= $key_orders->shipping->country . " ";
                    }

                    if (isset($key_orders->line_items) && !empty($key_orders->line_items)) {
                        foreach ($key_orders->line_items as $item) {
                            //var_dump($item);
                            $line_items .= $item->name . ' ';
                            $line_items .= (!empty($line_items))? $item->quantity . ' ' : '';
                            $line_items .= (!empty($line_items))? $item->total . ' ' : '';
                            $line_items .= (!empty($line_items))? ' ' : '';
                        }
                    }

                    $arr =  array(
                        'id'                => $key_orders->id,
                        'status'            => $key_orders->status,
                        'number'            => $key_orders->number,
                        'shipping_total'    => $key_orders->shipping_total,
                        'billing'           => $key_orders->billing,
                        'shipping_tax'      => $key_orders->shipping_tax,
                        'total'             => $key_orders->total,
                        'shipping'          => (!empty($shipping))? $shipping : ''  ,
                        'currency'          => $key_orders->currency,
                        'line_items'        => $line_items,
                        'date_created'      => $key_orders->date_created
                    );
                    array_push($new_list_orders, $arr);
                }

                $response = array(
                    'result'    => $new_list_orders,
                    'error'     => FALSE,
                    'code'      => 202
                );
            } else {
                $response = array(
                    'result'    => $new_list_orders,
                    'error'     => TRUE,
                    'code'      => 404
                );
            }

            wp_send_json($response);
        }

        public function insert_token_eship()
        {
            check_ajax_referer('eship_sec', 'nonce');
            $response = array();
            if(current_user_can('manage_options')) {
                $adm = wp_get_current_user();
                extract($_POST, EXTR_OVERWRITE);

                if($typeAction == 'add_token') {
                    $columns = [
                        'email'         => $adm->user_email,
                        'token_eship'   => $token,
                        'data'          => NULL,
                    ];
                    
                    $format = [
                        "%s",
                        "%s",
                        "%s"
                    ];
        
                    $result = $this->db->insert( ESHIP_TB, $columns, $format );
                    if ($result > 0) {
                        $response = array(
                            'result'    => 'Se inserto token',
                            'redirect'  => '?page=eship_dashboard',
                            'error'     => FALSE,
                            'code'      => 201
                        );
                    } else  {
                        $response = array(
                            'result'    => 'No se inserto tu token',
                            'redirect'  => '?page=eship_dashboard',
                            'error'     => TRUE,
                            'code'      => 404
                        );
                    }

                }

                echo json_encode($response);
                wp_die();
            }
            
        }

        public function woocommerce_conn_eship($type, $parameters = FALSE) {
            $woocommerce = new Client(
                //Page principal
                /*
                'http://18.191.235.204/wp-plugin-eship',
                'ck_e1e2f573ca6d3237a02a7442952fa37806ef47ea',
                'cs_fc047f331954ffa83623ed0f47c927afee406438',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
                */
                // Sitio de pruebas
                'http://wp.eship.mylocal:8888',
                'ck_8fc0c1a4fbc9fd2137a6b75c2728908f9346eb15',
                'cs_46c0207837b87425d850fff14656ffef0621b4bc',
                [
                    'wp_api' => true,
                    'version' => 'wc/v3'
                ]
            );

            //https://github.com/woocommerce/wc-api-php
            switch($type) {
                case 'settings_general':
                    return $woocommerce->get('settings/general');
                case 'list_orders':
                    return $woocommerce->get('orders');
                case 'order_get':
                    return $woocommerce->get('orders', $parameters);
                default:
                    return $woocommerce->get('');
            }
        }

        private function get_token_eship()
        {
            $results = $this->db->get_results( "SELECT * FROM " . ESHIP_TB . ";", OBJECT );
            if ((count($results) > 0) && (isset($results[0]->token_eship)) && (! is_null($results[0]->token_eship))) {
                return $results[0]->token_eship;
            } else  {
                return '';
            }
        }
    }

