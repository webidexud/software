<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Estratégico de Modernización Tecnológica SGPOE</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
            --info: #3498db;
            --danger: #e74c3c;
            --gray: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .university-logo {
            background-color: white;
            padding: 10px;
            border-radius: 8px;
            height: 80px;
        }
        
        .header-title {
            flex-grow: 1;
            padding-left: 2rem;
        }
        
        .header-title h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white; 
        }
        
        .header-title p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        section h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
            font-size: 1.8rem;
        }
        
        section h3 {
            color: var(--secondary);
            margin: 1.5rem 0 1rem;
            font-size: 1.4rem;
        }
        
        p {
            margin-bottom: 1rem;
        }
        
        ul, ol {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        li {
            margin-bottom: 0.5rem;
        }
        
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card {
            flex: 1 1 300px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .icon-feature {
            text-align: center;
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }
        
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 2rem auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: var(--light);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
        }
        
        .timeline-item:nth-child(odd) {
            left: 0;
        }
        
        .timeline-item:nth-child(even) {
            left: 50%;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -13px;
            background-color: white;
            border: 4px solid var(--secondary);
            border-radius: 50%;
            top: 15px;
            z-index: 1;
        }
        
        .timeline-item:nth-child(even)::after {
            left: -13px;
        }
        
        .timeline-content {
            width: 90%;
            padding: 20px;
            background-color: white;
            position: relative;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .timeline-content h3 {
            margin-top: 0;
        }
        
        .timeline-duration {
            background-color: var(--secondary);
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .architecture-diagram {
            background-color: var(--light);
            padding: 2rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            text-align: center;
        }
        
        .architecture-layers {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .layer {
            padding: 1rem;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .frontend-layer {
            background-color: var(--secondary);
        }
        
        .backend-layer {
            background-color: var(--info);
        }
        
        .database-layer {
            background-color: var(--success);
        }
        
        .infrastructure-layer {
            background-color: var(--primary);
        }
        
        .component {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .component-item {
            background-color: rgba(255, 255, 255, 0.3);
            padding: 0.5rem;
            border-radius: 4px;
            flex: 1 1 calc(33.333% - 0.5rem);
            min-width: 100px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
        }
        
        th {
            background-color: var(--primary);
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .risk-high {
            color: var(--danger);
            font-weight: bold;
        }
        
        .risk-medium {
            color: var(--warning);
            font-weight: bold;
        }
        
        .risk-low {
            color: var(--success);
            font-weight: bold;
        }
        
        .metric-card {
            background-color: var(--light);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .metric-title {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .metric-title i {
            color: var(--secondary);
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .metric-title h4 {
            margin: 0;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .metric-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .metric-name {
            font-weight: 500;
        }
        
        .metric-value {
            color: var(--secondary);
            font-weight: bold;
        }
        
        footer {
            background-color: var(--primary);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-info h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: white;
        }
        
        .footer-info p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .footer-logo {
            height: 60px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .header-title {
                padding-left: 0;
                margin-top: 1rem;
            }
            
            .timeline::after {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-item:nth-child(even) {
                left: 0;
            }
            
            .timeline-item::after {
                left: 18px;
            }
            
            .timeline-item:nth-child(even)::after {
                left: 18px;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-logo {
                margin-top: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <img src="img/logo.png" alt="Universidad Distrital Logo" class="university-logo">
                <div class="header-title">
                    <h1>Plan Estratégico de Modernización Tecnológica</h1>
                    <p>Migración y Optimización del Sistema SGPOE</p>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <section id="resumen">
            <h2><i class="fas fa-file-alt"></i> Resumen Ejecutivo</h2>
            <p>El presente documento establece la hoja de ruta para la migración y modernización del actual sistema SIEXUD (Sistema de Información de Extensión de la Universidad Distrital) hacia un nuevo sistema denominado SGPOE (Sistema de Gestión de Proyectos y Oficina de Extensión).</p>
            <p>Este plan aborda la transición gradual desde una arquitectura fragmentada hacia una solución integrada que optimice la gestión de proyectos, mejore la experiencia de usuario, y siente las bases para futuras implementaciones de inteligencia artificial. El enfoque propuesto garantiza la continuidad operativa durante la transición mediante un sistema puente que interactúa con la base de datos Oracle existente.</p>
        </section>
        
        <section id="antecedentes">
            <h2><i class="fas fa-history"></i> Antecedentes</h2>
            <p>El sistema SIEXUD, actualmente en producción, presenta las siguientes limitaciones:</p>
            <ul>
                <li>Arquitectura fragmentada con tres servidores separados:
                    <ul>
                        <li>Aplicativo principal</li>
                        <li>Almacenamiento de archivos</li>
                        <li>Base de datos Oracle</li>
                    </ul>
                </li>
                <li>Código legacy con limitada documentación y alto acoplamiento</li>
                <li>Problemas de consistencia en los datos y esquema de base de datos suboptimizado</li>
                <li>Interfaz de usuario desactualizada y experiencia de usuario limitada</li>
                <li>Ausencia de funcionalidades modernas de seguimiento y análisis de proyectos</li>
            </ul>
            <p>Se ha desarrollado un sistema puente en PHP que permite la interacción con la base de datos Oracle existente, facilitando la transición gradual al nuevo sistema SGPOE.</p>
        </section>
        
        <section id="objetivos">
            <h2><i class="fas fa-bullseye"></i> Objetivos del Proyecto</h2>
            <h3>Objetivo General</h3>
            <p>Desarrollar e implementar un sistema moderno, escalable y eficiente para la gestión integral de proyectos de extensión universitaria, que reemplace gradualmente al sistema SIEXUD mientras mantiene la continuidad operativa.</p>
            
            <h3>Objetivos Específicos</h3>
            <ol>
                <li>Diseñar e implementar una arquitectura de software moderna, modular y escalable</li>
                <li>Depurar y optimizar la estructura de datos existente en la base Oracle</li>
                <li>Desarrollar una interfaz de usuario intuitiva, responsiva y accesible</li>
                <li>Integrar gradualmente todas las funcionalidades actuales del SIEXUD</li>
                <li>Implementar mejoras significativas en gestión, seguimiento y visualización de proyectos</li>
                <li>Establecer las bases tecnológicas para futuras implementaciones de IA</li>
                <li>Garantizar la continuidad operativa durante todo el proceso de migración</li>
                <li>Documentar exhaustivamente el nuevo sistema y sus procesos</li>
            </ol>
        </section>
        
        <section id="alcance">
            <h2><i class="fas fa-project-diagram"></i> Alcance del Proyecto</h2>
            <p>El proyecto SGPOE abarca:</p>
            
            <div class="card-container">
                <div class="card">
                    <div class="card-header">Análisis y Diseño</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <ul>
                            <li>Mapeo completo de funcionalidades existentes</li>
                            <li>Análisis de la estructura de datos actual</li>
                            <li>Diseño de nueva arquitectura</li>
                            <li>Esquema de base de datos optimizado</li>
                            <li>Especificación de interfaces y experiencia de usuario</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Desarrollo e Implementación</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-code"></i>
                        </div>
                        <ul>
                            <li>Módulo de autenticación y gestión de usuarios</li>
                            <li>Gestión integral de proyectos</li>
                            <li>Administración de entidades y colaboradores</li>
                            <li>Gestión documental</li>
                            <li>Módulo de consultas naturales y reportes</li>
                            <li>Dashboard analítico con visualizaciones</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Migración de Datos</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-database"></i>
                        </div>
                        <ul>
                            <li>Limpieza y normalización de datos existentes</li>
                            <li>Migración progresiva a la nueva estructura</li>
                            <li>Validación de integridad y consistencia</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card-container">
                <div class="card">
                    <div class="card-header">Pruebas y Aseguramiento de Calidad</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-vial"></i>
                        </div>
                        <ul>
                            <li>Pruebas unitarias y de integración</li>
                            <li>Pruebas de usabilidad</li>
                            <li>Pruebas de carga y rendimiento</li>
                            <li>Validación de seguridad</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Despliegue y Transición</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <ul>
                            <li>Implementación gradual por módulos</li>
                            <li>Capacitación a usuarios</li>
                            <li>Puesta en producción con sistema paralelo</li>
                            <li>Transición completa al nuevo sistema</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        
        <section id="estrategia">
            <h2><i class="fas fa-chess"></i> Estrategia de Implementación</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <span class="timeline-duration">2 meses</span>
                        <h3>Fase 1: Preparación y Análisis</h3>
                        <ul>
                            <li>Análisis detallado del sistema existente y documentación</li>
                            <li>Entrevistas con stakeholders y usuarios clave</li>
                            <li>Definición de requisitos funcionales y no funcionales</li>
                            <li>Diseño de arquitectura y esquema de base de datos optimizado</li>
                            <li>Prototipado de interfaz de usuario</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <span class="timeline-duration">4 meses</span>
                        <h3>Fase 2: Desarrollo del Núcleo y Módulos Básicos</h3>
                        <ul>
                            <li>Implementación de la arquitectura base</li>
                            <li>Desarrollo del sistema de autenticación</li>
                            <li>Desarrollo de módulos de gestión de proyectos básicos</li>
                            <li>Integración con la base de datos Oracle existente</li>
                            <li>Pruebas preliminares de integración</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <span class="timeline-duration">3 meses</span>
                        <h3>Fase 3: Desarrollo de Módulos Complementarios</h3>
                        <ul>
                            <li>Implementación de gestión documental</li>
                            <li>Desarrollo de módulo de consultas naturales</li>
                            <li>Dashboard y visualizaciones analíticas</li>
                            <li>Mejoras en experiencia de usuario</li>
                            <li>Pruebas de integración completas</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <span class="timeline-duration">3 meses</span>
                        <h3>Fase 4: Migración de Datos y Transición</h3>
                        <ul>
                            <li>Depuración y normalización de datos</li>
                            <li>Migración progresiva por módulos</li>
                            <li>Capacitación a usuarios</li>
                            <li>Funcionamiento en paralelo (sistema antiguo y nuevo)</li>
                            <li>Ajustes basados en retroalimentación de usuarios</li>
                        </ul>
                    </div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-content">
                        <span class="timeline-duration">2 meses</span>
                        <h3>Fase 5: Refinamiento y Mejoras Avanzadas</h3>
                        <ul>
                            <li>Implementación de funcionalidades avanzadas de seguimiento</li>
                            <li>Optimización de rendimiento y escalabilidad</li>
                            <li>Mejoras de seguridad y protección de datos</li>
                            <li>Preparación para futuras integraciones con IA</li>
                            <li>Documentación técnica y de usuario final</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        
        <section id="arquitectura">
            <h2><i class="fas fa-layer-group"></i> Arquitectura Tecnológica Propuesta</h2>
            
            <div class="architecture-diagram">
                <div class="architecture-layers">
                    <div class="layer frontend-layer">
                        <h3>Frontend</h3>
                        <div class="component">
                            <div class="component-item">HTML5, CSS3, JavaScript (ES6+)</div>
                            <div class="component-item">Framework Bootstrap</div>
                            <div class="component-item">Chart.js para visualizaciones</div>
                            <div class="component-item">AJAX para comunicación asíncrona</div>
                        </div>
                    </div>
                    
                    <div class="layer backend-layer">
                        <h3>Backend</h3>
                        <div class="component">
                            <div class="component-item">PHP 8.x</div>
                            <div class="component-item">Arquitectura MVC</div>
                            <div class="component-item">APIs RESTful</div>
                            <div class="component-item">Servicios independientes</div>
                        </div>
                    </div>
                    
                    <div class="layer database-layer">
                        <h3>Base de Datos</h3>
                        <div class="component">
                            <div class="component-item">Oracle existente (inicial)</div>
                            <div class="component-item">Esquema optimizado</div>
                            <div class="component-item">Posible migración a PostgreSQL</div>
                            <div class="component-item">Capas de abstracción</div>
                        </div>
                    </div>
                    
                    <div class="layer infrastructure-layer">
                        <h3>Infraestructura</h3>
                        <div class="component">
                            <div class="component-item">Consolidación de servidores</div>
                            <div class="component-item">Entornos de desarrollo, pruebas y producción</div>
                            <div class="component-item">Respaldos automatizados</div>
                            <div class="component-item">Monitoreo y alertas</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <section id="inteligencia-artificial">
            <h2><i class="fas fa-brain"></i> Integración con Inteligencia Artificial (Visión a Futuro)</h2>
            <p>Preparación de la plataforma para futuras implementaciones de IA:</p>
            
            <div class="card-container">
                <div class="card">
                    <div class="card-header">Procesamiento de Documentos</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <ul>
                            <li>Extracción automatizada de información de contratos</li>
                            <li>Análisis de documentos para clasificación y categorización</li>
                            <li>Generación asistida de actas y documentos</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Análisis Predictivo de Proyectos</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <ul>
                            <li>Previsión de riesgos en la ejecución de proyectos</li>
                            <li>Estimación de plazos y recursos necesarios</li>
                            <li>Alertas tempranas sobre posibles desviaciones</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card-container">
                <div class="card">
                    <div class="card-header">Asistencia y Automatización</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-robot"></i>
                        </div>
                        <ul>
                            <li>Interfaz conversacional para consultas de proyectos</li>
                            <li>Asistencia en la creación y seguimiento de proyectos</li>
                            <li>Automatización de tareas rutinarias</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Análisis Avanzado de Datos</div>
                    <div class="card-body">
                        <div class="icon-feature">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <ul>
                            <li>Identificación de patrones en proyectos exitosos</li>
                            <li>Optimización de asignación de recursos</li>
                            <li>Generación automática de reportes e informes de gestión</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        
        <section id="gestion-riesgos">
            <h2><i class="fas fa-exclamation-triangle"></i> Gestión de Riesgos</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Riesgo</th>
                        <th>Impacto</th>
                        <th>Probabilidad</th>
                        <th>Estrategia de Mitigación</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Resistencia al cambio por parte de usuarios</td>
                        <td class="risk-high">Alto</td>
                        <td class="risk-medium">Media</td>
                        <td>Involucramiento temprano, capacitación continua, comunicación efectiva</td>
                    </tr>
                    <tr>
                        <td>Incompatibilidad con datos existentes</td>
                        <td class="risk-high">Alto</td>
                        <td class="risk-medium">Media</td>
                        <td>Pruebas exhaustivas de migración, validación continua</td>
                    </tr>
                    <tr>
                        <td>Problemas de integración con el sistema actual</td>
                        <td class="risk-high">Alto</td>
                        <td class="risk-high">Alta</td>
                        <td>Desarrollo incremental, pruebas tempranas de integración, plan de contingencia</td>
                    </tr>
                    <tr>
                        <td>Sobrecostos o retrasos</td>
                        <td class="risk-medium">Medio</td>
                        <td class="risk-medium">Media</td>
                        <td>Planificación detallada, seguimiento riguroso, gestión de expectativas</td>
                    </tr>
                    <tr>
                        <td>Pérdida de funcionalidades críticas</td>
                        <td class="risk-high">Alto</td>
                        <td class="risk-low">Baja</td>
                        <td>Mapeo exhaustivo de funcionalidades, validación con usuarios clave</td>
                    </tr>
                    <tr>
                        <td>Problemas de rendimiento</td>
                        <td class="risk-medium">Medio</td>
                        <td class="risk-medium">Media</td>
                        <td>Pruebas de carga periódicas, monitoreo continuo, optimización progresiva</td>
                    </tr>
                </tbody>
            </table>
        </section>
        
        <section id="recursos">
            <h2><i class="fas fa-users"></i> Recursos Necesarios</h2>
            
            <h3>Equipo Humano</h3>
            <ul>
                <li>1 Gerente de Proyecto</li>
                <li>2 Desarrolladores Senior (PHP, JavaScript, Oracle)</li>
                <li>2 Desarrolladores Junior</li>
                <li>1 Especialista en UX/UI</li>
                <li>1 Especialista en Base de Datos Oracle</li>
                <li>1 Especialista en QA y Testing</li>
                <li>Participación parcial de administradores de sistemas</li>
            </ul>
            
            <h3>Infraestructura</h3>
            <ul>
                <li>Entorno de desarrollo</li>
                <li>Entorno de pruebas (staging)</li>
                <li>Entorno de producción</li>
                <li>Servidor de control de versiones y CI/CD</li>
                <li>Herramientas de monitoreo y análisis</li>
            </ul>
        </section>
        
        <section id="cronograma">
            <h2><i class="fas fa-calendar-alt"></i> Cronograma General</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Fase</th>
                        <th>Duración</th>
                        <th>Hitos principales</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Fase 1: Preparación y Análisis</td>
                        <td>2 meses</td>
                        <td>Documento de requisitos aprobado, Arquitectura definida, Prototipos validados</td>
                    </tr>
                    <tr>
                        <td>Fase 2: Desarrollo del Núcleo</td>
                        <td>4 meses</td>
                        <td>Sistema base funcional, Módulos básicos integrados con Oracle, Primera versión para pruebas</td>
                    </tr>
                    <tr>
                        <td>Fase 3: Desarrollo de Módulos Complementarios</td>
                        <td>3 meses</td>
                        <td>Todos los módulos desarrollados, Sistema completo en entorno de pruebas</td>
                    </tr>
                    <tr>
                        <td>Fase 4: Migración y Transición</td>
                        <td>3 meses</td>
                        <td>Datos migrados y validados, Usuarios capacitados, Sistemas funcionando en paralelo</td>
                    </tr>
                    <tr>
                        <td>Fase 5: Refinamiento</td>
                        <td>2 meses</td>
                        <td>Sistema optimizado, Documentación completa, Cierre formal del proyecto</td>
                    </tr>
                </tbody>
            </table>
            
            <p class="mt-3">Duración total estimada: <strong>14 meses</strong></p>
        </section>
        
        <section id="indicadores">
            <h2><i class="fas fa-check-circle"></i> Indicadores de Éxito</h2>
            
            <div class="card-container">
                <div class="metric-card">
                    <div class="metric-title">
                        <i class="fas fa-cogs"></i>
                        <h4>Indicadores Técnicos</h4>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Implementación de funcionalidades existentes:</span>
                        <span class="metric-value">100%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Reducción en tiempo de respuesta:</span>
                        <span class="metric-value">30%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Reducción en incidencias por inconsistencia de datos:</span>
                        <span class="metric-value">50%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Documentación técnica completa y actualizada</span>
                        <span class="metric-value">100%</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">
                        <i class="fas fa-user-check"></i>
                        <h4>Indicadores de Usuario</h4>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Satisfacción de usuario:</span>
                        <span class="metric-value">>85%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Reducción en tiempo para gestión de proyectos:</span>
                        <span class="metric-value">40%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Adopción del nuevo sistema:</span>
                        <span class="metric-value">95%</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-title">
                        <i class="fas fa-building"></i>
                        <h4>Indicadores de Negocio</h4>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Incremento en capacidad de gestión simultánea:</span>
                        <span class="metric-value">25%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Reducción en tiempo administrativo:</span>
                        <span class="metric-value">30%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">Mejora en precisión de reportes y análisis:</span>
                        <span class="metric-value">40%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-name">ROI positivo:</span>
                        <span class="metric-value">1 año</span>
                    </div>
                </div>
            </div>
        </section>
        
        <section id="conclusiones">
            <h2><i class="fas fa-flag-checkered"></i> Conclusiones y Recomendaciones</h2>
            
            <p>El proyecto SGPOE representa una oportunidad significativa para modernizar la gestión de proyectos de extensión en la Universidad Distrital, mejorando la eficiencia operativa y sentando las bases para futuras innovaciones tecnológicas. La estrategia gradual propuesta minimiza los riesgos mientras garantiza la continuidad de las operaciones.</p>
            
            <h3>Recomendaciones</h3>
            <ol>
                <li>Priorizar la participación y capacitación temprana de los usuarios finales</li>
                <li>Establecer puntos de control y validación al final de cada fase</li>
                <li>Documentar exhaustivamente el proceso y las decisiones tomadas</li>
                <li>Planificar desde el inicio la estructura de datos para futuras implementaciones de IA</li>
                <li>Considerar la formación de un comité técnico de seguimiento con representantes de las áreas involucradas</li>
                <li>Implementar un proceso de mejora continua post-implementación</li>
            </ol>
            
            <p>Este plan estratégico proporciona un marco inicial que deberá ser refinado y ajustado conforme avance el proyecto, respondiendo a las necesidades emergentes y oportunidades identificadas durante su ejecución.</p>
        </section>
        
        <section id="nombres-alternativos">
            <h2><i class="fas fa-font"></i> Nombres Alternativos del Sistema</h2>
            
            <p>A continuación se presentan opciones alternativas para el nombre del sistema:</p>
            
            <div class="card-container">
                <div class="card">
                    <div class="card-header">Nombres Basados en Acrónimos</div>
                    <div class="card-body">
                        <ul>
                            <li><strong>GEPRO-UD:</strong> Gestión de Proyectos Universidad Distrital</li>
                            <li><strong>SIGEX:</strong> Sistema Integrado de Gestión de Extensión</li>
                            <li><strong>PROMETEO:</strong> Plataforma Robusta para Organización y Manejo Eficiente de Trabajos de Extensión Organizacional</li>
                            <li><strong>INTEGRA:</strong> Interfaz Nueva de Tecnología para Extensión y Gestión de Recursos Académicos</li>
                            <li><strong>SIGEPO:</strong> Sistema Integrado de Gestión de Proyectos de Oficina</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">Nombres Conceptuales</div>
                    <div class="card-body">
                        <ul>
                            <li><strong>NEXUS-Proyectos:</strong> Núcleo de Excelencia para Unificar Servicios de Proyectos</li>
                            <li><strong>AURORA:</strong> Administración Unificada de Recursos Organizacionales y Registros Académicos</li>
                            <li><strong>PEGASUS:</strong> Plataforma de Extensión y Gestión Avanzada para Servicios Universitarios</li>
                            <li><strong>EXTENSA:</strong> Extensión Tecnológica para Entidades, Nexos y Servicios Académicos</li>
                            <li><strong>PRISMA:</strong> Plataforma de Registro Integrado para Seguimiento y Manejo de Actividades</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <h3>Plan Estratégico de Modernización Tecnológica</h3>
                    <p>Universidad Distrital Francisco José de Caldas</p>
                    <p>Oficina de Extensión Universitaria</p>
                    <p>© 2025 - Todos los derechos reservados</p>
                </div>
                <img src="/api/placeholder/180/60" alt="Universidad Distrital" class="footer-logo">
            </div>
        </div>
    </footer>