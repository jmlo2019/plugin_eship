    <?php
    use EshipAdmin\ESHIP_Quotation;
    use EshipAdmin\ESHIP_Shipment;
    use EshipAdmin\ESHIP_Model;
    use EshipAdmin\ESHIP_Woocommerce_Api;
    use EshipAdmin\ESHIP_Admin_Notices;

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
        private $eship_quotation;
        private $eship_model;

        public function __construct( $plugin_name, $version ) 
        {
            $this->plugin_name      = $plugin_name;
            $this->version          = $version;
            $this->eship_quotation  = new ESHIP_Quotation();
            $this->eship_model      = new ESHIP_Model();
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

            wp_enqueue_script( 'eship_validation_jquery_admin_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js', array('jquery'), 'v1.19.3', TRUE );
            wp_enqueue_script( 'eship_validation_jquery_methods_admin_js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/additional-methods.min.js', array('jquery'), 'v1.19.3', TRUE );

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

        public function check_data_user_eship() {
            $arr_user = $this->eship_model->get_data_user_eship();
            $message = FALSE;
            if ($arr_user) {
                $woo_api = new ESHIP_Woocommerce_Api();
                try {
                    $woo_api->test();
                } catch (Exception $e) {
                    $message = $e->getMessage();
                }
            }

            return $message;
        }

        public function add_meta_boxes_eship()
        {
            $register_view  = 'view_buttons_eship';
            $register_title = "<img class='img-thumbnail' style='max-width:75px;' src='" . ESHIP_PLUGIN_DIR_URL . 'admin/img/eship.png' . "'>";

            if (empty($this->eship_model->get_data_user_eship())) {
                $register_view  = 'view_register_eship';
                $register_title = "<img src='" . ESHIP_PLUGIN_DIR_URL . 'admin/img/eship.png' . "' style='max-width:75px;'>";
            }

            $meta_box = array(
                'id'        => 'woocommerce-order-eship',
                'title'     => $register_title,
                'callback'  => [$this, $register_view],
                'view'      => 'shop_order',
                'context'   => 'side',
                'priority'  => 'high'
            );

            $add_meta_box = new ESHIP_Build_Add_Meta_Box($meta_box);
            $add_meta_box->run();
        }

        public function view_buttons_eship()
        {
            if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit') {
                $order  = $_GET['post'];
                //$result = $this->eship_quotation->create($order);
            }

            $form_data              = $this->eship_model->get_data_user_eship();
            $btn_account_ak_modal   = 'Update Data';
            $id_api_key             = 'updateDataEshipModal';
            $text_api_key           = 'To obtain your eShip API key, you login into your eShip account 
                                           <a href="https://app.myeship.co/" target="_blank">(app.myeship.co)</a>, go to 
                                           "Settings" and click on "View your API Key".';
            $text_title_api_key     = 'Update Data';
            $show_btn_update        = TRUE;
            $modal_token            = ESHIP_PLUGIN_DIR_PATH . 'admin/partials/connection/_form_connection.php';
            $modal_custom  = ESHIP_PLUGIN_DIR_PATH . 'admin/partials/buttons_modals/modal_custom.php';
            
            require_once ESHIP_PLUGIN_DIR_PATH . 'admin/partials/buttons_modals/buttons.php';
        }

        public function view_register_eship()
        {
            $error_eship = $this->check_data_user_eship();
            $text_modal_ak = 'Connect to ESHIP';
            $text_title_api_key = 'Register API Key';
            $text_api_key = 'To obtain your eShip API key, you login into your eShip account 
                             <a href="https://app.myeship.co/" target="_blank">(app.myeship.co)</a>, go to 
                             "Settings" and click on "View your API Key".';
            $id_api_key = 'tokenEshipModal';
            $btn_account_ak_modal = 'Register API Key';
            $title_eship_account = 'I do not have an account of ESHIP';
            $text_eship_account = '';
            $btn_account_ak = 'I have ESHIP account';
            $btn_account_ak_text = '';
            $btn_account = 'Register Now';
            $btn_account_link = 'https://app.myeship.co/en/login';
            $modal_token =  ESHIP_PLUGIN_DIR_PATH . 'admin/partials/connection/_form_connection.php';
            require_once ESHIP_PLUGIN_DIR_PATH . 'admin/partials/connection/connection.php';
        }

        public function get_shipment_eship()
        {
            check_ajax_referer('eship_sec', 'nonce');
            $response = array();

            if(current_user_can('manage_options')) {
                $result = FALSE;
                extract($_POST, EXTR_OVERWRITE);

                if($typeAction == 'create_shipment') {
                    $shipment   = new ESHIP_Shipment($rateId);
                    $result     = $shipment->getShipment();
                    //var_dump($result);
                    //die();
                    $result     = json_decode($result);
                    #TODO Idea Register el documento en la base de datos
                }

                if ($result) {
                    $response = array(
                        'result'    => $result,
                        'redirect'  => '?page=eship_dashboard',
                        'error'     => FALSE,
                        'code'      => 201
                    );
                } else  {
                    $response = array(
                        'result'    => $result,//'No se genero tu guía',
                        'redirect'  => FALSE,
                        'error'     => TRUE,
                        'code'      => 404
                    );
                }

                echo json_encode($response);
                wp_die();
            }
        }

        public function get_quotation_eship()
        {
            check_ajax_referer('eship_sec', 'nonce');
            $result = FALSE;
            if (isset($_POST['order_id'])) {
                $result = $this->eship_quotation->create($_POST['order_id']);
                $result = json_decode($result);
                //$result = htmlentities($result);

                if ($result && !(isset($result->error))) {
                    $response = array(
                        'result'    => $result,
                        'redirect'  => FALSE,
                        'error'     => FALSE,
                        'code'      => 201
                    );
                } else  {
                    $response = array(
                        'result'    => $result->result,
                        'error'     => TRUE,
                        'code'      => 404
                    );
                }

            } else {
                $response = array(
                    'result'    => 'No se conecto con las APIs',
                    'error'     => TRUE,
                    'code'      => 500
                );
            }
            echo json_encode($response);
            wp_die();
        }

        public function insert_token_eship()
        {
            check_ajax_referer('eship_sec', 'nonce');
            $result = $this->eship_model->insert_data_store_eship($_POST);

            if (!is_null($result)) {
                $response = array(
                    'result'    => 'Exito',
                    'redirect'  => TRUE,
                    'error'     => FALSE,
                    'code'      => 201
                );
            } else  {
                $response = array(
                    'result'    => 'Error',
                    'error'     => TRUE,
                    'code'      => 404
                );
            }

            echo json_encode($response);
            wp_die();
        }

        public function update_token_eship()
        {
            check_ajax_referer('eship_sec', 'nonce');
            $result = $this->eship_model->update_data_store_eship($_POST);

            if (!is_null($result)) {
                $response = array(
                    'result'    => 'Done!',
                    'redirect'  => TRUE,
                    'error'     => FALSE,
                    'code'      => 201
                );
            } else  {
                $response = array(
                    'result'    => 'No updated',
                    'error'     => TRUE,
                    'code'      => 404
                );
            }

            echo json_encode($response);
            wp_die();
        }
    }

