<?php
/**
 * Clase principal del plugin Restrict Lessons
 */
class RestrictLessons {
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Inicializar propiedades si es necesario
    }

    /**
     * Inicializar el plugin
     */
    public function init() {
        // Agregar hooks necesarios
        add_action('init', array($this, 'register_post_meta'));
        add_filter('tutor_course/single/enrolled/nav_items', array($this, 'modify_course_nav_items'), 10, 2);
        add_filter('tutor_course/single/enrolled/lessons', array($this, 'filter_restricted_lessons'), 10, 2);
        add_action('wp_ajax_check_lesson_access', array($this, 'check_lesson_access'));
        add_action('wp_ajax_nopriv_check_lesson_access', array($this, 'check_lesson_access'));
    }

    /**
     * Registrar meta campos personalizados
     */
    public function register_post_meta() {
        register_post_meta('lesson', 'rl_is_restricted', array(
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_post_meta('lesson', 'rl_product_id', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_post_meta('lesson', 'rl_update_year', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }

    /**
     * Modificar elementos de navegación del curso
     */
    public function modify_course_nav_items($nav_items, $course_id) {
        if (!is_array($nav_items)) {
            return $nav_items;
        }

        foreach ($nav_items as &$item) {
            if ($item['type'] === 'lesson') {
                $is_restricted = get_post_meta($item['id'], 'rl_is_restricted', true);
                if ($is_restricted && !$this->user_has_access($item['id'])) {
                    $item['title'] = sprintf(
                        '%s <i class="fas fa-lock" title="%s"></i>',
                        $item['title'],
                        __('Contenido restringido', 'restrict-lessons')
                    );
                }
            }
        }

        return $nav_items;
    }

    /**
     * Filtrar lecciones restringidas
     */
    public function filter_restricted_lessons($lessons, $course_id) {
        if (!is_array($lessons)) {
            return $lessons;
        }

        foreach ($lessons as &$lesson) {
            $is_restricted = get_post_meta($lesson->ID, 'rl_is_restricted', true);
            if ($is_restricted && !$this->user_has_access($lesson->ID)) {
                $lesson->is_locked = true;
                $lesson->lock_icon = '<i class="fas fa-lock"></i>';
                $lesson->product_id = get_post_meta($lesson->ID, 'rl_product_id', true);
                $lesson->update_year = get_post_meta($lesson->ID, 'rl_update_year', true);
            }
        }

        return $lessons;
    }

    /**
     * Verificar si el usuario tiene acceso a una lección
     */
    private function user_has_access($lesson_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $is_restricted = get_post_meta($lesson_id, 'rl_is_restricted', true);
        
        if (!$is_restricted) {
            return true;
        }

        $product_id = get_post_meta($lesson_id, 'rl_product_id', true);
        $update_year = get_post_meta($lesson_id, 'rl_update_year', true);

        // Verificar si el usuario es nuevo (compra después del 01/01/2025)
        $first_course_purchase = $this->get_user_first_course_purchase($user_id);
        if ($first_course_purchase && strtotime($first_course_purchase) > strtotime($update_year . '-01-01')) {
            return true;
        }

        // Verificar si el usuario ha comprado la actualización
        return $this->has_purchased_update($user_id, $product_id);
    }

    /**
     * Obtener la fecha de la primera compra del curso por el usuario
     */
    private function get_user_first_course_purchase($user_id) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed'),
            'orderby' => 'date',
            'order' => 'ASC',
            'limit' => 1,
        ));

        if (!empty($orders)) {
            $first_order = reset($orders);
            return $first_order->get_date_created()->date('Y-m-d');
        }

        return false;
    }

    /**
     * Verificar si el usuario ha comprado una actualización específica
     */
    private function has_purchased_update($user_id, $product_id) {
        return wc_customer_bought_product('', $user_id, $product_id);
    }

    /**
     * Verificar acceso a lección vía AJAX
     */
    public function check_lesson_access() {
        // Verificar nonce
        if (!check_ajax_referer('rl-frontend-nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Error de seguridad', 'restrict-lessons')
            ));
        }

        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        
        if (!$lesson_id) {
            wp_send_json_error(array(
                'message' => __('Lección no válida', 'restrict-lessons')
            ));
        }

        $has_access = $this->user_has_access($lesson_id);
        $product_id = get_post_meta($lesson_id, 'rl_product_id', true);
        $update_year = get_post_meta($lesson_id, 'rl_update_year', true);

        if ($has_access) {
            wp_send_json_success(array(
                'has_access' => true,
                'message' => __('Acceso permitido', 'restrict-lessons')
            ));
        } else {
            $product = wc_get_product($product_id);
            wp_send_json_success(array(
                'has_access' => false,
                'message' => sprintf(
                    __('Esta lección es parte de la actualización %d. Por favor, compra la actualización para acceder.', 'restrict-lessons'),
                    $update_year
                ),
                'product' => array(
                    'id' => $product_id,
                    'name' => $product ? $product->get_name() : '',
                    'price' => $product ? $product->get_price_html() : '',
                    'url' => $product ? $product->get_permalink() : ''
                )
            ));
        }
    }
}