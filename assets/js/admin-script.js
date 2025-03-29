/**
 * Admin JavaScript para Restrict Lessons
 */
(function($) {
    'use strict';

    class RestrictLessonsAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.initializeEventListeners();
            this.initializeProductDependency();
            this.initializeTooltips();
        }

        initializeEventListeners() {
            // Manejar cambio en checkbox de restricción
            $('#rl_is_restricted').on('change', (e) => {
                this.toggleProductFields(e.target.checked);
            });

            // Validación de formulario
            $('#post').on('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                }
            });

            // Actualización dinámica de precios
            $('#rl_product_id').on('change', (e) => {
                this.updateProductInfo($(e.target).val());
            });

            // Búsqueda en tiempo real de productos
            $('#rl_product_search').on('input', this.debounce((e) => {
                this.searchProducts(e.target.value);
            }, 300));
        }

        initializeProductDependency() {
            const isRestricted = $('#rl_is_restricted').is(':checked');
            this.toggleProductFields(isRestricted);
        }

        initializeTooltips() {
            $('.rl-help-tip').tooltipster({
                theme: 'tooltipster-light',
                maxWidth: 300,
                animation: 'fade',
                delay: 200
            });
        }

        toggleProductFields(show) {
            const $productFields = $('.rl-product-fields');
            if (show) {
                $productFields.slideDown();
            } else {
                $productFields.slideUp();
            }
        }

        validateForm() {
            const isRestricted = $('#rl_is_restricted').is(':checked');
            if (!isRestricted) return true;

            const productId = $('#rl_product_id').val();
            const updateYear = $('#rl_update_year').val();

            if (!productId) {
                this.showError('Por favor, selecciona un producto para la actualización.');
                return false;
            }

            if (!updateYear) {
                this.showError('Por favor, selecciona el año de actualización.');
                return false;
            }

            return true;
        }

        updateProductInfo(productId) {
            if (!productId) {
                this.clearProductInfo();
                return;
            }

            this.showLoading();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rl_get_product_info',
                    product_id: productId,
                    nonce: rlAdminData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayProductInfo(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('Error al obtener información del producto.');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        searchProducts(term) {
            if (term.length < 3) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'rl_search_products',
                    term: term,
                    nonce: rlAdminData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateProductDropdown(response.data);
                    }
                }
            });
        }

        updateProductDropdown(products) {
            const $select = $('#rl_product_id');
            $select.empty();
            $select.append('<option value="">' + rlAdminData.i18n.selectProduct + '</option>');

            products.forEach(product => {
                $select.append(`<option value="${product.id}">${product.name} - ${product.price}</option>`);
            });
        }

        displayProductInfo(info) {
            const $infoContainer = $('.rl-product-info');
            $infoContainer.html(`
                <div class="rl-product-preview">
                    <img src="${info.image}" alt="${info.name}">
                    <div class="rl-product-details">
                        <h4>${info.name}</h4>
                        <p class="rl-price">${info.price}</p>
                        <p class="rl-stock">${info.stock_status}</p>
                    </div>
                </div>
            `).slideDown();
        }

        clearProductInfo() {
            $('.rl-product-info').empty().slideUp();
        }

        showLoading() {
            $('.rl-loading-overlay').fadeIn();
        }

        hideLoading() {
            $('.rl-loading-overlay').fadeOut();
        }

        showError(message) {
            const $notice = $('<div class="notice notice-error is-dismissible"><p></p></div>');
            $notice.find('p').text(message);
            
            $('.wrap h1').after($notice);
            
            // Hacer desaparecer después de 5 segundos
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
        }

        // Utilidad para debounce
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        new RestrictLessonsAdmin();
    });

})(jQuery);