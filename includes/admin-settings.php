<?php
/**
 * Manejo de la configuración administrativa del plugin
 */

class RL_Admin_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_lesson_meta_boxes'));
        add_action('save_post', array($this, 'save_lesson_meta'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tutor',
            __('Configuración de Lecciones Restringidas', 'restrict-lessons'),
            __('Lecciones Restringidas', 'restrict-lessons'),
            'manage_options',
            'restrict-lessons-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('restrict-lessons', 'rl_settings');

        add_settings_section(
            'rl_general_settings',
            __('Configuración General', 'restrict-lessons'),
            array($this, 'render_general_section'),
            'restrict-lessons'
        );

        add_settings_field(
            'rl_modal_title',
            __('Título del Modal', 'restrict-lessons'),
            array($this, 'render_text_field'),
            'restrict-lessons',
            'rl_general_settings',
            array(
                'label_for' => 'rl_modal_title',
                'default' => __('Contenido Restringido', 'restrict-lessons')
            )
        );

        add_settings_field(
            'rl_modal_message',
            __('Mensaje del Modal', 'restrict-lessons'),
            array($this, 'render_textarea_field'),
            'restrict-lessons',
            'rl_general_settings',
            array(
                'label_for' => 'rl_modal_message',
                'default' => __('Este contenido requiere una compra adicional.', 'restrict-lessons')
            )
        );

        add_settings_field(
            'rl_button_text',
            __('Texto del Botón de Compra', 'restrict-lessons'),
            array($this, 'render_text_field'),
            'restrict-lessons',
            'rl_general_settings',
            array(
                'label_for' => 'rl_button_text',
                'default' => __('Comprar Actualización', 'restrict-lessons')
            )
        );
    }

    /**
     * Renderizar la página de configuración
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'rl_messages',
                'rl_message',
                __('Configuración guardada', 'restrict-lessons'),
                'updated'
            );
        }

        settings_errors('rl_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('restrict-lessons');
                do_settings_sections('restrict-lessons');
                submit_button(__('Guardar Cambios', 'restrict-lessons'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renderizar sección general
     */
    public function render_general_section($args) {
        ?>
        <p><?php _e('Configura las opciones generales para las lecciones restringidas.', 'restrict-lessons'); ?></p>
        <?php
    }

    /**
     * Renderizar campo de texto
     */
    public function render_text_field($args) {
        $options = get_option('rl_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <input
            type="text"
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="rl_settings[<?php echo esc_attr($args['label_for']); ?>]"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
        >
        <?php
    }

    /**
     * Renderizar campo de texto multilínea
     */
    public function render_textarea_field($args) {
        $options = get_option('rl_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <textarea
            id="<?php echo esc_attr($args['label_for']); ?>"
            name="rl_settings[<?php echo esc_attr($args['label_for']); ?>]"
            class="large-text"
            rows="3"
        ><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    /**
     * Agregar meta boxes a las lecciones
     */
    public function add_lesson_meta_boxes() {
        add_meta_box(
            'rl_lesson_restrictions',
            __('Restricciones de la Lección', 'restrict-lessons'),
            array($this, 'render_lesson_meta_box'),
            'lesson',
            'side',
            'default'
        );
    }

    /**
     * Renderizar meta box de lección
     */
    public function render_lesson_meta_box($post) {
        wp_nonce_field('rl_lesson_meta', 'rl_lesson_meta_nonce');

        $is_restricted = get_post_meta($post->ID, 'rl_is_restricted', true);
        $product_id = get_post_meta($post->ID, 'rl_product_id', true);
        $update_year = get_post_meta($post->ID, 'rl_update_year', true);

        // Obtener productos de WooCommerce
        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        <p>
            <label>
                <input 
                    type="checkbox" 
                    name="rl_is_restricted" 
                    value="1" 
                    <?php checked($is_restricted, '1'); ?>
                >
                <?php _e('Lección Restringida', 'restrict-lessons'); ?>
            </label>
        </p>

        <p>
            <label for="rl_product_id"><?php _e('Producto de Actualización:', 'restrict-lessons'); ?></label>
            <select name="rl_product_id" id="rl_product_id" class="widefat">
                <option value=""><?php _e('Seleccionar producto...', 'restrict-lessons'); ?></option>
                <?php
                foreach ($products as $product) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($product->get_id()),
                        selected($product_id, $product->get_id(), false),
                        esc_html($product->get_name())
                    );
                }
                ?>
            </select>
        </p>

        <p>
            <label for="rl_update_year"><?php _e('Año de Actualización:', 'restrict-lessons'); ?></label>
            <select name="rl_update_year" id="rl_update_year" class="widefat">
                <?php
                $current_year = intval(date('Y'));
                for ($year = $current_year; $year <= $current_year + 5; $year++) {
                    printf(
                        '<option value="%d" %s>%d</option>',
                        $year,
                        selected($update_year, $year, false),
                        $year
                    );
                }
                ?>
            </select>
        </p>
        <?php
    }

    /**
     * Guardar meta datos de la lección
     */
    public function save_lesson_meta($post_id) {
        if (!isset($_POST['rl_lesson_meta_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['rl_lesson_meta_nonce'], 'rl_lesson_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar estado de restricción
        $is_restricted = isset($_POST['rl_is_restricted']) ? '1' : '';
        update_post_meta($post_id, 'rl_is_restricted', $is_restricted);

        // Guardar ID del producto
        if (isset($_POST['rl_product_id'])) {
            update_post_meta($post_id, 'rl_product_id', sanitize_text_field($_POST['rl_product_id']));
        }

        // Guardar año de actualización
        if (isset($_POST['rl_update_year'])) {
            update_post_meta($post_id, 'rl_update_year', sanitize_text_field($_POST['rl_update_year']));
        }
    }
}

// Inicializar la clase de configuración administrativa
new RL_Admin_Settings();