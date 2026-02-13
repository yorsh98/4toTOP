<x-in-layout>
                        <div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__bounceIn animate__delay-0.5s">
                            <div class="absolute top-4 left-4">
                                <a href="{{ url('/') }}" class="bg-white text-black font-semibold py-1 px-3 rounded-md shadow-md flex items-center text-sm hover:bg-blue-700 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    INICIO
                                </a>
                            </div>
                            <div class="flex justify-center items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">    
                                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <polygon style="fill:#BFDCFF;" points="0,66.783 0,512 256,512 278.261,44.522 "></polygon> <polygon style="fill:#8BC0FF;" points="512,66.783 256,44.522 256,512 512,512 "></polygon> <polygon style="fill:#446080;" points="256,0 0,0 0,66.783 256,66.783 278.261,33.391 "></polygon> <polygon style="fill:#324860;" points="422.957,66.783 445.217,33.391 422.957,0 256,0 256,66.783 "></polygon> <rect x="422.957" style="fill:#FF562B;" width="89.043" height="66.783"></rect> <path style="fill:#8BC0FF;" d="M256,150.261V116.87c-95.128,0-172.522,77.392-172.522,172.522S160.871,461.913,256,461.913v-33.391 c-13.952,0-28.591-13.272-40.16-36.411c-11.624-23.247-18.759-53.333-20.578-86.024H256v-33.391h-60.738 c1.82-32.692,8.954-62.777,20.578-86.024C227.409,163.533,242.048,150.261,256,150.261z M185.974,407.043 c0.609,1.218,1.229,2.413,1.854,3.593c-37.718-21.29-64.561-59.666-69.954-104.549h43.938 C163.728,344.005,172.135,379.365,185.974,407.043z M117.875,272.696c5.393-44.883,32.236-83.259,69.954-104.549 c-0.626,1.18-1.245,2.375-1.854,3.593c-13.838,27.678-22.246,63.038-24.16,100.956H117.875z"></path> <path style="fill:#3897FF;" d="M256,116.87h-16.696v345.043H256c95.128,0,172.522-77.392,172.522-172.522S351.128,116.87,256,116.87 z M394.131,272.696h-43.945c-1.913-37.918-10.321-73.278-24.16-100.956c-0.611-1.221-1.232-2.42-1.86-3.603 C361.889,189.427,388.739,227.807,394.131,272.696z M272.696,422.46V306.087h44.042c-1.82,32.692-8.954,62.777-20.578,86.024 C289.181,406.069,281.084,416.43,272.696,422.46z M272.696,272.696V156.322c8.388,6.029,16.485,16.391,23.464,30.349 c11.624,23.247,18.759,53.333,20.578,86.024H272.696z M324.166,410.646c0.628-1.183,1.249-2.382,1.86-3.603 c13.839-27.678,22.246-63.038,24.16-100.956h43.945C388.739,350.976,361.889,389.357,324.166,410.646z"></path> </g></svg>
                                </div>
                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-gray-300">SELECCIONE LA PAGINA A VISITAR</h2>
                                </div>
                            </div>
                        </div>                                  
                        <div class="grid gap-3 animate__animated animate__bounceIn animate__delay-0.8s">
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl hover:shadow-xl transition-all mb-6">
                            <button onclick="toggleAcceso('mod1')" 
                                    class="w-full px-6 py-4 flex items-center justify-between text-lg font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-2xl transition-all duration-300 group">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        🌐
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Accesos Sistemas Judiciales</span>
                                </div>
                                <svg id="icon-mod1" 
                                    class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400 hover:bg-gray-50 group-hover:text-blue-600"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div id="mod1" class=" p-6 pt-4 grid gap-4 lg:grid-cols-3 md:grid-cols-2 border-t border-gray-100 dark:border-gray-700">
                                <a href="http://gestionpenal.reformaprocesal.pjud/webGestion/login.jsf" target="_blank" class="flex dark:bg-gray-700 items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Poder Judicial" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">GESTIÓN PENAL 1</span>
                                </a>
                                
                                <!-- Repetir para los demás elementos -->
                                <a href="http://gestionpenal2.reformaprocesal.pjud/webGestion/login.jsf" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">GESTIÓN PENAL 2</span>
                                </a>

                                <a href="http://gestionpenal3.reformaprocesal.pjud/webGestion/login.jsf" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">GESTIÓN PENAL 3</span>
                                </a>
                                <a href="http://monitoweb.srcei.cl/monito" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">MONITO WEB 1</span>
                                </a>
                                <a href="https://monitoweb2.srcei.cl/monito" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">MONITO WEB 2</span>
                                </a>
                                <a href="http://www.reformaprocesal.pjud/DagWeb/Console/DagConsoleWeb.vbd" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">SIAGJ <--abrir enlace en INTERNET EXPLORER  </span>
                                </a>
                                <a href="https://www.pjud.cl/transparencia/busqueda-de-abogados/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">SUSPENSION DE ABOGADOS</span>
                                </a>
                                <a href="http://prod.intranet.pjud/suspension_abogados/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">AGREGAR SUSPENSION DE ABOGADO</span>
                                </a>
                                <a href="https://repoupenal.pjud.cl/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">PORTAL ORDENES Y CONTRAORDENES</span>
                                </a>
                               <a href="https://consultaintegrada.pjud.cl/loginMinistry" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">CONSULTA INTEGRADA DE CAUSAS UNIJUD</span>
                                </a>                                
 
                            </div>
                        </div> 
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl hover:shadow-xl transition-all mb-6">
                            <button onclick="toggleAcceso('mod2')" 
                                    class="w-full px-6 py-4 flex items-center justify-between text-lg font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-2xl transition-all duration-300 group">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        🔒
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300"> Accesos Administrativos PJUD</span>
                                </div>
                                <svg id="icon-mod2" 
                                    class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400 hover:bg-gray-50 group-hover:text-blue-600"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div id="mod2" class=" p-6 pt-4 grid gap-4 lg:grid-cols-3 md:grid-cols-2 border-t border-gray-100 dark:border-gray-700">
                                <a href="http://www2.intranet.pjud/" target="_blank" class="flex dark:bg-gray-700 items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Poder Judicial" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Intranet PJUD</span>
                                </a>                        
                                <a href="https://bienestar.pjud.cl" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Bienestar</span>
                                </a>

                                <a href="http://www2.recursoshumanos.intranet.pjud/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">RRHH</span>
                                </a>
                                <a href="https://personas.pjud.cl/portalpersonassrh/servlet/com.portalpersonas.login" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Personas</span>
                                </a>
                                <a href="http://mesaayuda.intranet.pjud/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Mesa de ayuda (Requerimientos)</span>
                                </a>
                                
                                
                            </div>
                        </div> 
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl hover:shadow-xl transition-all mb-6">
                            <button onclick="toggleAcceso('mod3')" 
                                    class="w-full px-6 py-4 flex items-center justify-between text-lg font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-2xl transition-all duration-300 group">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        💻
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Accesos Tecnicos</span>
                                </div>
                                <svg id="icon-mod3" 
                                    class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400 hover:bg-gray-50 group-hover:text-blue-600"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div id="mod3" class=" p-6 pt-4 grid gap-4 lg:grid-cols-3 md:grid-cols-2 border-t border-gray-100 dark:border-gray-700">
                                <a href="https://recaudacion.bancoestado.cl/CostasJudiciales/Default.aspx" target="_blank" class="flex dark:bg-gray-700 items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/bancoestado.svg') }}" alt="Icono" class="w-9 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Costas Judiciales</span>
                                </a>
                                
                                <!-- repetir de a hacia /a para repetir cada boton con ingreso directo a la web necesitada -->
                                <a href="https://www.bancoestado.cl/content/bancoestado-public/cl/es/home/inicio---bancoestado-instituciones-publicas-.html#/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/bancoestado.svg') }}" alt="Icono" class="w-9 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Banco Estado Instituciones publicas</span>
                                </a>

                                <a href="http://www.ctacte.pjud/CTACTEWEB/jsp/Login/Login.jsp" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/bancoestado.svg') }}" alt="Icono" class="w-9 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Cuenta Corriente</span>
                                </a>
                                <a href="http://www.cgu.pjud/cgu90mn-pjud/servlet/hlogin" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">CGU</span>
                                </a>
                                <a href="https://portal-cl.sovos.com/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Portal Sovos</span>
                                </a>
                                <a href="http://sgf-finanzas.apps.ocp.pjud/#/pages/login" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Finanzas</span>
                                </a>
                                <a href="https://www.mercadopublico.cl/Home" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="/favicon.webp" alt="Gestión Penal" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Mercado Publico</span>
                                </a>
                                <a href="https://dte.correos.cl/ConsultaFacturaElectronica/vista/Login.aspx" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <img src="{{ asset('SVG/correos.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Consulta Facturas electronicas (CorreosChile)</span>
                                </a>
                                <a href="https://www.sii.cl/valores_y_fechas/index_valores_y_fechas.html" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <img src="{{ asset('SVG/SII.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">SII valores y fechas</span>
                                </a>
                                <a href="https://zeus.sii.cl/cvc/stc/stc.html" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/SII.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">SII Situación tributaria</span>
                                </a>                                                                
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-2xl hover:shadow-xl transition-all mb-6">
                            <button onclick="toggleAcceso('mod4')" 
                                    class="w-full px-6 py-4 flex items-center justify-between text-lg font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-t-2xl transition-all duration-300 group">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    📂
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-300">Nubes y manejo de la Informacion</span>
                                </div>
                                <svg id="icon-mod4" 
                                    class="w-6 h-6 transition-transform duration-300 text-gray-600 dark:text-gray-400 hover:bg-gray-50 group-hover:text-blue-600"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div id="mod4" class=" p-6 pt-4 grid gap-4 lg:grid-cols-3 md:grid-cols-2 border-t border-gray-100 dark:border-gray-700">
                                <a href="https://cloud.pjud.cl/" target="_blank" class="flex dark:bg-gray-700 items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/folder.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">CLOUD.PJUD.CL</span>
                                </a>
                                
                                
                                <a href="https://grabaciones.pjud.cl/index.php/login" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/folder.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">GRABACIONES.PJUD.CL</span>
                                </a>

                                <a href="https://goodtape.io/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/folder.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">GOOD TAPE</span>
                                </a>
                                <a href="https://pjudcl-my.sharepoint.com/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/folder.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">ONEDRIVE</span>
                                </a>
                                <a href="https://wetransfer.com/" target="_blank" class="flex items-center p-3 space-x-3 rounded-xl transition-all shadow-sm hover:bg-gray-100 hover:-translate-y-0.5 border border-gray-100 dark:border-gray-700">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                        <img src="{{ asset('SVG/folder.svg') }}" alt="Icono" class="w-6 h-6">
                                    </div>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">WETRANSFER</span>
                                </a>
                                
                                
                            </div>
                        </div>
                                
                                </div>
                            </div>
    
                    

@push('scripts')
<script>
let openModule = null;

function toggleAcceso(id) {
    const module = document.getElementById(id);
    const icon = document.getElementById(`icon-${id}`);
    
    if (openModule && openModule !== module) {
        //openModule.classList.add('hidden');
        document.getElementById(`icon-${openModule.id}`).classList.remove('rotate-180');
    }
    
    module.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
    
    openModule = module.classList.contains('hidden') ? null : module;
}
</script>
@endpush('scripts')

</x-in-layout>
