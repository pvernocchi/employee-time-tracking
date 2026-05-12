<?php $title = 'Aviso de Protección de Datos'; ?>

<div class="page-header">
    <h1>🔒 Política de Privacidad - Registro de Jornada</h1>
</div>

<div class="card">
    <h2>INFORMACIÓN SOBRE PROTECCIÓN DE DATOS</h2>
    <p><em>Conforme al Reglamento General de Protección de Datos (RGPD) y la Ley Orgánica 3/2018 de Protección de Datos Personales y garantía de los derechos digitales (LOPDGDD)</em></p>

    <h3>1. Responsable del Tratamiento</h3>
    <p>El responsable del tratamiento de sus datos personales es la empresa empleadora, conforme a lo establecido en la normativa laboral vigente.</p>

    <h3>2. Finalidad del Tratamiento</h3>
    <p>Los datos recogidos a través del sistema de registro de jornada tienen como finalidad:</p>
    <ul>
        <li>Cumplimiento de la obligación legal de registro de jornada establecida en el artículo 34.9 del Estatuto de los Trabajadores (Real Decreto-ley 8/2019).</li>
        <li>Control horario y gestión de la relación laboral.</li>
        <li>Cálculo de horas extraordinarias y complementos salariales.</li>
        <li>Puesta a disposición de la Inspección de Trabajo y Seguridad Social cuando sea requerido.</li>
    </ul>

    <h3>3. Base Jurídica</h3>
    <p>El tratamiento de datos se fundamenta en:</p>
    <ul>
        <li><strong>Art. 6.1.c) RGPD:</strong> Cumplimiento de una obligación legal aplicable al responsable (Art. 34.9 ET).</li>
        <li><strong>Art. 6.1.b) RGPD:</strong> Ejecución del contrato de trabajo.</li>
    </ul>

    <h3>4. Datos Tratados</h3>
    <ul>
        <li>Datos identificativos: nombre, apellidos, email corporativo.</li>
        <li>Datos laborales: departamento, horario de entrada/salida, pausas, notas.</li>
        <li>Datos técnicos: dirección IP de registro.</li>
    </ul>

    <h3>5. Conservación de Datos</h3>
    <p>Los registros de jornada se conservarán durante un período de <strong>4 años</strong>, conforme a lo establecido en la normativa laboral y de Seguridad Social para posibles inspecciones.</p>

    <h3>6. Destinatarios</h3>
    <p>Los datos podrán ser comunicados a:</p>
    <ul>
        <li>Inspección de Trabajo y Seguridad Social.</li>
        <li>Representantes legales de los trabajadores (acceso al registro).</li>
        <li>Administraciones Públicas competentes cuando así lo requiera la ley.</li>
    </ul>

    <h3>7. Derechos del Trabajador</h3>
    <p>Conforme al RGPD y la LOPDGDD, usted tiene derecho a:</p>
    <ul>
        <li><strong>Acceso:</strong> Conocer qué datos personales se tratan.</li>
        <li><strong>Rectificación:</strong> Solicitar la corrección de datos inexactos.</li>
        <li><strong>Supresión:</strong> Solicitar la eliminación cuando ya no sean necesarios (sujeto al período de conservación legal).</li>
        <li><strong>Portabilidad:</strong> Recibir sus datos en formato estructurado.</li>
        <li><strong>Limitación:</strong> Solicitar la limitación del tratamiento en determinadas circunstancias.</li>
        <li><strong>Oposición:</strong> Oponerse al tratamiento en determinadas circunstancias.</li>
    </ul>
    <p>Para ejercer estos derechos, puede exportar sus propios registros desde la sección <a href="/timesheet">Timesheet</a>.</p>

    <h3>8. Reclamación</h3>
    <p>Tiene derecho a presentar una reclamación ante la <strong>Agencia Española de Protección de Datos (AEPD)</strong> - <a href="https://www.aepd.es" target="_blank">www.aepd.es</a></p>

    <h3>9. Carácter Obligatorio</h3>
    <p>El registro de jornada es una <strong>obligación legal</strong> del empleador. La participación del trabajador en el sistema de registro es obligatoria conforme al Art. 34.9 del Estatuto de los Trabajadores.</p>
</div>

<div class="card mt-2">
    <h2>Confirmación de Conocimiento</h2>
    <p>Al registrar su consentimiento, confirma que ha sido informado/a sobre el tratamiento de sus datos personales en el sistema de registro de jornada.</p>
    <form method="POST" action="/compliance/consent">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="consent_type" value="time_tracking_privacy_notice">
        <button type="submit" class="btn btn-primary">He leído y comprendo esta política de privacidad</button>
    </form>
</div>
