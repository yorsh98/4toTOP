import { serviciosMaqueta } from './alpine/servicios-maqueta.js';
import { serviciosPlano } from './alpine/servicios-plano.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('serviciosPlano', serviciosPlano);
    Alpine.data('serviciosMaqueta', serviciosMaqueta);
});
