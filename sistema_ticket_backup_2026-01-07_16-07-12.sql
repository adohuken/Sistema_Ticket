-- Backup de Base de Datos - Sistema de Tickets
-- Fecha: 2026-01-07 16:07:12
-- Generado por: CLI Backup Tool

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Estructura de tabla para la tabla `asignaciones`
DROP TABLE IF EXISTS `asignaciones`;
CREATE TABLE `asignaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `tecnico_id` int(11) NOT NULL COMMENT 'Usuario con rol Tecnico asignado',
  `asignado_por` int(11) NOT NULL COMMENT 'Admin o Gerente que asignó',
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_asignacion_ticket` (`ticket_id`),
  KEY `fk_asignacion_tecnico` (`tecnico_id`),
  KEY `fk_asignacion_asignador` (`asignado_por`),
  CONSTRAINT `fk_asignacion_asignador` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asignacion_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `asignaciones`
INSERT INTO `asignaciones` VALUES 
('1','2','3','1','2025-12-15 11:39:02'),
('2','3','3','1','2025-12-15 11:39:10'),
('3','5','3','1','2025-12-15 11:39:12'),
('4','7','3','1','2025-12-15 11:39:23'),
('5','7','3','1','2025-12-15 11:45:31'),
('6','20','3','1','2026-01-06 15:09:37'),
('7','18','3','1','2026-01-06 15:09:42'),
('8','17','3','1','2026-01-06 15:09:50'),
('9','16','3','1','2026-01-06 15:09:56');

-- Estructura de tabla para la tabla `cargos`
DROP TABLE IF EXISTS `cargos`;
CREATE TABLE `cargos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `cargos`

-- Estructura de tabla para la tabla `categorias`
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL COMMENT 'Ej: Hardware, Software, Redes, Acceso',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `categorias`
INSERT INTO `categorias` VALUES 
('1','Hardware'),
('2','Software'),
('3','Redes'),
('4','Formulario'),
('5','Otros');

-- Estructura de tabla para la tabla `configuracion_sistema`
DROP TABLE IF EXISTS `configuracion_sistema`;
CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL COMMENT 'Identificador único de la configuración',
  `valor` text DEFAULT NULL COMMENT 'Valor de la configuración',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción de la configuración',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  UNIQUE KEY `idx_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `configuracion_sistema`
INSERT INTO `configuracion_sistema` VALUES 
('1','logo_master_suministros','uploads/logos/logo_master_suministros_1765771910.jpg','Ruta del logo de Master Suministros para Actas','2025-12-14 22:11:50'),
('2','logo_centro','uploads/logos/logo_centro_1765810472.png','Ruta del logo de Centro para Actas','2025-12-15 08:54:32'),
('3','logo_mastertec','uploads/logos/logo_mastertec_1765765877.jpg','Ruta del logo de MasterTec para Actas','2025-12-14 20:31:17'),
('4','acta_titulo_empresa','Master Technologies','Nombre de la empresa en el encabezado','2025-12-14 19:29:58'),
('5','acta_subtitulo_empresa','Departamento de RRHH','Subtítulo de la empresa','2025-12-14 19:29:58'),
('6','acta_ingreso_titulo','Acta Informativa de Ingreso','Título del acta de ingreso','2025-12-14 19:29:58'),
('7','acta_ingreso_descripcion','Documento informativo sobre el proceso de ingreso del colaborador','Descripción del acta de ingreso','2025-12-14 19:29:58'),
('8','acta_ingreso_nota_pie','Copia Informativa - No Válida como Contrato','Nota al pie del acta de ingreso','2025-12-14 19:29:58'),
('9','acta_salida_titulo','Acta Informativa de Baja','Título del acta de salida','2025-12-14 19:29:58'),
('10','acta_salida_descripcion','Documento informativo sobre el proceso de salida del colaborador','Descripción del acta de salida','2025-12-14 19:29:58'),
('11','acta_salida_nota_pie','Copia Informativa - No Válida como Finiquito','Nota al pie del acta de salida','2025-12-14 19:29:58'),
('12','acta_label_colaborador','Colaborador','Etiqueta para nombre del colaborador','2025-12-14 19:29:58'),
('13','acta_label_cedula','Cédula','Etiqueta para cédula','2025-12-14 19:29:58'),
('14','acta_label_telefono','Teléfono','Etiqueta para teléfono','2025-12-14 19:29:58'),
('15','acta_label_cargo','Cargo/Zona','Etiqueta para cargo','2025-12-14 19:29:58'),
('16','acta_label_fecha','Fecha','Etiqueta para fecha','2025-12-14 19:29:58'),
('17','acta_label_correo','Correo Electrónico','Etiqueta para correo','2025-12-14 19:29:58'),
('18','acta_label_equipos','Equipos Asignados','Etiqueta para equipos','2025-12-14 19:29:58'),
('19','acta_label_observaciones','Observaciones','Etiqueta para observaciones','2025-12-14 19:29:58'),
('20','acta_ingreso_seccion_datos','Datos del Colaborador','Título sección datos personales','2025-12-14 19:29:58'),
('21','acta_ingreso_seccion_correo','Configuración de Correo','Título sección correo','2025-12-14 19:29:58'),
('22','acta_ingreso_seccion_equipos','Asignación de Equipos','Título sección equipos','2025-12-14 19:29:58'),
('23','acta_ingreso_seccion_accesos','Accesos y Licencias','Título sección accesos','2025-12-14 19:29:58'),
('24','acta_salida_seccion_datos','Datos del Colaborador','Título sección datos personales','2025-12-14 19:29:58'),
('25','acta_salida_seccion_correo','Gestión de Correo','Título sección correo','2025-12-14 19:29:58'),
('26','acta_salida_seccion_equipos','Devolución de Equipos','Título sección equipos','2025-12-14 19:29:58'),
('27','acta_salida_seccion_respaldo','Respaldo de Información','Título sección respaldo','2025-12-14 19:29:58');

