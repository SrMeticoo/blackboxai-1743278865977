<?php
/**
 * Template para el modal de lecciones restringidas
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener configuración del plugin
$settings = get_option('rl_settings', array());
$modal_title = isset($settings['rl_modal_title']) 
    ? $settings['rl_modal_title'] 
    : __('Contenido Restringido', 'restrict-lessons');
$modal_message = isset($settings['rl_modal_message']) 
    ? $settings['rl_modal_message'] 
    : __('Este contenido requiere una compra adicional.', 'restrict-lessons');
$button_text = isset($settings['rl_button_text']) 
    ? $settings['rl_button_text'] 
    : __('Comprar Actualización', 'restrict-lessons');

// Obtener información del producto
$product = wc_get_product($product_id);
if (!$product) {
    return;
}

// Obtener URL de imagen de fondo (usando Unsplash para una imagen profesional)
$background_image = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80';
?>

<div class="rl-modal-content">
    <div class="rl-modal-header" style="background-image: url('<?php echo esc_url($background_image); ?>');">
        <h3><?php echo esc_html($modal_title); ?></h3>
        <button type="button" class="rl-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'restrict-lessons'); ?>">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="rl-modal-body">
        <div class="rl-lock-icon">
            <i class="fas fa-lock fa-3x"></i>
        </div>

        <div class="rl-modal-message">
            <p><?php echo esc_html($modal_message); ?></p>
        </div>

        <div class="rl-product-info">
            <div class="rl-product-header">
                <?php if ($product->get_image_id()): ?>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')); ?>" 
                         alt="<?php echo esc_attr($product->get_name()); ?>"
                         class="rl-product-image">
                <?php endif; ?>
                <h4><?php echo esc_html($product->get_name()); ?></h4>
            </div>

            <div class="rl-product-details">
                <div class="rl-price-container">
                    <span class="rl-price-label"><?php esc_html_e('Precio:', 'restrict-lessons'); ?></span>
                    <span class="rl-price-amount"><?php echo $product->get_price_html(); ?></span>
                </div>

                <?php if ($update_year): ?>
                    <div class="rl-update-info">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php 
                            printf(
                                esc_html__('Actualización para el año %d', 'restrict-lessons'),
                                $update_year
                            ); 
                        ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($product->get_short_description()): ?>
                    <div class="rl-product-description">
                        <?php echo wp_kses_post($product->get_short_description()); ?>
                    </div>
                <?php endif; ?>

                <?php
                // Mostrar descuento si el usuario ha comprado actualizaciones anteriores
                $discount = apply_filters('rl_update_discount', 0, get_current_user_id(), $product_id);
                if ($discount > 0):
                ?>
                    <div class="rl-discount-info">
                        <i class="fas fa-tag"></i>
                        <span><?php 
                            printf(
                                esc_html__('¡Descuento del %d%% disponible!', 'restrict-lessons'),
                                $discount
                            ); 
                        ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rl-modal-footer">
        <a href="<?php echo esc_url($product->get_permalink()); ?>" 
           class="rl-purchase-button">
            <i class="fas fa-shopping-cart"></i>
            <?php echo esc_html($button_text); ?>
        </a>

        <?php if (is_user_logged_in()): ?>
            <p class="rl-login-note">
                <?php esc_html_e('Accederás al contenido inmediatamente después de la compra.', 'restrict-lessons'); ?>
            </p>
        <?php else: ?>
            <p class="rl-login-note">
                <?php 
                printf(
                    esc_html__('Por favor %1$sinicia sesión%2$s o %3$sregístrate%4$s para comprar.', 'restrict-lessons'),
                    '<a href="' . esc_url(wp_login_url(get_permalink())) . '">',
                    '</a>',
                    '<a href="' . esc_url(wp_registration_url()) . '">',
                    '</a>'
                ); 
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>