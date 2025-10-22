document.addEventListener('DOMContentLoaded', function() {
    // Para el formulario de registro
    document.querySelector('#register-form form').addEventListener('submit', function(event)  {
        console.log('Evento submit capturado (registro)');
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
            mensajeDiv.innerHTML = mensaje;
            if (mensaje.includes('success')) {
                form.reset();
            }
        })
        .catch(error => {
            console.error('Error en fetch (registro):', error);
            mensajeDiv.innerHTML = '<div class="mensaje-registro error">Error: No se pudo procesar la solicitud.</div>';
        });
    });

    // Para el formulario de login
    document.querySelector('#login-form form').addEventListener('submit', function(event) {
        console.log('Evento submit capturado (login)');
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
            console.log('Mensaje recibido:', mensaje);
            mensajeDiv.innerHTML = mensaje;
            if (mensaje.toLowerCase().includes('success')) {
                console.log('Éxito detectado, iniciando redirección');
                setTimeout(() => { 
                    window.location.href = 'InterfazDashboard.html'; 
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Error en fetch (login):', error);
            mensajeDiv.innerHTML = '<div class="mensaje-registro error">Error: No se pudo procesar la solicitud.</div>';
        });
    });
});

// Funciones para direccionamiento de las páginas
function showRegister() {
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('register-form').classList.remove('hidden');
}

function showLogin() {
    document.getElementById('register-form').classList.add('hidden');
    document.getElementById('login-form').classList.remove('hidden');
}

function iniciarSesion(event) {
    event.preventDefault();
    window.location.href = "InterfazDashboard.html";
}

function cerrarSesion(event) {
    event.preventDefault();
    window.location.href = "InterfazInicioRegistro.html";
}