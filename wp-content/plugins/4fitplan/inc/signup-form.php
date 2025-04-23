<?php 

// Archivo: wp-content/plugins/4fitplan/inc/class-signup-form.php

class Signup_Form {
    
    public function render() {
        ob_start();
        ?>
        <form method="post" id="signup_form">
            <h3>Regístrate</h3>
            <p>
                <label>Email: <input type="email" name="user_email" required></label>
            </p>
            <p>
                <label>Nombre de usuario: <input type="text" name="user_login" required></label>
            </p>
            <p>
                <label>Contraseña: <input type="password" name="user_pass" required></label>
            </p>
            <p>
                <button type="submit" name="signup_submit" class="button">Registrarse</button>
            </p>
        </form>
        <?php
        // Procesar inscripción si se envía el formulario
        if (isset($_POST['signup_submit'])) {
            $this->procesar_inscripcion();
        }
        return ob_get_clean();
    }
    
    protected function procesar_inscripcion() {
        // Recuperar y sanitizar los datos
        $user_email = sanitize_email($_POST['user_email']);
        $user_login = sanitize_user($_POST['user_login']);
        $user_pass  = $_POST['user_pass']; // Considera validar la longitud y seguridad
        
        // Crear el usuario en WordPress
        $userdata = array(
            'user_login' => $user_login,
            'user_email' => $user_email,
            'user_pass'  => $user_pass
        );
        $user_id = wp_insert_user($userdata);
        
        if (is_wp_error($user_id)) {
            echo "<p>Error en el registro: " . $user_id->get_error_message() . "</p>";
        } else {
            echo "<p>Registro completado con éxito. Ahora puedes iniciar sesión.</p>";
        }
    }
}