<?php
/**
 * seccion_formularios_rrhh.php
 * Vista consolidada de todos los formularios RRHH (Ingresos y Salidas) para SuperAdmin
 */
?>

<div class="p-6 flex-1 glass">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
            <i class="ri-user-star-line text-pink-600"></i>
            Formularios de RRHH
        </h2>
        <div class="flex gap-3">
            <a href="index.php?view=nuevo_ingreso"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold flex items-center gap-2 shadow-lg transition-all">
                <i class="ri-user-add-line"></i> Nuevo Ingreso
            </a>
            <a href="index.php?view=nueva_salida"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold flex items-center gap-2 shadow-lg transition-all">
                <i class="ri-user-unfollow-line"></i> Nueva Salida
            </a>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php
        $total_ingresos = count(array_filter($formularios, fn($f) => $f['tipo'] === 'Ingreso'));
        $total_salidas = count(array_filter($formularios, fn($f) => $f['tipo'] === 'Salida'));
        $total_licencias = count(array_filter($formularios, fn($f) => $f['tipo'] === 'Licencia'));
        $total_formularios = count($formularios);
        ?>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Ingresos</p>
                    <p class="text-4xl font-bold mt-2"><?= $total_ingresos ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="ri-user-add-line text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Total Salidas</p>
                    <p class="text-4xl font-bold mt-2"><?= $total_salidas ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="ri-user-unfollow-line text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Licencias</p>
                    <p class="text-4xl font-bold mt-2"><?= $total_licencias ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="ri-shield-keyhole-line text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium">Total Formularios</p>
                    <p class="text-4xl font-bold mt-2"><?= $total_formularios ?></p>
                </div>
                <div class="bg-white bg-opacity-20 p-4 rounded-full">
                    <i class="ri-file-list-3-line text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-lg p-4 mb-6 flex gap-4 items-center flex-wrap">
        <label class="text-sm font-bold text-slate-700">Filtrar por tipo:</label>
        <button onclick="filtrarFormularios('todos')" id="btn-todos"
            class="px-4 py-2 rounded-lg bg-slate-600 text-white font-semibold">Todos</button>
        <button onclick="filtrarFormularios('Ingreso')" id="btn-ingreso"
            class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-blue-100">Ingresos</button>
        <button onclick="filtrarFormularios('Salida')" id="btn-salida"
            class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-red-100">Salidas</button>
        <button onclick="filtrarFormularios('Licencia')" id="btn-licencia"
            class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-purple-100">Licencias</button>
    </div>

    <!-- Tabla de Formularios -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Colaborador</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Cargo/Zona</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($formularios)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <i class="ri-inbox-line text-5xl mb-2"></i>
                                <p class="text-lg">No hay formularios registrados</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($formularios as $f): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors formulario-row" data-tipo="<?= $f['tipo'] ?>">
                                <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                    #<?= str_pad($f['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($f['tipo'] === 'Ingreso'): ?>
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200 flex items-center w-fit gap-1">
                                            <i class="ri-user-add-line"></i> Ingreso
                                        </span>
                                    <?php elseif ($f['tipo'] === 'Licencia'): ?>
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 border border-purple-200 flex items-center w-fit gap-1">
                                            <i class="ri-shield-keyhole-line"></i> Licencia
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 border border-red-200 flex items-center w-fit gap-1">
                                            <i class="ri-user-unfollow-line"></i> Salida
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800"><?= htmlspecialchars($f['nombre_colaborador']) ?>
                                    </div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($f['cedula_telefono']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($f['cargo_zona']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?php
                                    $fecha_mostrar = $f['tipo'] === 'Ingreso' ? $f['fecha_solicitud'] : $f['fecha_efectiva'];
                                    echo htmlspecialchars($fecha_mostrar);
                                    ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button onclick='verDetallesRRHH(<?= htmlspecialchars(json_encode($f)) ?>)'
                                            class="text-blue-600 hover:text-blue-800 font-semibold text-sm flex items-center gap-1 bg-blue-50 px-3 py-1.5 rounded transition hover:bg-blue-100">
                                            <i class="ri-eye-line"></i> Detalles
                                        </button>

                                        <?php if (!isset($f['asignado_a']) || is_null($f['asignado_a'])): ?>
                                            <a href="index.php?view=editar_rrhh&id=<?= $f['id'] ?>"
                                                class="text-green-600 hover:text-green-800 font-semibold text-sm flex items-center gap-1 bg-green-50 px-3 py-1.5 rounded transition hover:bg-green-100"
                                                title="Editar formulario">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                        <?php else: ?>
                                            <span
                                                class="text-slate-400 font-semibold text-sm flex items-center gap-1 bg-slate-50 px-3 py-1.5 rounded cursor-not-allowed"
                                                title="No se puede editar: asignado a técnico">
                                                <i class="ri-lock-line"></i>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($f['tipo'] === 'Ingreso'): ?>
                                            <a href="imprimir_acta_ingreso.php?id=<?= $f['id'] ?>" target="_blank"
                                                class="text-teal-600 hover:text-teal-800 font-semibold text-sm flex items-center gap-1 bg-teal-50 px-3 py-1.5 rounded transition hover:bg-teal-100"
                                                title="Ver Acta Informativa (Sin Firma)">
                                                <i class="ri-file-info-line"></i>
                                            </a>
                                        <?php elseif ($f['tipo'] === 'Licencia'): ?>
                                            <a href="imprimir_acta_licencia.php?id=<?= $f['id'] ?>" target="_blank"
                                                class="text-purple-600 hover:text-purple-800 font-semibold text-sm flex items-center gap-1 bg-purple-50 px-3 py-1.5 rounded transition hover:bg-purple-100"
                                                title="Ver Acta de Entrega">
                                                <i class="ri-printer-line"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="imprimir_acta_salida.php?id=<?= $f['id'] ?>" target="_blank"
                                                class="text-teal-600 hover:text-teal-800 font-semibold text-sm flex items-center gap-1 bg-teal-50 px-3 py-1.5 rounded transition hover:bg-teal-100"
                                                title="Ver Acta Informativa (Sin Firma)">
                                                <i class="ri-file-info-line"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Detalles (Reutilizado del código existente) -->
<div id="modalDetallesRRHH"
    class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="text-xl font-bold text-slate-800" id="modalTitulo">Detalles del Formulario</h3>
            <button onclick="cerrarModalRRHH()" class="text-slate-400 hover:text-slate-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4" id="modalContenido">
            <!-- El contenido se llenará dinámicamente con JS -->
        </div>
        <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button onclick="cerrarModalRRHH()"
                class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-semibold">Cerrar</button>
        </div>
    </div>
</div>

<script>
    // Filtrar formularios por tipo
    function filtrarFormularios(tipo) {
        const rows = document.querySelectorAll('.formulario-row');
        const buttons = {
            'todos': document.getElementById('btn-todos'),
            'Ingreso': document.getElementById('btn-ingreso'),
            'Salida': document.getElementById('btn-salida'),
            'Licencia': document.getElementById('btn-licencia')
        };

        // Resetear estilos de botones
        Object.values(buttons).forEach(btn => {
            btn.classList.remove('bg-slate-600', 'text-white', 'bg-blue-600', 'bg-red-600', 'bg-purple-600');
            btn.classList.add('bg-slate-200', 'text-slate-700');
        });

        // Activar botón seleccionado
        if (tipo === 'todos') {
            buttons['todos'].classList.remove('bg-slate-200', 'text-slate-700');
            buttons['todos'].classList.add('bg-slate-600', 'text-white');
            rows.forEach(row => row.style.display = '');
        } else {
            buttons[tipo].classList.remove('bg-slate-200', 'text-slate-700');
            let colorClass = 'bg-blue-600';
            if (tipo === 'Salida') colorClass = 'bg-red-600';
            if (tipo === 'Licencia') colorClass = 'bg-purple-600';

            buttons[tipo].classList.add(colorClass, 'text-white');
            rows.forEach(row => {
                row.style.display = row.dataset.tipo === tipo ? '' : 'none';
            });
        }
    }

    // Ver detalles del formulario (Reutilizado)
    function verDetallesRRHH(data) {
        const modal = document.getElementById('modalDetallesRRHH');
        const titulo = document.getElementById('modalTitulo');
        const contenido = document.getElementById('modalContenido');

        titulo.textContent = 'Detalles de ' + data.tipo + ': ' + data.nombre_colaborador;

        let html = '';

        const row = (label, value, detail = null) => {
            if (!value && !detail) return '';
            let valHtml = `<span class="font-semibold ${value === 'SI' ? 'text-green-600' : (value === 'NO' ? 'text-red-600' : 'text-slate-800')}">${value || '-'}</span>`;
            if (detail) valHtml += `<div class="text-sm text-slate-500 mt-1 bg-slate-50 p-2 rounded border border-slate-100">${detail}</div>`;
            return `
        <div class="border-b border-slate-100 pb-3 last:border-0">
            <div class="text-xs font-bold text-slate-400 uppercase mb-1">${label}</div>
            <div>${valHtml}</div>
        </div>
    `;
        };

        if (data.tipo === 'Ingreso') {
            html += `<div class="grid grid-cols-2 gap-4 mb-4">
                ${row('Fecha Solicitud', data.fecha_solicitud)}
                ${row('Cédula / Teléfono', data.cedula_telefono)}
                ${row('Cargo / Zona', data.cargo_zona)}
             </div>`;

            html += `<h4 class="font-bold text-blue-600 mt-4 mb-2 border-b border-blue-100 pb-1">Requerimientos</h4>`;
            html += row('Licencias', data.disponibilidad_licencias, data.detalle_licencias);
            html += row('Correo Nuevo', data.correo_nuevo, data.direccion_correo);
            html += row('Remitente', data.remitente_mostrar, data.detalle_remitente);
            html += row('Respaldo Nube', data.respaldo_nube, data.detalle_respaldo);
            html += row('Reenvíos', data.reenvios_correo, data.detalle_reenvios);

            html += `<h4 class="font-bold text-blue-600 mt-4 mb-2 border-b border-blue-100 pb-1">Equipos</h4>`;
            html += row('Asignación PC', data.asignacion_equipo, data.detalle_asignacion);
            html += row('Nube Móvil', data.nube_movil, data.detalle_nube_movil);
            html += row('Equipo Usado', data.equipo_usado, data.especificacion_equipo_usado);

            if (data.otras_indicaciones) {
                html += `<div class="mt-4 bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                    <div class="font-bold text-yellow-800 mb-1">Otras Indicaciones:</div>
                    <div class="text-yellow-900">${data.otras_indicaciones}</div>
                 </div>`;
            }

        } else if (data.tipo === 'Licencia') {
        html += `<div class="grid grid-cols-2 gap-4 mb-4">
                ${row('Fecha Solicitud', data.fecha_solicitud)}
                ${row('Beneficiario', data.nombre_colaborador)}
                ${row('Departamento', data.cargo_zona)}
             </div>`;

        html += `<h4 class="font-bold text-purple-600 mt-4 mb-2 border-b border-purple-100 pb-1">Detalle de Licencia</h4>`;
        html += row('Tipo Licencia', data.detalle_licencias);

        if (data.otras_indicaciones) {
            html += `<div class="mt-4 bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="font-bold text-purple-800 mb-1">Justificación / Notas:</div>
                    <div class="text-purple-900">${data.otras_indicaciones}</div>
                 </div>`;
        }

    } else {
        html += `<div class="grid grid-cols-2 gap-4 mb-4">
                ${row('Fecha Efectiva', data.fecha_efectiva)}
                ${row('Cédula / Teléfono', data.cedula_telefono)}
                ${row('Cargo / Zona', data.cargo_zona)}
             </div>`;

        html += `<h4 class="font-bold text-red-600 mt-4 mb-2 border-b border-red-100 pb-1">Cierre de Accesos</h4>`;
        html += row('Bloqueo Correo', data.bloqueo_correo, data.cuenta_correo_bloqueo);
        html += row('Respaldo Info', data.respaldo_info, data.detalle_respaldo_salida);
        html += row('Redirección', data.redireccion_correo, data.email_redireccion);

        html += `<h4 class="font-bold text-red-600 mt-4 mb-2 border-b border-red-100 pb-1">Devolución</h4>`;
        html += row('Devolución PC', data.devolucion_equipo, data.detalle_devolucion_equipo);
        html += row('Devolución Móvil', data.devolucion_movil, data.detalle_devolucion_movil);

        if (data.observaciones) {
            html += `<div class="mt-4 bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                    <div class="font-bold text-yellow-800 mb-1">Observaciones:</div>
                    <div class="text-yellow-900">${data.observaciones}</div>
                 </div>`;
        }
    }

    contenido.innerHTML = html;
    modal.classList.remove('hidden');
    }

    function cerrarModalRRHH() {
        document.getElementById('modalDetallesRRHH').classList.add('hidden');
    }
</script>