    //FUNCION QUE MANEJA EL DESPLIEGUE DEL NAVBAR EN INDEX
    const navbar = document.getElementById('mainNav');
    const scrollThreshold = 100; // Ajusta este valor según cuando quieras que aparezca

    if (navbar) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;

            if (currentScroll > scrollThreshold) {
                navbar.classList.add('active');
            } else {
                navbar.classList.remove('active');
            }
        });
    }

    //FUNCION QUE MANEJA LA ANIMACION DE FLIP EN INDEX
    function toggleFlip(element) {
        element.querySelector('.flipper').classList.toggle('flipped');
    }    
