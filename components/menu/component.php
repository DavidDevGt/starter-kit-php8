<nav class="navbar navbar-expand-lg bg-body-tertiary bg-primary p-3 shadow-lg w-full" data-bs-theme="dark">
    <div class="container-fluid">
        <img height="35" class="ms-4 me-4" src="../../src/assets/images/logo-starter-kit.png">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-5 text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0" id="navbar_area">
            </ul>
            <div role="search">
                <button class="btn btn-danger" type="button" onclick="logout()">Cerrar Sesión</button>
            </div>


        </div>
    </div>
</nav>

<style>
    .nav-item,
    .nav-link {
        font-size: 1rem;
    }

    .dropdown-item {
        font-size: 0.95rem;
    }

    .nav-link::before {
        content: " ";
        display: block;
        width: 0;
        height: 2px;
        background-color: #fff;
        transition: width 0.3s;
    }

    .nav-link:hover {
        text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        /* Ajusta a tu preferencia */
    }
</style>

<script>
    // El document ready pero en vanilla
    document.addEventListener('DOMContentLoaded', function() {
        cargarMenu();
    });

    // const crearMenu = (data) => {}

    // No tocar esta función
    function getBaseURL() {
        let pathArray = window.location.pathname.split('/');
        // Elimina el vacío al final y el archivo .php si existe
        if (pathArray[pathArray.length - 1] === '') {
            pathArray.pop();
        }
        if (pathArray[pathArray.length - 1].includes('.')) {
            pathArray.pop();
        }
        // Encuentra el nombre del proyecto y reconstruye la base URL
        let projectIndex = pathArray.indexOf('starter-kit-php8');
        if (projectIndex !== -1) {
            // En desarrollo, incluye el nombre del proyecto
            return window.location.origin + pathArray.slice(0, projectIndex + 1).join('/');
        } else {
            // En producción, asume que el dominio apunta al directorio raíz del proyecto
            //* REVISAR ESTO *//
            return window.location.origin + '/';
        }
    }


    function cargarMenu() {
        fetch('../../components/menu/menu-ajax.php')
            .then(response => response.json())
            .then(data => {
                crearMenu(data);
            })
            .catch(error => console.error('Error al cargar el menu:', error));
    }

    function logout() {
        window.location.href = '../../components/menu/logout.php'
    }

    function crearMenu(modulos) {
        const baseUrl = getBaseURL(); // Obtiene la base URL de la aplicación
        const navbarArea = document.getElementById('navbar_area');

        // Filtrar módulos principales y ordenarlos
        const modulosPrincipales = modulos.filter(modulo => modulo.primary_module === "1");

        modulosPrincipales.forEach(modulo => {
            const li = document.createElement('li');
            li.className = 'nav-item dropdown';

            const a = document.createElement('a');
            a.className = 'nav-link dropdown-toggle';
            a.href = '#';
            a.id = `dropdown-${modulo.id}`;
            a.role = 'button';
            a.dataset.bsToggle = 'dropdown';
            a.setAttribute('aria-expanded', 'false');
            a.textContent = modulo.name;

            const ul = document.createElement('ul');
            ul.className = 'dropdown-menu';
            ul.setAttribute('aria-labelledby', `dropdown-${modulo.id}`);

            // Filtrar y ordenar módulos hijos
            const hijos = modulos.filter(m => m.father_module_id === modulo.id).sort((a, b) => a.position - b.position);

            hijos.forEach(hijo => {
                const liHijo = document.createElement('li');
                const aHijo = document.createElement('a');
                aHijo.className = 'dropdown-item';
                aHijo.href = baseUrl + hijo.route; // Aquí se construye la URL completa
                aHijo.textContent = hijo.name;
                liHijo.appendChild(aHijo);
                ul.appendChild(liHijo);
            });

            if (hijos.length === 0) {
                const liHijo = document.createElement('li');
                const aHijo = document.createElement('a');
                aHijo.className = 'dropdown-item';
                aHijo.textContent = 'Sin resultados';
                liHijo.appendChild(aHijo);
                ul.appendChild(liHijo);
            }

            li.appendChild(a);
            li.appendChild(ul);
            navbarArea.appendChild(li);
        });
    }
</script>