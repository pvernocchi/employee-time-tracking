<?php
$isEdit = $policy !== null;
$isStatutory = $isEdit && (bool) $policy['is_statutory'];
$title = $isEdit ? 'Editar categoría de permiso' : 'Nueva categoría de permiso';
$formAction = $isEdit
    ? '/admin/settings/leave-policy/' . (int) $policy['id']
    : '/admin/settings/leave-policy';
?>

<div class="page-header">
    <h1><?= $isEdit ? '✏️ Editar categoría de permiso' : '➕ Nueva categoría de permiso' ?></h1>
    <div class="page-actions">
        <a href="/admin/settings/leave-policy" class="btn btn-outline">← Volver</a>
    </div>
</div>

<div class="card">
    <?php if ($isStatutory): ?>
    <div class="alert alert-warning" style="margin-bottom:1.5rem;">
        <strong>⚖️ Categoría del Estatuto de los Trabajadores</strong><br>
        Esta categoría está protegida por ley. Solo es posible aumentar los días legales;
        el nombre y la marca de estatuto no pueden modificarse, y la categoría no puede eliminarse.
        <?php if ((float) $policy['min_statutory_days'] > 0): ?>
            <br><small>Mínimo legal: <?= rtrim(rtrim(number_format((float) $policy['min_statutory_days'], 1), '0'), '.') ?> días.</small>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-group">
            <label for="name">Nombre de la categoría<?= !$isStatutory ? ' *' : '' ?></label>
            <?php if ($isStatutory): ?>
                <input type="text" id="name" value="<?= htmlspecialchars($policy['name']) ?>" disabled>
                <small class="text-muted">El nombre de las categorías estatutarias no puede modificarse.</small>
            <?php else: ?>
                <input type="text" id="name" name="name" required
                       value="<?= htmlspecialchars($policy['name'] ?? '') ?>"
                       placeholder="Ej. Permiso por nacimiento">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="legal_days">Días legales permitidos</label>
            <input type="number" id="legal_days" name="legal_days"
                   min="<?= $isStatutory ? (float) $policy['min_statutory_days'] : '0' ?>"
                   step="0.5"
                   value="<?= $isEdit ? htmlspecialchars((string) (float) $policy['legal_days']) : '0' ?>"
                   placeholder="0">
            <?php if ($isStatutory && (float) $policy['min_statutory_days'] > 0): ?>
                <small class="text-muted">
                    El mínimo legal es <?= rtrim(rtrim(number_format((float) $policy['min_statutory_days'], 1), '0'), '.') ?> días.
                    Puedes aumentarlo, pero no reducirlo.
                </small>
            <?php else: ?>
                <small class="text-muted">Introduce 0 si no hay un número fijo de días.</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="dec_24_31_deduction">Descuento de vacaciones para 24 y 31 de diciembre</label>
            <select id="dec_24_31_deduction" name="dec_24_31_deduction">
                <?php $decemberDeduction = $isEdit ? ($policy['dec_24_31_deduction'] ?? 'full') : 'full'; ?>
                <option value="full" <?= $decemberDeduction === 'full' ? 'selected' : '' ?>>Día completo</option>
                <option value="half" <?= $decemberDeduction === 'half' ? 'selected' : '' ?>>Medio día</option>
            </select>
            <small class="text-muted">
                Se aplica al cálculo de solicitudes de vacaciones en días laborables 24/12 y 31/12.
            </small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <?php if ($isStatutory): ?>
                    <input type="checkbox" checked disabled>
                <?php else: ?>
                    <input type="checkbox" name="is_statutory" id="is_statutory" value="1"
                           <?= ($isEdit && $policy['is_statutory']) ? 'checked' : '' ?>>
                <?php endif; ?>
                Según el Estatuto de los Trabajadores (ET)
            </label>
            <small class="text-muted">
                Marca esta opción si el permiso está regulado por el Estatuto de los Trabajadores.
                Las categorías marcadas no podrán eliminarse y sus días sólo podrán aumentarse.
            </small>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="tracks_balance" id="tracks_balance" value="1"
                       <?= (!$isEdit || $policy['tracks_balance']) ? 'checked' : '' ?>>
                Controla saldo de días restantes
            </label>
            <small class="text-muted">
                Desmarcar si este permiso no tiene un límite de días (p. ej., baja por enfermedad, deber público).
                Las categorías sin saldo mostrarán "No Aplica" en la columna "Restantes".
            </small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Guardar cambios' : 'Crear categoría' ?>
            </button>
            <a href="/admin/settings/leave-policy" class="btn btn-outline">Cancelar</a>
        </div>
    </form>
</div>