-- Estructura de tabla para la tabla `empresas`
DROP TABLE IF EXISTS `empresas`;
CREATE TABLE `empresas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `pais` varchar(50) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `empresas`
INSERT INTO `empresas` VALUES 
('1','MasterTec','Master-ni','Nicaragua','1','2025-12-14 12:52:52','2025-12-14 12:52:52'),
('3','Suministros Integrales','SUMI-MAG','Nicaragua','1','2025-12-14 13:28:09','2025-12-14 13:28:09'),
('4','Centro Printuras','CENT-MAG','Nicaragua','1','2025-12-14 13:30:05','2025-12-15 09:13:15');

-- Estructura de tabla para la tabla `formularios_rrhh`
DROP TABLE IF EXISTS `formularios_rrhh`;
CREATE TABLE `formularios_rrhh` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('Ingreso','Salida') NOT NULL,
  `nombre_colaborador` varchar(100) NOT NULL,
  `fecha_efectiva` date NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) NOT NULL COMMENT 'Usuario de RRHH',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_solicitud` date DEFAULT NULL,
  `cedula_telefono` varchar(50) DEFAULT NULL,
  `cargo_zona` varchar(100) DEFAULT NULL,
  `disponibilidad_licencias` enum('SI','NO') DEFAULT 'NO',
  `detalle_licencias` text DEFAULT NULL,
  `correo_nuevo` enum('SI','NO') DEFAULT 'NO',
  `direccion_correo` varchar(100) DEFAULT NULL,
  `remitente_mostrar` enum('SI','NO') DEFAULT 'NO',
  `detalle_remitente` varchar(100) DEFAULT NULL,
  `respaldo_nube` enum('SI','NO') DEFAULT 'NO',
  `detalle_respaldo` text DEFAULT NULL,
  `reenvios_correo` enum('SI','NO') DEFAULT 'NO',
  `detalle_reenvios` varchar(255) DEFAULT NULL,
  `otras_indicaciones` text DEFAULT NULL,
  `asignacion_equipo` enum('SI','NO') DEFAULT 'NO',
  `detalle_asignacion` text DEFAULT NULL,
  `nube_movil` enum('SI','NO') DEFAULT 'NO',
  `detalle_nube_movil` text DEFAULT NULL,
  `equipo_usado` enum('SI','NO') DEFAULT 'NO',
  `especificacion_equipo_usado` varchar(100) DEFAULT NULL,
  `bloqueo_correo` enum('SI','NO') DEFAULT 'SI',
  `cuenta_correo_bloqueo` varchar(255) DEFAULT NULL,
  `respaldo_info` enum('SI','NO') DEFAULT 'NO',
  `detalle_respaldo_salida` varchar(255) DEFAULT NULL,
  `redireccion_correo` enum('SI','NO') DEFAULT 'NO',
  `email_redireccion` varchar(255) DEFAULT NULL,
  `devolucion_equipo` enum('SI','NO') DEFAULT 'SI',
  `detalle_devolucion_equipo` varchar(255) DEFAULT NULL,
  `devolucion_movil` enum('SI','NO') DEFAULT 'NO',
  `detalle_devolucion_movil` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rrhh_creador` (`creado_por`),
  CONSTRAINT `fk_rrhh_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `formularios_rrhh`
INSERT INTO `formularios_rrhh` VALUES 
('3','Ingreso','test test','0000-00-00',NULL,NULL,'5','2025-12-14 17:20:22','2025-12-14','561-286595-1998 / 81077430','Vendedor','SI','ventas1@mastertec.com.ni','SI','','SI','TEST','NO','','NO','','otro test','SI','','NO',NULL,'NO',NULL,'SI',NULL,'NO',NULL,'NO',NULL,'SI',NULL,'NO',NULL,NULL),
('4','Salida','test test','2025-12-15',NULL,'hagamos otro test','5','2025-12-14 21:44:06','2025-12-15','561-286595-1998 / 81077430','Vendedor','NO',NULL,'NO',NULL,'NO',NULL,'NO',NULL,'NO',NULL,NULL,'NO',NULL,'NO',NULL,'NO',NULL,'SI','todas la cuentas','SI','verificar el respaldo','NO','','SI','','SI','',NULL);

-- Estructura de tabla para la tabla `historial_actividad`
DROP TABLE IF EXISTS `historial_actividad`;
CREATE TABLE `historial_actividad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_historial_usuario` (`usuario_id`),
  CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `historial_actividad`
