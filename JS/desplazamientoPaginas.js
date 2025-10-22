document.addEventListener('DOMContentLoaded', function() {
    // FORMULARIO DE REGISTRO
    document.querySelector('#register-form form').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const mensajeDiv = document.getElementById('mensaje-registro');

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(mensaje => {
            // Mostrar el mensaje dentro del formulario
            mensajeDiv.innerHTML = mensaje;
            mensajeDiv.classList.remove('hidden');
            
            // Limpiar el formulario si el registro fue exitoso
            if (mensaje.toLowerCase().includes('exitoso')) {
                form.reset();
                mensajeDiv.classList.add('success');
                mensajeDiv.classList.remove('error');
            } else {
                mensajeDiv.classList.add('error');
                mensajeDiv.classList.remove('success');
            }
        })
        .catch(error => {
            console.error('Error en fetch (registro):', error);
            mensajeDiv.innerHTML = '<div class="mensaje-registro error">❌ Error: No se pudo procesar la solicitud.</div>';
        });
    });

    // FORMULARIO DE LOGIN
    document.querySelector('#login-form form').addEventListener('submit', function(event) {
        event.preventDefault();
        const form = this;
        const formData = new FormData(form);
        const mensajeDiv = document.getElementById('mensaje-login');

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(mensaje => {
            // Mostrar mensaje dentro del formulario
            mensajeDiv.innerHTML = mensaje;
            mensajeDiv.classList.remove('hidden');

            if (mensaje.toLowerCase().includes('exitoso')) {
                mensajeDiv.classList.add('success');
                mensajeDiv.classList.remove('error');

                // Redirigir tras 1 segundo
                setTimeout(() => {
                    window.location.href = 'InterfazDashboard.html';
                }, 1000);
            } else {
                mensajeDiv.classList.add('error');
                mensajeDiv.classList.remove('success');
            }
        })
        .catch(error => {
            console.error('Error en fetch (login):', error);
            mensajeDiv.innerHTML = '<div class="mensaje-registro error">❌ Error: No se pudo procesar la solicitud.</div>';
        });
    });
});

// CAMBIO ENTRE FORMULARIOS
function showRegister() {
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('register-form').classList.remove('hidden');
}

function showLogin() {
    document.getElementById('register-form').classList.add('hidden');
    document.getElementById('login-form').classList.remove('hidden');
}

