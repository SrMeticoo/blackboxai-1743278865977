/**
 * Frontend JavaScript para Restrict Lessons
 */
(function($) {
    'use strict';

    // Clase principal
    class RestrictLessons {
        constructor() {
            this.modalTemplate = this.createModalTemplate();
            this.init();
        }

        init() {
            this.initializeEventListeners();
            this.appendModalToBody();
        }

        createModalTemplate() {
            const modal = document.createElement('div');
            modal.className = 'rl-modal';
            modal.innerHTML = '<div class="rl-modal-wrapper"></div>';
            return modal;
        }

        appendModalToBody() {
            document.body.appendChild(this.modalTemplate);
        }

        initializeEventListeners() {
            // Click en lecciones restringidas
            $(document).on('click', '.rl-restricted-lesson a', (e) => {
                e.preventDefault();
                const lessonId = $(e.currentTarget).closest('.rl-restricted-lesson').data('lesson-id');
                this.handleRestrictedLessonClick(lessonId);
            });

            // Cerrar modal
            $(document).on('click', '.rl-modal-close, .rl-modal', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });

            // Escape para cerrar modal
            $(document).on('keyup', (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            });
        }

        handleRestrictedLessonClick(lessonId) {
            if (!lessonId) return;

            this.showLoading();

            // Solicitar contenido del modal
            $.ajax({
                url: rlData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_lesson_access_modal',
                    lesson_id: lessonId,
                    nonce: rlData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showModal(response.data.content);
                    } else {
                        this.handleError(response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.handleError(error);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }

        showModal(content) {
            const modalWrapper = this.modalTemplate.querySelector('.rl-modal-wrapper');
            modalWrapper.innerHTML = content;
            this.modalTemplate.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Animación de entrada
            requestAnimationFrame(() => {
                modalWrapper.querySelector('.rl-modal-content').classList.add('rl-fade-in');
            });
        }

        closeModal() {
            const modalContent = this.modalTemplate.querySelector('.rl-modal-content');
            modalContent.classList.remove('rl-fade-in');
            
            // Esperar a que termine la animación
            setTimeout(() => {
                this.modalTemplate.classList.remove('active');
                document.body.style.overflow = '';
            }, 300);
        }

        showLoading() {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'rl-loading';
            document.body.appendChild(loadingDiv);
        }

        hideLoading() {
            const loadingDiv = document.querySelector('.rl-loading');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }

        handleError(message) {
            console.error('Error:', message);
            // Mostrar mensaje de error al usuario
            const errorMessage = message || rlData.i18n.errorMessage || 'Ha ocurrido un error';
            
            // Crear y mostrar un toast de error
            this.showToast(errorMessage, 'error');
        }

        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `rl-toast rl-toast-${type}`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            // Animación de entrada
            requestAnimationFrame(() => {
                toast.classList.add('rl-toast-show');
            });

            // Remover después de 3 segundos
            setTimeout(() => {
                toast.classList.remove('rl-toast-show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        window.restrictLessons = new RestrictLessons();
    });

    // Funciones auxiliares
    function updateLessonStatus() {
        $('.rl-restricted-lesson').each(function() {
            const $lesson = $(this);
            const lessonId = $lesson.data('lesson-id');

            $.ajax({
                url: rlData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_lesson_access',
                    lesson_id: lessonId,
                    nonce: rlData.nonce
                },
                success: (response) => {
                    if (response.success && response.data.has_access) {
                        // Animación suave al desbloquear
                        $lesson.addClass('rl-unlocking');
                        setTimeout(() => {
                            $lesson.removeClass('rl-restricted-lesson rl-unlocking');
                            $lesson.find('.fa-lock').fadeOut(() => {
                                $(this).remove();
                            });
                        }, 500);
                    }
                }
            });
        });
    }

    // Actualizar estado de lecciones después de una compra exitosa
    $(document).on('wc_cart_button_updated', () => {
        updateLessonStatus();
    });

    // Actualizar estado cuando WooCommerce confirma una compra
    $(document).on('wc_fragments_refreshed', () => {
        updateLessonStatus();
    });

    // Manejar cambios en el carrito
    $(document).on('added_to_cart removed_from_cart', () => {
        updateLessonStatus();
    });

})(jQuery);