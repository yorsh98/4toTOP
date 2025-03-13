    //FUNCION QUE MANEJA EL DESPLIEGUE DEL NAVBAR EN INDEX
    let lastScroll = 0;
    const navbar = document.getElementById('mainNav');
    const scrollThreshold = 100; // Ajusta este valor según cuando quieras que aparezca

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > scrollThreshold) {
            navbar.classList.add('active');
            // Animación para cada ícono
            document.querySelectorAll('.nav-icon').forEach((icon, index) => {
                icon.style.animation = `slideIn 0.5s ease-out ${index * 0.1}s forwards`;
            });
        } else {
            navbar.classList.remove('active');
        }
        lastScroll = currentScroll;
    });

    //FUNCION QUE MANEJA LA ANIMACION DE FLIP EN INDEX
    function toggleFlip(element) {
        element.querySelector('.flipper').classList.toggle('flipped');
    }    