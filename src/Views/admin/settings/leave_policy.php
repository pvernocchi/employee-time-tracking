<?php $title = 'Política de permisos global'; ?>

<div class="page-header">
    <h1>📋 Política de permisos global</h1>
    <div class="page-actions">
        <a href="/admin/settings/leave-policy/create" class="btn btn-primary">+ Nueva categoría</a>
        <a href="/admin/settings/smtp" class="btn btn-outline">← Configuración</a>
    </div>
</div>

<div class="card">
    <p class="text-muted">
        Gestiona las categorías de permisos y los días legales permitidos.
        Las categorías marcadas con <strong>⚖️ ET</strong> pertenecen al Estatuto de los Trabajadores;
        sus días sólo pueden aumentarse y no pueden eliminarse.
    </p>

    <?php if (empty($policies)): ?>
        <p class="text-muted">No hay categorías de permisos definidas.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Categoría</th>
                <th>Días legales</th>
                <th>Estatuto de los Trabajadores</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($policies as $policy): ?>
            <tr>
                <td><?= htmlspecialchars($policy['name']) ?></td>
                <td>
                    <?php
                    $days = (float) $policy['legal_days'];
                    echo $days > 0 ? rtrim(rtrim(number_format($days, 1), '0'), '.') : '—';
                    ?>
                </td>
                <td>
                    <?php if ($policy['is_statutory']): ?>
                        <span class="badge badge-active" title="Según el Estatuto de los Trabajadores">⚖️ Sí</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">No</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/admin/settings/leave-policy/<?= (int) $policy['id'] ?>/edit" class="btn btn-sm btn-outline">Editar</a>
                    <?php if (!$policy['is_statutory']): ?>
                    <form method="POST" action="/admin/settings/leave-policy/<?= (int) $policy['id'] ?>/delete"
                          style="display:inline-block;"
                          onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
