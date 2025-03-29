<?php
/**
 * Plugin Name: Restrict Lessons
 * Plugin URI: https://yourwebsite.com/restrict-lessons
 * Description: Un plugin personalizado para restringir lecciones específicas dentro de cursos de Tutor LMS y habilitarlas mediante compras adicionales en WooCommerce.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: restrict-lessons
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Si este archivo es llamado directamente, abortar
if (!defined('WPINC')) {
    die;
}

// Definir constantes del plugin
define('RL_VERSION', '1.0.0');
define('RL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Verificar dependencias del plugin
 */
function rl_check_dependencies() {
    $dependencies_met = true;
    $missing_plugins = array();

    if (!class_exists('WooCommerce')) {
        $dependencies_met = false;
        $missing_plugins[] = 'WooCommerce';
    }

    if (!class_exists('TUTOR\Tutor')) {
        $dependencies_met = false;
        $missing_plugins[] = 'Tutor LMS';
    }

    if (!$dependencies_met) {
        add_action('admin_notices', function() use ($missing_plugins) {
            $message = sprintf(
                '<div class="error"><p>%s %s</p></div>',
                __('El plugin Restrict Lessons requiere los siguientes plugins: ', 'restrict-lessons'),
                implode(', ', $missing_plugins)
            );
            echo wp_kses_post($message);
        });
        return false;
    }

    return true;
}

/**
 * Código que se ejecuta durante la activación del plugin
 */
function rl_activate() {
    if (!rl_check_dependencies()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Por favor, instala y activa WooCommerce y Tutor LMS antes de activar este plugin.', 'restrict-lessons'),
            'Plugin dependency check',
            array('back_link' => true)
        );
    }

    // Crear tablas personalizadas si es necesario
    // Configurar roles y capacidades
    add_role('rl_student', __('Estudiante RL', 'restrict-lessons'), array(
        'read' => true,
        'rl_access_restricted_content' => true
    ));
}
register_activation_hook(__FILE__, 'rl_activate');

/**
 * Código que se ejecuta durante la desactivación del plugin
 */
function rl_deactivate() {
    // Limpiar roles y capacidades
    remove_role('rl_student');
}
register_deactivation_hook(__FILE__, 'rl_deactivate');

/**
 * Inicializar el plugin
 */
function rl_init() {
    // Cargar el dominio de texto para internacionalización
    load_plugin_textdomain('restrict-lessons', false, dirname(RL_PLUGIN_BASENAME) . '/languages');

    // Incluir archivos necesarios
    require_once RL_PLUGIN_DIR . 'includes/class-restrict-lessons.php';
    require_once RL_PLUGIN_DIR . 'includes/admin-settings.php';
    require_once RL_PLUGIN_DIR . 'includes/lesson-access-handler.php';
    require_once RL_PLUGIN_DIR . 'includes/certificate-customization.php';

    // Inicializar la clase principal
    if (class_exists('RestrictLessons')) {
        $plugin = new RestrictLessons();
        $plugin->init();
    }
}
add_action('plugins_loaded', 'rl_init');

/**
 * Registrar scripts y estilos
 */
function rl_enqueue_scripts() {
    // Estilos del frontend
    wp_enqueue_style(
        'rl-frontend-style',
        RL_PLUGIN_URL . 'assets/css/frontend-style.css',
        array(),
        RL_VERSION
    );

    // Scripts del frontend
    wp_enqueue_script(
        'rl-frontend-script',
        RL_PLUGIN_URL . 'assets/js/frontend-script.js',
        array('jquery'),
        RL_VERSION,
        true
    );

    // Localizar script
    wp_localize_script('rl-frontend-script', 'rlData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rl-frontend-nonce'),
        'i18n' => array(
            'modalTitle' => __('Contenido Restringido', 'restrict-lessons'),
            'purchaseButton' => __('Comprar Actualización', 'restrict-lessons'),
            'closeModal' => __('Cerrar', 'restrict-lessons')
        )
    ));
}
add_action('wp_enqueue_scripts', 'rl_enqueue_scripts');

/**
 * Registrar scripts y estilos del admin
 */
function rl_admin_enqueue_scripts($hook) {
    // Solo cargar en páginas específicas del admin
    if (strpos($hook, 'restrict-lessons') === false) {
        return;
    }

    wp_enqueue_style(
        'rl-admin-style',
        RL_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        RL_VERSION
    );

    wp_enqueue_script(
        'rl-admin-script',
        RL_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        RL_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'rl_admin_enqueue_scripts');