INSERT INTO `historial_actividad` VALUES 
('1','1','Crear Usuario','Usuario: JuanTest (Empresa ID: )','2025-12-14 12:43:03'),
('2','1','Crear Usuario','Usuario: pedroTest (Empresa ID: )','2025-12-14 12:43:49'),
('3','1','Actualizar Usuario','Usuario ID: 4','2025-12-14 12:44:04'),
('4','1','Actualizar Usuario','Usuario actualizado: PedroTest','2025-12-14 12:44:04'),
('5','1','Crear Usuario','Usuario: HHRR (Empresa ID: )','2025-12-14 13:34:03'),
('6','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 13:35:13'),
('7','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 13:35:14'),
('8','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 13:35:32'),
('9','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 13:35:32'),
('10','1','Actualizar Permisos','Permisos actualizados para rol ID: 5','2025-12-14 13:36:29'),
('11','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 15:35:02'),
('12','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 15:35:03'),
('13','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 15:35:27'),
('14','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 15:35:27'),
('15','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:01:16'),
('16','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:01:16'),
('17','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:01:33'),
('18','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:01:33'),
('19','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:05:12'),
('20','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:05:12'),
('21','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:05:30'),
('22','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:05:30'),
('23','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:05:44'),
('24','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:05:44'),
('25','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:06:26'),
('26','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:06:26'),
('27','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 16:06:46'),
('28','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 16:06:46'),
('29','5','Registrar Ingreso RRHH','Ingreso: test test - Ticket #2','2025-12-14 17:20:22'),
('30','1','Configuración','Logos de actas actualizados','2025-12-14 20:31:17'),
('31','1','Actualizar Usuario','Usuario ID: 5','2025-12-14 21:42:00'),
('32','1','Actualizar Usuario','Usuario actualizado: RRHH','2025-12-14 21:42:00'),
('33','5','Registrar Salida RRHH','Salida: test test - Ticket #3','2025-12-14 21:44:06'),
('34','1','Configuración','Logos de actas actualizados','2025-12-14 22:11:50'),
('35','1','Configuración','Logos de actas actualizados','2025-12-15 08:54:32'),
('36','1','Actualizar Permisos','Permisos actualizados para rol ID: 1','2025-12-15 09:14:05'),
('37','1','Crear Categoría','Categoría: Hardware','2025-12-15 09:47:45'),
('38','1','Crear Categoría','Categoría: Software','2025-12-15 09:47:53'),
('39','1','Crear Categoría','Categoría: Redes','2025-12-15 09:47:57'),
('40','1','Crear Categoría','Categoría: Formulario','2025-12-15 09:48:09'),
('41','1','Crear Categoría','Categoría: Otros','2025-12-15 09:48:21'),
('42','1','Actualizar Permisos','Permisos actualizados para rol ID: 1','2025-12-15 09:49:43'),
('43','1','Eliminar Usuario','Usuario eliminado: PedroTest','2025-12-15 11:22:47'),
('44','1','Crear Usuario','Usuario: PedroTest (Empresa ID: 1)','2025-12-15 11:23:43'),
('45','1','Eliminar Usuario','Usuario eliminado: PedroTest','2025-12-15 11:25:41'),
('46','1','Crear Usuario','Usuario: PedroTest (Empresa ID: 1)','2025-12-15 11:26:09'),
('47','7','Crear Ticket','Ticket creado: No tengo acceso al correo','2025-12-15 11:33:53'),
('48','7','Crear Ticket','Ticket creado: acceso al onedrive','2025-12-15 11:35:42'),
('49','7','Crear Ticket','Ticket creado: mi pc se pega','2025-12-15 11:36:13'),
('50','7','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:36:50'),
('51','7','Crear Ticket','Ticket creado: no tengo internet','2025-12-15 11:37:08'),
('52','1','Asignar Ticket','Ticket ID: 2 a Técnico ID: 3','2025-12-15 11:39:02'),
('53','1','Asignar Ticket','Ticket ID: 3 a Técnico ID: 3','2025-12-15 11:39:10'),
('54','1','Asignar Ticket','Ticket ID: 5 a Técnico ID: 3','2025-12-15 11:39:12'),
('55','1','Asignar Ticket','Ticket ID: 7 a Técnico ID: 3','2025-12-15 11:39:23'),
('56','7','Crear Ticket','Ticket creado: test','2025-12-15 11:41:46'),
('57','1','Asignar Ticket','Ticket ID: 7 a Técnico ID: 3','2025-12-15 11:45:31'),
('58','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:45:38'),
('59','1','Actualizar Ticket','Ticket ID: 7 - Estado actualizado a Resuelto y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:45:38'),
('60','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:46:54'),
('61','1','Actualizar Ticket','Ticket ID: 7 - Estado actualizado a Resuelto y Prioridad a Alta (Información actualizada) (Categoría actualizada) + Nota de resolución guardada','2025-12-15 11:46:54'),
('62','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:48:42'),
('63','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a Pendiente y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:48:42'),
('64','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:48:56'),
('65','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a En Atención y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:48:56'),
('66','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:49:03'),
('67','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a Cerrado y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:49:03'),
('68','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:50:13'),
('69','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a Cerrado y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:50:13'),
('70','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:50:18'),
('71','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a Cerrado y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:50:18'),
('72','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:50:39'),
('73','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a Cerrado y Prioridad a Alta (Información actualizada) (Categoría actualizada) + Nota de resolución guardada','2025-12-15 11:50:39'),
('74','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:52:20'),
('75','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a Cerrado y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:52:20'),
('76','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:52:25'),
('77','1','Actualizar Ticket','Ticket ID: 11 - Estado actualizado a En Atención y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:52:25'),
('78','1','Crear Ticket','Ticket creado: mi pc no enciende','2025-12-15 11:52:52'),
('79','1','Actualizar Ticket','Ticket ID: 19 - Estado actualizado a Cerrado y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2025-12-15 11:52:52'),
('80','1','Actualizar Permisos','Permisos actualizados para rol ID: 3','2025-12-15 12:45:11'),
('81','1','Actualizar Usuario','Usuario ID: 1','2026-01-06 14:38:30'),
('82','1','Actualizar Usuario','Usuario actualizado: SuperAdmin','2026-01-06 14:38:30'),
('83','1','Actualizar Ticket','Ticket ID: 20 - Estado actualizado a Pendiente y Prioridad a Alta (Información actualizada) (Categoría actualizada)','2026-01-06 15:09:04'),
('84','1','Asignar Ticket','Ticket ID: 20 a Técnico ID: 3','2026-01-06 15:09:37'),
('85','1','Asignar Ticket','Ticket ID: 18 a Técnico ID: 3','2026-01-06 15:09:42'),
('86','1','Asignar Ticket','Ticket ID: 17 a Técnico ID: 3','2026-01-06 15:09:50'),
('87','1','Asignar Ticket','Ticket ID: 16 a Técnico ID: 3','2026-01-06 15:09:56'),
('88','1','Crear Ticket','Ticket creado: esto es un test para ver como se comporta esta parte','2026-01-07 08:10:42'),
('89','1','Actualizar Usuario','Usuario ID: 1','2026-01-07 08:21:40'),
('90','1','Actualizar Usuario','Usuario actualizado: SuperAdmin','2026-01-07 08:21:40'),
('91','1','Actualizar Usuario','Usuario ID: 5','2026-01-07 08:24:05'),
('92','1','Actualizar Usuario','Usuario actualizado: RRHH','2026-01-07 08:24:05'),
('93','1','Actualizar Usuario','Usuario ID: 5','2026-01-07 08:24:53'),
('94','1','Actualizar Usuario','Usuario actualizado: RRHH','2026-01-07 08:24:54');

