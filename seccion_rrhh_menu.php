<?php
/**
 * seccion_rrhh_menu.php - Menú de Selección de Formularios RRHH
 * Unifica el acceso a Altas (Ingreso) y Bajas (Salida)
 */
?>

<div class="h-[calc(100vh-5rem)] flex items-center justify-center bg-slate-50/50 p-6">
    <div class="w-full max-w-4xl">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-slate-800">Gestión de Movimientos RRHH</h1>
            <p class="text-slate-500 mt-2 text-lg">Selecciona el tipo de proceso que deseas iniciar</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Opción ALTA -->
            <a href="index.php?view=nuevo_ingreso"
                class="group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 border border-slate-100 hover:-translate-y-1 h-full flex flex-col">
                <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-emerald-400 to-teal-500 rounded-t-3xl">
                </div>
                <div class="flex flex-col items-center text-center h-full">
                    <div
                        class="w-24 h-24 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-sm border border-emerald-100">
                        <i class="ri-user-add-line text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 mb-2 group-hover:text-emerald-700 transition-colors">
                        Solicitud de Alta</h2>
                    <p class="text-slate-500 leading-relaxed mb-6 flex-1">
                        Registrar un <strong>nuevo ingreso</strong> de personal. Incluye asignación de equipos, accesos
                        y notificaciones automáticas.
                    </p>
                    <span
                        class="mt-auto inline-flex items-center gap-2 text-emerald-600 font-bold bg-emerald-50 px-4 py-2 rounded-lg group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        Iniciar Proceso <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
            </a>

            <!-- Opción BAJA -->
            <a href="index.php?view=nueva_salida"
                class="group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 border border-slate-100 hover:-translate-y-1 h-full flex flex-col">
                <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-rose-400 to-red-500 rounded-t-3xl"></div>
                <div class="flex flex-col items-center text-center h-full">
                    <div
                        class="w-24 h-24 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-sm border border-rose-100">
                        <i class="ri-user-unfollow-line text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 mb-2 group-hover:text-rose-700 transition-colors">
                        Solicitud de Baja</h2>
                    <p class="text-slate-500 leading-relaxed mb-6 flex-1">
                        Procesar la <strong>salida</strong> de un colaborador. Gestionar devolución de activos,
                        revocación de accesos y cierre.
                    </p>
                    <span
                        class="mt-auto inline-flex items-center gap-2 text-rose-600 font-bold bg-rose-50 px-4 py-2 rounded-lg group-hover:bg-rose-600 group-hover:text-white transition-all">
                        Iniciar Proceso <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
            </a>

            <!-- Opción SOLICITUD LICENCIA -->
            <a href="index.php?view=solicitud_licencia"
                class="group relative bg-white rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 border border-slate-100 hover:-translate-y-1 h-full flex flex-col">
                <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-t-3xl">
                </div>
                <div class="flex flex-col items-center text-center h-full">
                    <div
                        class="w-24 h-24 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-sm border border-blue-100">
                        <i class="ri-shield-keyhole-line text-5xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 mb-2 group-hover:text-blue-700 transition-colors">
                        Solicitud Licencia</h2>
                    <p class="text-slate-500 leading-relaxed mb-6 flex-1">
                        Gestionar nuevas licencias de <strong>software</strong> (365, Antivirus, Adobe) para
                        colaboradores activos.
                    </p>
                    <span
                        class="mt-auto inline-flex items-center gap-2 text-blue-600 font-bold bg-blue-50 px-4 py-2 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-all">
                        Iniciar Proceso <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
            </a>
        </div>

        <div class="mt-12 text-center">
            <a href="index.php?view=historial_rrhh"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl shadow-sm hover:shadow-md hover:border-blue-300 hover:text-blue-600 transition-all">
                <i class="ri-history-line text-lg"></i>
                Ver Historial de Movimientos
            </a>
        </div>
    </div>
</div>