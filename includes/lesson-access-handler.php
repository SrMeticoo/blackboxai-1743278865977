<?php
/**
 * Manejo del acceso a lecciones restringidas
 */
class RL_Lesson_Access_Handler {
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('tutor_lesson_content', array($this, 'filter_lesson_content'), 10, 2);
        add_action('wp_ajax_get_lesson_access_modal', array($this, 'get_lesson_access_modal'));
        add_action('wp_ajax_nopriv_get_lesson_access_modal', array($this, 'get_lesson_access_modal'));
        add_filter('tutor_course_complete_conditions', array($this, 'modify_course_completion_conditions'), 10, 2);
    }

    /**
     * Filtrar el contenido de la lección
     */
    public function filter_lesson_content($content, $lesson_id) {
        if (!$this->is_lesson_restricted($lesson_id)) {
            return $content;
        }

        if ($this->user_has_access($lesson_id)) {
            return $content;
        }

        // Obtener la configuración del modal
        $settings = get_option('rl_settings', array());
        $modal_message = isset($settings['rl_modal_message']) 
            ? $settings['rl_modal_message'] 
            : __('Este contenido requiere una compra adicional.', 'restrict-lessons');

        // Renderizar mensaje de restricción
        $output = '<div class="rl-restricted-content">';
        $output .= '<div class="rl-lock-icon"><i class="fas fa-lock fa-3x"></i></div>';
        $output .= '<h3>' . esc_html__('Contenido Restringido', 'restrict-lessons') . '</h3>';
        $output .= '<p>' . esc_html($modal_message) . '</p>';
        $output .= $this->get_purchase_button($lesson_id);
        $output .= '</div>';

        return $output;
    }

    /**
     * Verificar si una lección está restringida
     */
    private function is_lesson_restricted($lesson_id) {
        return (bool) get_post_meta($lesson_id, 'rl_is_restricted', true);
    }

    /**
     * Verificar si el usuario tiene acceso a la lección
     */
    private function user_has_access($lesson_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $product_id = get_post_meta($lesson_id, 'rl_product_id', true);
        $update_year = get_post_meta($lesson_id, 'rl_update_year', true);

        // Verificar si el usuario es nuevo (compra después del año de actualización)
        $first_purchase_date = $this->get_user_first_course_purchase($user_id);
        if ($first_purchase_date && strtotime($first_purchase_date) > strtotime($update_year . '-01-01')) {
            return true;
        }

        // Verificar si el usuario ha comprado la actualización
        return $this->has_purchased_update($user_id, $product_id);
    }

    /**
     * Obtener la fecha de la primera compra del curso
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
     * Verificar si el usuario ha comprado una actualización
     */
    private function has_purchased_update($user_id, $product_id) {
        return wc_customer_bought_product('', $user_id, $product_id);
    }

    /**
     * Obtener el botón de compra
     */
    private function get_purchase_button($lesson_id) {
        $product_id = get_post_meta($lesson_id, 'rl_product_id', true);
        if (!$product_id) {
            return '';
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return '';
        }

        $settings = get_option('rl_settings', array());
        $button_text = isset($settings['rl_button_text']) 
            ? $settings['rl_button_text'] 
            : __('Comprar Actualización', 'restrict-lessons');

        $output = '<div class="rl-purchase-button-container">';
        $output .= sprintf(
            '<a href="%s" class="rl-purchase-button tutor-btn tutor-btn-primary">%s</a>',
            esc_url($product->get_permalink()),
            esc_html($button_text)
        );
        $output .= '</div>';

        return $output;
    }

    /**
     * Obtener contenido del modal de acceso
     */
    public function get_lesson_access_modal() {
        // Verificar nonce
        check_ajax_referer('rl-frontend-nonce', 'nonce');

        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        if (!$lesson_id) {
            wp_send_json_error(array('message' => __('Lección no válida', 'restrict-lessons')));
        }

        $product_id = get_post_meta($lesson_id, 'rl_product_id', true);
        $update_year = get_post_meta($lesson_id, 'rl_update_year', true);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(array('message' => __('Producto no encontrado', 'restrict-lessons')));
        }

        $settings = get_option('rl_settings', array());
        $modal_title = isset($settings['rl_modal_title']) 
            ? $settings['rl_modal_title'] 
            : __('Contenido Restringido', 'restrict-lessons');
        $modal_message = isset($settings['rl_modal_message']) 
            ? $settings['rl_modal_message'] 
            : __('Este contenido requiere una compra adicional.', 'restrict-lessons');

        ob_start();
        ?>
        <div class="rl-modal-content">
            <div class="rl-modal-header">
                <h3><?php echo esc_html($modal_title); ?></h3>
                <button class="rl-modal-close">&times;</button>
            </div>
            <div class="rl-modal-body">
                <div class="rl-lock-icon">
                    <i class="fas fa-lock fa-3x"></i>
                </div>
                <p><?php echo esc_html($modal_message); ?></p>
                <div class="rl-product-info">
                    <h4><?php echo esc_html($product->get_name()); ?></h4>
                    <p class="rl-product-price"><?php echo $product->get_price_html(); ?></p>
                    <p class="rl-update-year">
                        <?php 
                        printf(
                            esc_html__('Actualización para el año %d', 'restrict-lessons'),
                            $update_year
                        ); 
                        ?>
                    </p>
                </div>
            </div>
            <div class="rl-modal-footer">
                <a href="<?php echo esc_url($product->get_permalink()); ?>" class="rl-purchase-button tutor-btn tutor-btn-primary">
                    <?php 
                    echo isset($settings['rl_button_text']) 
                        ? esc_html($settings['rl_button_text'])
                        : esc_html__('Comprar Actualización', 'restrict-lessons');
                    ?>
                </a>
            </div>
        </div>
        <?php
        $modal_content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $modal_content
        ));
    }

    /**
     * Modificar las condiciones de finalización del curso
     */
    public function modify_course_completion_conditions($conditions, $course_id) {
        // No contar las lecciones restringidas para la finalización del curso
        if (isset($conditions['lessons'])) {
            $lessons = tutor_utils()->get_course_contents_by_topic($course_id, -1);
            $restricted_count = 0;

            foreach ($lessons as $lesson) {
                if ($this->is_lesson_restricted($lesson->ID) && !$this->user_has_access($lesson->ID)) {
                    $restricted_count++;
                }
            }

            if ($restricted_count > 0 && isset($conditions['total_lessons'])) {
                $conditions['total_lessons'] -= $restricted_count;
            }
        }

        return $conditions;
    }
}

// Inicializar el manejador de acceso a lecciones
new RL_Lesson_Access_Handler();