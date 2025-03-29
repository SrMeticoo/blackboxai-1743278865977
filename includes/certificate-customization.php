<?php
/**
 * Personalización de certificados para excluir lecciones restringidas
 */
class RL_Certificate_Customization {
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('tutor_course_completion_percent', array($this, 'modify_completion_percentage'), 10, 2);
        add_filter('tutor_course_completed_percent', array($this, 'modify_completion_percentage'), 10, 2);
        add_filter('tutor_is_course_completed', array($this, 'check_course_completion'), 10, 3);
        add_filter('tutor_certificate_completion_data', array($this, 'modify_certificate_data'), 10, 3);
    }

    /**
     * Modificar el porcentaje de finalización del curso
     */
    public function modify_completion_percentage($percent, $course_id) {
        $user_id = get_current_user_id();
        if (!$user_id || !$course_id) {
            return $percent;
        }

        // Obtener todas las lecciones del curso
        $lessons = tutor_utils()->get_course_contents_by_topic($course_id, -1);
        if (empty($lessons)) {
            return $percent;
        }

        $total_lessons = 0;
        $completed_lessons = 0;

        foreach ($lessons as $lesson) {
            // Verificar si la lección está restringida
            $is_restricted = get_post_meta($lesson->ID, 'rl_is_restricted', true);
            
            // Si la lección no está restringida o el usuario tiene acceso, contarla
            if (!$is_restricted || $this->user_has_access($user_id, $lesson->ID)) {
                $total_lessons++;
                if ($this->is_lesson_completed($user_id, $lesson->ID)) {
                    $completed_lessons++;
                }
            }
        }

        // Calcular el nuevo porcentaje excluyendo lecciones restringidas
        if ($total_lessons > 0) {
            $percent = ($completed_lessons / $total_lessons) * 100;
        }

        return round($percent, 2);
    }

    /**
     * Verificar si el usuario tiene acceso a una lección
     */
    private function user_has_access($user_id, $lesson_id) {
        $is_restricted = get_post_meta($lesson_id, 'rl_is_restricted', true);
        if (!$is_restricted) {
            return true;
        }

        $product_id = get_post_meta($lesson_id, 'rl_product_id', true);
        $update_year = get_post_meta($lesson_id, 'rl_update_year', true);

        // Verificar si el usuario es nuevo
        $first_purchase_date = $this->get_user_first_course_purchase($user_id);
        if ($first_purchase_date && strtotime($first_purchase_date) > strtotime($update_year . '-01-01')) {
            return true;
        }

        // Verificar si ha comprado la actualización
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
     * Verificar si una lección está completada
     */
    private function is_lesson_completed($user_id, $lesson_id) {
        return tutor_utils()->is_completed_lesson($lesson_id, $user_id);
    }

    /**
     * Verificar la finalización del curso
     */
    public function check_course_completion($completed, $course_id, $user_id) {
        if (!$course_id || !$user_id) {
            return $completed;
        }

        // Obtener el porcentaje de finalización modificado
        $completion_percent = $this->modify_completion_percentage(0, $course_id);
        
        // El curso está completado si se alcanzó el 100%
        return $completion_percent >= 100;
    }

    /**
     * Modificar datos del certificado
     */
    public function modify_certificate_data($data, $course_id, $user_id) {
        if (!$course_id || !$user_id) {
            return $data;
        }

        // Modificar el porcentaje de finalización en los datos del certificado
        if (isset($data['completed_percent'])) {
            $data['completed_percent'] = $this->modify_completion_percentage($data['completed_percent'], $course_id);
        }

        // Modificar el número de lecciones completadas
        if (isset($data['completed_lessons'])) {
            $lessons = tutor_utils()->get_course_contents_by_topic($course_id, -1);
            $completed_count = 0;

            foreach ($lessons as $lesson) {
                // Solo contar lecciones no restringidas o accesibles
                if (!get_post_meta($lesson->ID, 'rl_is_restricted', true) || 
                    $this->user_has_access($user_id, $lesson->ID)) {
                    if ($this->is_lesson_completed($user_id, $lesson->ID)) {
                        $completed_count++;
                    }
                }
            }

            $data['completed_lessons'] = $completed_count;
        }

        // Agregar información adicional sobre actualizaciones compradas
        $data['has_updates'] = $this->get_user_purchased_updates($user_id, $course_id);

        return $data;
    }

    /**
     * Obtener las actualizaciones compradas por el usuario
     */
    private function get_user_purchased_updates($user_id, $course_id) {
        $purchased_updates = array();
        $lessons = tutor_utils()->get_course_contents_by_topic($course_id, -1);

        foreach ($lessons as $lesson) {
            $is_restricted = get_post_meta($lesson->ID, 'rl_is_restricted', true);
            if ($is_restricted) {
                $product_id = get_post_meta($lesson->ID, 'rl_product_id', true);
                $update_year = get_post_meta($lesson->ID, 'rl_update_year', true);

                if ($this->has_purchased_update($user_id, $product_id)) {
                    $purchased_updates[$update_year] = true;
                }
            }
        }

        return array_keys($purchased_updates);
    }
}

// Inicializar la personalización de certificados
new RL_Certificate_Customization();