-- Estructura de tabla para la tabla `historial_cambios`
DROP TABLE IF EXISTS `historial_cambios`;
CREATE TABLE `historial_cambios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entidad_tipo` varchar(50) NOT NULL COMMENT 'Ej: personal, ticket, usuario',
  `entidad_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `campo_modificado` varchar(100) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historial_cambios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `historial_cambios`

-- Estructura de tabla para la tabla `inventario`
DROP TABLE IF EXISTS `inventario`;
CREATE TABLE `inventario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('Laptop','PC','Monitor','Teclado','Mouse','Headset','Silla','Escritorio','Movil','Impresora','Otro') NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serial` varchar(100) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `estado` enum('Nuevo','Buen Estado','Regular','Malo','En Reparacion') DEFAULT 'Nuevo',
  `condicion` enum('Disponible','Asignado','Dado de Baja') DEFAULT 'Disponible',
  `asignado_a` varchar(200) DEFAULT NULL COMMENT 'Nombre del colaborador o ID usuario',
  `fecha_asignacion` datetime DEFAULT NULL,
  `fecha_compra` date DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial` (`serial`),
  UNIQUE KEY `idx_sku_unique` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `inventario`

-- Estructura de tabla para la tabla `mantenimiento_equipos`
DROP TABLE IF EXISTS `mantenimiento_equipos`;
CREATE TABLE `mantenimiento_equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipo_id` int(11) NOT NULL,
  `tipo_mantenimiento` enum('Preventivo','Correctivo','Upgrade') NOT NULL,
  `descripcion_problema` text DEFAULT NULL,
  `descripcion_solucion` text DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT 0.00,
  `proveedor` varchar(100) DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_estimada_fin` date DEFAULT NULL,
  `horas_estimadas` decimal(5,2) DEFAULT 0.00,
  `estado` enum('Programado','En Proceso','Completado','Cancelado') DEFAULT 'En Proceso',
  `prioridad` enum('Baja','Media','Alta','Urgente') DEFAULT 'Media',
  `registrado_por` int(11) DEFAULT NULL,
  `responsable_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `fk_responsable` (`responsable_id`),
  CONSTRAINT `fk_responsable` FOREIGN KEY (`responsable_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mantenimiento_equipos_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `inventario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mantenimiento_equipos_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `mantenimiento_equipos`

-- Estructura de tabla para la tabla `modulos`
DROP TABLE IF EXISTS `modulos`;
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `etiqueta` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `modulos`
INSERT INTO `modulos` VALUES 
('14','dashboard','Ver Dashboard','Acceso al panel principal'),
('15','crear_ticket','Crear Ticket','Permite crear nuevos tickets'),
('16','mis_tickets','Mis Tickets','Ver tickets creados por el usuario'),
('17','gestion_usuarios','Gestión de Usuarios','Crear, editar y eliminar usuarios'),
('18','asignar_tickets','Asignar Tickets','Asignar tickets a técnicos'),
('19','mis_tareas','Mis Tareas (Técnico)','Ver tickets asignados para resolver'),
('20','reportes','Ver Reportes','Acceso a estadísticas y gráficos'),
('21','rrhh_altas','RRHH: Altas','Formularios de ingreso de personal'),
('22','rrhh_bajas','RRHH: Bajas','Formularios de salida de personal'),
('23','rrhh_historial','RRHH: Historial','Ver historial de ingresos y salidas'),
('24','backup_bd','Backup Base de Datos','Realizar copias de seguridad'),
('25','restaurar_bd','Restaurar Base de Datos','Restaurar copias de seguridad'),
('26','reiniciar_bd','Reiniciar Base de Datos','Reiniciar la base de datos a estado de fábrica'),
('27','gestion_permisos','Gestión de Permisos','Administrar permisos de roles'),
('28','configuracion','Configuración del Sistema','Ajustes generales'),
('29','categorias','Gestión de Categorías','Administrar categorías de tickets'),
('30','seguimiento_tickets','Seguimiento de Tickets','Ver historial y seguimiento completo de todos los tickets del sistema'),
('31','gestion_personal','Gestión de Personal','Módulo de Gestión de Empleados Multi-Empresa'),
('32','gestion_sucursales','Gestión de Sucursales','Gestión de Estructura Organizativa (Empresas y Sucursales)'),
('33','rrhh_inventario','Inventario de Activos','Gestión de equipos informáticos y mobiliario'),
('34','rrhh_registro_equipo','Registro de Equipo','Registrar nuevos equipos en el inventario'),
('35','rrhh_asignacion_equipos','Asignación de Equipos','Asignar, reasignar y liberar equipos del inventario'),
('37','estadisticas_globales','Estadísticas Globales','Dashboard integral de métricas y KPIs del sistema.'),
('38','personal_importar','Importar Personal','Permite carga masiva de empleados desde Excel/CSV.'),
('39','historial_tecnico','Historial Técnico','Visualización detallada del historial de tickets por técnico.'),
('40','registros_365','Registro Cuentas 365','Gestión de licencias y cuentas Microsoft 365'),
('41','mantenimiento_equipos','Control Mantenimiento','Gestión de mantenimientos y reparaciones de equipos'),
('42','visualizacion_it','Visualización IT','Vista de tarjetas con información completa de cuentas 365 y equipos'),
('43','cargos','Gestión de Cargos','Gestión de Cargos/Puestos');

-- Estructura de tabla para la tabla `notificaciones`
DROP TABLE IF EXISTS `notificaciones`;
CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` varchar(20) DEFAULT 'info',
  `leida` tinyint(1) DEFAULT 0,
  `enlace` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `leida` (`leida`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `notificaciones`
INSERT INTO `notificaciones` VALUES 
('1','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #2. Por favor revísalo.','info','0','index.php?view=mis_tickets','2025-12-15 11:39:02'),
('2','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #3. Por favor revísalo.','info','0','index.php?view=mis_tickets','2025-12-15 11:39:10'),
('3','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #5. Por favor revísalo.','info','0','index.php?view=mis_tickets','2025-12-15 11:39:12'),
('4','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #7. Por favor revísalo.','info','0','index.php?view=mis_tickets','2025-12-15 11:39:23'),
('5','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #7. Por favor revísalo.','info','0','index.php?view=mis_tickets','2025-12-15 11:45:31'),
('6','7','Ticket Resuelto','Tu ticket #7 \'mi pc no enciende\' ha sido marcado como Resuelto.','success','0','index.php?view=mis_tickets','2025-12-15 11:45:38'),
('7','7','Ticket Resuelto','Tu ticket #7 \'mi pc no enciende\' ha sido marcado como Resuelto.','success','0','index.php?view=mis_tickets','2025-12-15 11:46:54'),
('8','1','Ticket Cerrado','Tu ticket #11 \'mi pc no enciende\' ha sido marcado como Cerrado.','info','0','index.php?view=mis_tickets','2025-12-15 11:49:03'),
('9','1','Ticket Cerrado','Tu ticket #11 \'mi pc no enciende\' ha sido marcado como Cerrado.','info','0','index.php?view=mis_tickets','2025-12-15 11:50:13'),
('10','1','Ticket Cerrado','Tu ticket #11 \'mi pc no enciende\' ha sido marcado como Cerrado.','info','0','index.php?view=mis_tickets','2025-12-15 11:50:18'),
('11','1','Ticket Cerrado','Tu ticket #11 \'mi pc no enciende\' ha sido marcado como Cerrado.','info','0','index.php?view=mis_tickets','2025-12-15 11:50:39'),
('12','1','Ticket Cerrado','Tu ticket #11 \'mi pc no enciende\' ha sido marcado como Cerrado.','info','0','index.php?view=mis_tickets','2025-12-15 11:52:20'),
('13','1','Ticket Cerrado','Tu ticket #19 \'mi pc no enciende\' ha sido marcado como Cerrado.','info','0','index.php?view=mis_tickets','2025-12-15 11:52:52'),
('14','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #20. Por favor revísalo.','info','0','index.php?view=mis_tickets','2026-01-06 15:09:37'),
('15','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #18. Por favor revísalo.','info','0','index.php?view=mis_tickets','2026-01-06 15:09:42'),
('16','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #17. Por favor revísalo.','info','0','index.php?view=mis_tickets','2026-01-06 15:09:50'),
('17','3','Nuevo Ticket Asignado','Se te ha asignado el ticket #16. Por favor revísalo.','info','0','index.php?view=mis_tickets','2026-01-06 15:09:56');

-- Estructura de tabla para la tabla `permisos_roles`
DROP TABLE IF EXISTS `permisos_roles`;
CREATE TABLE `permisos_roles` (
  `rol_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  PRIMARY KEY (`rol_id`,`modulo_id`),
  KEY `fk_permiso_modulo` (`modulo_id`),
  CONSTRAINT `fk_permiso_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_permiso_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `permisos_roles`
INSERT INTO `permisos_roles` VALUES 
('1','14'),
('1','15'),
('1','16'),
('1','17'),
('1','18'),
('1','20'),
('1','21'),
('1','23'),
('1','24'),
('1','25'),
('1','26'),
('1','27'),
('1','28'),
('1','29'),
('1','30'),
('1','31'),
('1','32'),
('1','33'),
('1','34'),
('1','35'),
('1','38'),
('1','40'),
('1','41'),
('1','42'),
('1','43'),
('2','14'),
('2','15'),
('2','16'),
('2','18'),
('2','19'),
('2','29'),
('2','31'),
('2','32'),
('2','33'),
('2','43'),
('3','14'),
('3','19'),
('3','40'),
('3','41'),
('3','42'),
('3','43'),
('4','14'),
('4','15'),
('4','16'),
('4','20'),
('4','23'),
('4','37'),
('5','14'),
('5','15'),
('5','16'),
('5','21'),
('5','23'),
('5','31'),
('5','33'),
('5','34'),
('5','35'),
('5','38'),
('5','43'),
('6','14'),
('6','15'),
('6','16');

-- Estructura de tabla para la tabla `personal`
DROP TABLE IF EXISTS `personal`;
CREATE TABLE `personal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `codigo_empleado` varchar(20) DEFAULT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cedula` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` varchar(20) DEFAULT NULL,
  `estado_civil` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(50) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_salida` date DEFAULT NULL,
  `tipo_contrato` varchar(50) DEFAULT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `usuario_sistema_id` int(11) DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'Activo',
  `foto_url` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `modificado_por` int(11) DEFAULT NULL,
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_empleado` (`codigo_empleado`),
  KEY `usuario_sistema_id` (`usuario_sistema_id`),
  KEY `creado_por` (`creado_por`),
  KEY `modificado_por` (`modificado_por`),
  KEY `idx_personal_empresa` (`empresa_id`),
  KEY `idx_personal_sucursal` (`sucursal_id`),
  KEY `idx_personal_estado` (`estado`),
  KEY `idx_personal_codigo` (`codigo_empleado`),
  CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personal_ibfk_3` FOREIGN KEY (`usuario_sistema_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `personal_ibfk_4` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `personal_ibfk_5` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `personal`
INSERT INTO `personal` VALUES 
('1','1','7','EMP-MST-HN-260941','Usuario Test 1','De Master Honduras','001-26094-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u260941@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('2','1','7','EMP-MST-HN-608462','Usuario Test 2','De Master Honduras','001-60846-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u608462@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('3','1','1','EMP-Mt-ln-344171','Usuario Test 1','De Master Leon','001-34417-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u344171@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('4','1','1','EMP-Mt-ln-659292','Usuario Test 2','De Master Leon','001-65929-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u659292@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('5','3','2','EMP-Seb-SUMI-752771','Usuario Test 1','De Sebaco','001-75277-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u752771@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('6','3','2','EMP-Seb-SUMI-507812','Usuario Test 2','De Sebaco','001-50781-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u507812@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('7','3','3','EMP-Leo-SUMI-640881','Usuario Test 1','De Leon','001-64088-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u640881@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('8','3','3','EMP-Leo-SUMI-857262','Usuario Test 2','De Leon','001-85726-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u857262@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('9','3','6','EMP-SUMI-HN-160481','Usuario Test 1','De Suministros Honduras','001-16048-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u160481@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('10','3','6','EMP-SUMI-HN-317772','Usuario Test 2','De Suministros Honduras','001-31777-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u317772@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('11','4','5','EMP-RD-Cent-890661','Usuario Test 1','De Republica Dominicana','001-89066-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u890661@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('12','4','5','EMP-RD-Cent-237462','Usuario Test 2','De Republica Dominicana','001-23746-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u237462@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('13','4','4','EMP-SALv-Cent-990481','Usuario Test 1','De El Salvador','001-99048-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u990481@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('14','4','4','EMP-SALv-Cent-949882','Usuario Test 2','De El Salvador','001-94988-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u949882@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56'),
('15','1','8','','Deyvi Javier','Martinez Abarca','561-260389-0005E','1989-03-26','Masculino','Soltero','81743279','','adohuken2005@gmail.com','Managua','Managua','Nicaragua','Jefe IT','IT','2025-05-20',NULL,'Indefinido',NULL,'1','Activo',NULL,'','1','2025-12-14 15:23:54',NULL,'2025-12-14 15:23:54');

-- Estructura de tabla para la tabla `personal_historial`
DROP TABLE IF EXISTS `personal_historial`;
CREATE TABLE `personal_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `personal_id` int(11) NOT NULL,
  `tipo_cambio` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `empresa_anterior_id` int(11) DEFAULT NULL,
  `sucursal_anterior_id` int(11) DEFAULT NULL,
  `cargo_anterior` varchar(100) DEFAULT NULL,
  `salario_anterior` decimal(10,2) DEFAULT NULL,
  `empresa_nueva_id` int(11) DEFAULT NULL,
  `sucursal_nueva_id` int(11) DEFAULT NULL,
  `cargo_nuevo` varchar(100) DEFAULT NULL,
  `salario_nuevo` decimal(10,2) DEFAULT NULL,
  `fecha_efectiva` date NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `personal_id` (`personal_id`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `personal_historial_ibfk_1` FOREIGN KEY (`personal_id`) REFERENCES `personal` (`id`) ON DELETE CASCADE,
  CONSTRAINT `personal_historial_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `personal_historial`

-- Estructura de tabla para la tabla `registros_365`
DROP TABLE IF EXISTS `registros_365`;
CREATE TABLE `registros_365` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `cargo_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `licencia` varchar(50) DEFAULT 'Business Basic',
  `estado` enum('Activo','Inactivo','Suspendido') DEFAULT 'Activo',
  `fecha_asignacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `password_ag` varchar(255) DEFAULT NULL COMMENT 'Contraseña de Active Directory/Azure AD',
  `password_azure` varchar(255) DEFAULT NULL,
  `cuenta_gmail` varchar(100) DEFAULT NULL COMMENT 'Cuenta Gmail asociada',
  `password_gmail` varchar(255) DEFAULT NULL,
  `telefono_principal` varchar(20) DEFAULT NULL COMMENT 'Número de teléfono principal',
  `telefono_secundario` varchar(20) DEFAULT NULL COMMENT 'Número de teléfono secundario',
  `pin_windows` varchar(50) DEFAULT NULL COMMENT 'PIN de inicio de sesión Windows',
  `notas_adicionales` text DEFAULT NULL COMMENT 'Notas adicionales de configuración',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `registros_365_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `registros_365`

-- Estructura de tabla para la tabla `roles`
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL COMMENT 'Nombre del rol (SuperAdmin, Admin, Tecnico, Gerencia, RRHH, Usuario)',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción de los permisos del rol',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `roles`
INSERT INTO `roles` VALUES 
('1','SuperAdmin','Acceso total al sistema y configuraciones avanzadas de BD'),
('2','Admin','Gestión de usuarios y asignación de tickets'),
('3','Tecnico','Resolución de tickets asignados'),
('4','Gerencia','Visualización de reportes y creación de tickets'),
('5','RRHH','Gestión de ingresos/salidas y soporte interno'),
('6','Usuario','Creación y seguimiento de tickets básicos');

-- Estructura de tabla para la tabla `sucursales`
DROP TABLE IF EXISTS `sucursales`;
CREATE TABLE `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(50) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_modificacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `empresa_id` (`empresa_id`,`codigo`),
  CONSTRAINT `sucursales_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `sucursales`
INSERT INTO `sucursales` VALUES 
('1','1','Master Leon','Mt-ln','Leon','Nicaragua',NULL,NULL,NULL,'1','2025-12-14 13:20:53','2025-12-14 13:20:53'),
('2','3','Sebaco','Seb-SUMI','Sebaco','Nicaragua',NULL,NULL,NULL,'1','2025-12-14 13:28:47','2025-12-14 13:28:47'),
('3','3','Leon','Leo-SUMI','Leon','Nicaragua',NULL,NULL,NULL,'1','2025-12-14 13:29:24','2025-12-14 13:29:24'),
('4','4','El Salvador','SALv-Cent','San Salvador','El Salvador',NULL,NULL,NULL,'1','2025-12-14 13:31:05','2025-12-14 13:31:05'),
('5','4','Republica Dominicana','RD-Cent','Santo Domingo','Republica Dominicana',NULL,NULL,NULL,'1','2025-12-14 13:32:09','2025-12-14 13:32:09'),
('6','3','Suministros Honduras','SUMI-HN','Tegusigalpa','Honduras',NULL,NULL,NULL,'1','2025-12-14 13:32:42','2025-12-14 13:32:42'),
('7','1','Master Honduras','MST-HN','Tegusigalpa','Honduras',NULL,NULL,NULL,'1','2025-12-14 13:33:14','2025-12-14 13:33:14'),
('8','1','Master Managua','MAs-MAG','Managua','Nicaragua',NULL,NULL,NULL,'1','2025-12-14 15:20:55','2025-12-14 15:20:55'),
('9','4','Centro Managua','cp-MGA','Managua','Nicaragua',NULL,NULL,NULL,'1','2025-12-15 09:13:40','2025-12-15 09:13:40');

-- Estructura de tabla para la tabla `tickets`
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `resolucion` text DEFAULT NULL,
  `prioridad` enum('Baja','Media','Alta','Critica') DEFAULT 'Media',
  `estado` enum('Abierto','Pendiente','En Progreso','En Atención','Resuelto','Cerrado') DEFAULT 'Pendiente',
  `creador_id` int(11) NOT NULL COMMENT 'Usuario que creó el ticket',
  `categoria_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ticket_creador` (`creador_id`),
  KEY `fk_ticket_categoria` (`categoria_id`),
  CONSTRAINT `fk_ticket_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ticket_creador` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `tickets`
INSERT INTO `tickets` VALUES 
('2','Nuevo Ingreso: test test','SOLICITUD DE INGRESO DE NUEVO COLABORADOR\n\nINFORMACIÓN DEL COLABORADOR:\n• Nombre: test test\n• Cargo/Zona: Vendedor\n• Fecha de Ingreso: 14/12/2025\n\nEQUIPOS: Pendiente de asignación',NULL,'Media','En Atención','5',NULL,'2025-12-14 17:20:22','2025-12-15 11:39:02'),
('3','Baja de Personal: test test','SOLICITUD DE BAJA DE COLABORADOR\n\nINFORMACIÓN DEL COLABORADOR:\n• Nombre: test test\n• Cargo/Zona: Vendedor\n• Fecha Efectiva de Salida: 15/12/2025',NULL,'Alta','En Atención','5',NULL,'2025-12-14 21:44:06','2025-12-15 11:39:10'),
('4','No tengo acceso al correo','El outlok se queda cargando em el login y no pasa de ahí ',NULL,'Alta','Pendiente','7','2','2025-12-15 11:33:53','2025-12-15 11:33:53'),
('5','acceso al onedrive','test',NULL,'Media','En Atención','7','1','2025-12-15 11:35:42','2025-12-15 11:39:12'),
('6','mi pc se pega','test',NULL,'Critica','Pendiente','7','4','2025-12-15 11:36:13','2025-12-15 11:36:13'),
('7','mi pc no enciende','test','📝 [15/12/2025 18:46] ready','Alta','Resuelto','7','5','2025-12-15 11:36:50','2025-12-15 11:46:54'),
('8','no tengo internet','test',NULL,'Baja','Pendiente','7','3','2025-12-15 11:37:08','2025-12-15 11:37:08'),
('9','test','test',NULL,'Media','Pendiente','7','4','2025-12-15 11:41:46','2025-12-15 11:41:46'),
('10','mi pc no enciende','test',NULL,'Alta','Pendiente','1','5','2025-12-15 11:45:38','2025-12-15 11:45:38'),
('11','mi pc no enciende','test','📝 [15/12/2025 18:50] ready','Alta','En Atención','1','5','2025-12-15 11:46:54','2025-12-15 11:52:25'),
('12','mi pc no enciende','test',NULL,'Alta','Pendiente','1','5','2025-12-15 11:48:42','2025-12-15 11:48:42'),
('13','mi pc no enciende','test',NULL,'Alta','Pendiente','1','5','2025-12-15 11:48:56','2025-12-15 11:48:56'),
('14','mi pc no enciende','test',NULL,'Alta','Pendiente','1','5','2025-12-15 11:49:03','2025-12-15 11:49:03'),
('15','mi pc no enciende','test',NULL,'Alta','Pendiente','1','5','2025-12-15 11:50:13','2025-12-15 11:50:13'),
('16','mi pc no enciende','test',NULL,'Alta','En Atención','1','5','2025-12-15 11:50:18','2026-01-06 15:09:56'),
('17','mi pc no enciende','test',NULL,'Alta','En Atención','1','5','2025-12-15 11:50:39','2026-01-06 15:09:50'),
('18','mi pc no enciende','test',NULL,'Alta','En Atención','1','5','2025-12-15 11:52:20','2026-01-06 15:09:42'),
('19','mi pc no enciende','test',NULL,'Alta','Cerrado','1','5','2025-12-15 11:52:25','2025-12-15 11:52:52'),
('20','mi pc no enciende','test2',NULL,'Alta','En Atención','1','5','2025-12-15 11:52:52','2026-01-06 15:09:37'),
('21','esto es un test para ver como se comporta esta parte','se realizara un test para verificar si hay algun problema con esta seccion',NULL,'Alta','Pendiente','1','5','2026-01-07 08:10:42','2026-01-07 08:10:42');

-- Estructura de tabla para la tabla `usuarios`
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hash de la contraseña',
  `rol_id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `sucursal_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `notifs_email` tinyint(1) DEFAULT 0,
  `notifs_sonido` tinyint(1) DEFAULT 1,
  `empresa_asignada` enum('mastertec','suministros','centro') DEFAULT NULL COMMENT 'Empresa asignada para usuarios RRHH',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_rol` (`rol_id`),
  KEY `fk_usuario_empresa` (`empresa_id`),
  KEY `fk_usuario_sucursal` (`sucursal_id`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `usuarios`
INSERT INTO `usuarios` VALUES 
('1','SuperAdmin','superadmin@ticketsys.com','$2y$10$UNfwGQP.A4RyY4HXsp1JCOph3w0uCFAt..k/N2/FS3t55E3y8Gn3C','1',NULL,NULL,'2025-12-14 12:20:37','0','1',NULL),
('3','JuanTest','juan@mastertec.com.ni','$2y$10$WOsAtlVtxGUghXWiIdE4buk1JkT4ZH5E/w7Ondk/IWcAK2YlkQZlG','3',NULL,NULL,'2025-12-14 12:43:03','0','1',NULL),
('5','RRHH','RRHH@mastertec.com.ni','$2y$10$JcklbpaMG7WSDjti6AW3quGb341Byz4nhKmOjINq0a5jk17s.lv16','5',NULL,NULL,'2025-12-14 13:34:03','0','1','centro'),
('7','PedroTest','pedro@gmail.com','$2y$10$NeXPPxgWQ.GvC0XdjM.7LesOq43j2hzrQRG..dc0bpPb.Vdpxb9RC','6','1','8','2025-12-15 11:26:09','0','1',NULL);

-- Estructura de tabla para la tabla `usuarios_accesos`
DROP TABLE IF EXISTS `usuarios_accesos`;
CREATE TABLE `usuarios_accesos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_acceso` (`usuario_id`,`sucursal_id`),
  KEY `sucursal_id` (`sucursal_id`),
  CONSTRAINT `usuarios_accesos_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcado de datos para la tabla `usuarios_accesos`
INSERT INTO `usuarios_accesos` VALUES 
('70','5','4','2026-01-07 08:24:53'),
('71','5','5','2026-01-07 08:24:53'),
('72','5','7','2026-01-07 08:24:53'),
('73','5','1','2026-01-07 08:24:53'),
('74','5','8','2026-01-07 08:24:53'),
('75','5','3','2026-01-07 08:24:53'),
('76','5','2','2026-01-07 08:24:53'),
('77','5','6','2026-01-07 08:24:53');

-- Estructura de tabla para la tabla `vista_personal_completo`
DROP TABLE IF EXISTS `vista_personal_completo`;
;

-- Volcado de datos para la tabla `vista_personal_completo`
INSERT INTO `vista_personal_completo` VALUES 
('1','1','7','EMP-MST-HN-260941','Usuario Test 1','De Master Honduras','001-26094-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u260941@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','MasterTec','Master-ni','Master Honduras','MST-HN','Tegusigalpa','Honduras',NULL,'0'),
('2','1','7','EMP-MST-HN-608462','Usuario Test 2','De Master Honduras','001-60846-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u608462@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','MasterTec','Master-ni','Master Honduras','MST-HN','Tegusigalpa','Honduras',NULL,'0'),
('3','1','1','EMP-Mt-ln-344171','Usuario Test 1','De Master Leon','001-34417-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u344171@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','MasterTec','Master-ni','Master Leon','Mt-ln','Leon','Nicaragua',NULL,'0'),
('4','1','1','EMP-Mt-ln-659292','Usuario Test 2','De Master Leon','001-65929-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u659292@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','MasterTec','Master-ni','Master Leon','Mt-ln','Leon','Nicaragua',NULL,'0'),
('5','3','2','EMP-Seb-SUMI-752771','Usuario Test 1','De Sebaco','001-75277-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u752771@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Suministros Integrales','SUMI-MAG','Sebaco','Seb-SUMI','Sebaco','Nicaragua',NULL,'0'),
('6','3','2','EMP-Seb-SUMI-507812','Usuario Test 2','De Sebaco','001-50781-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u507812@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Suministros Integrales','SUMI-MAG','Sebaco','Seb-SUMI','Sebaco','Nicaragua',NULL,'0'),
('7','3','3','EMP-Leo-SUMI-640881','Usuario Test 1','De Leon','001-64088-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u640881@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Suministros Integrales','SUMI-MAG','Leon','Leo-SUMI','Leon','Nicaragua',NULL,'0'),
('8','3','3','EMP-Leo-SUMI-857262','Usuario Test 2','De Leon','001-85726-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u857262@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Suministros Integrales','SUMI-MAG','Leon','Leo-SUMI','Leon','Nicaragua',NULL,'0'),
('9','3','6','EMP-SUMI-HN-160481','Usuario Test 1','De Suministros Honduras','001-16048-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u160481@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Suministros Integrales','SUMI-MAG','Suministros Honduras','SUMI-HN','Tegusigalpa','Honduras',NULL,'0'),
('10','3','6','EMP-SUMI-HN-317772','Usuario Test 2','De Suministros Honduras','001-31777-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u317772@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Suministros Integrales','SUMI-MAG','Suministros Honduras','SUMI-HN','Tegusigalpa','Honduras',NULL,'0'),
('11','4','5','EMP-RD-Cent-890661','Usuario Test 1','De Republica Dominicana','001-89066-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u890661@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Centro Printuras','CENT-MAG','Republica Dominicana','RD-Cent','Santo Domingo','Republica Dominicana',NULL,'0'),
('12','4','5','EMP-RD-Cent-237462','Usuario Test 2','De Republica Dominicana','001-23746-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u237462@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Centro Printuras','CENT-MAG','Republica Dominicana','RD-Cent','Santo Domingo','Republica Dominicana',NULL,'0'),
('13','4','4','EMP-SALv-Cent-990481','Usuario Test 1','De El Salvador','001-99048-1000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u990481@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Centro Printuras','CENT-MAG','El Salvador','SALv-Cent','San Salvador','El Salvador',NULL,'0'),
('14','4','4','EMP-SALv-Cent-949882','Usuario Test 2','De El Salvador','001-94988-2000L','1990-01-01','Otro',NULL,'555-5555',NULL,'u949882@test.com','Direccion Test 123','Ciudad Test','Pais Test','Tester Operativo','Control de Calidad','2025-12-14',NULL,'Indefinido',NULL,NULL,'Activo',NULL,NULL,'1','2025-12-14 13:47:56',NULL,'2025-12-14 13:47:56','Centro Printuras','CENT-MAG','El Salvador','SALv-Cent','San Salvador','El Salvador',NULL,'0'),
('15','1','8','','Deyvi Javier','Martinez Abarca','561-260389-0005E','1989-03-26','Masculino','Soltero','81743279','','adohuken2005@gmail.com','Managua','Managua','Nicaragua','Jefe IT','IT','2025-05-20',NULL,'Indefinido',NULL,'1','Activo',NULL,'','1','2025-12-14 15:23:54',NULL,'2025-12-14 15:23:54','MasterTec','Master-ni','Master Managua','MAs-MAG','Managua','Nicaragua','SuperAdmin','0');

COMMIT;
