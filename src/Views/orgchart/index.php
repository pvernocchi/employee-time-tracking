<?php $title = $t('orgchart.title'); ?>

<div class="page-header">
    <h1>🏢 <?= htmlspecialchars($t('orgchart.heading')) ?></h1>
    <div class="page-actions">
        <?php if ($isAdmin): ?>
            <a href="/admin/employees/edit/<?= (int) $focusedUserId ?>" class="btn btn-outline">← <?= htmlspecialchars($t('orgchart.back_to_edit')) ?></a>
        <?php else: ?>
            <a href="/dashboard" class="btn btn-outline">← <?= htmlspecialchars($t('orgchart.back_to_dashboard')) ?></a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <p class="text-muted"><?= htmlspecialchars($t('orgchart.description', ['name' => $focusedUserName])) ?></p>

    <div class="orgchart-container" id="orgchart-root">
        <?php if ($tree): ?>
            <?php
            /**
             * Recursively render a node and all its children.
             *
             * @param array $node Tree node with keys: id, first_name, last_name, role, department, focused, children
             */
            function renderOrgNode(array $node): string
            {
                $isFocused = !empty($node['focused']);
                $cardClass = 'orgchart-card' . ($isFocused ? ' orgchart-card--focused' : '');

                $roleBadgeClass = 'badge badge-role-' . htmlspecialchars($node['role']);
                $deptHtml = !empty($node['department'])
                    ? '<span class="orgchart-dept">' . htmlspecialchars($node['department']) . '</span>'
                    : '';

                $html  = '<div class="orgchart-item">';
                $html .= '<div class="' . $cardClass . '">';
                $html .= '<span class="orgchart-name">' . htmlspecialchars($node['first_name'] . ' ' . $node['last_name']) . '</span>';
                $html .= '<span class="' . $roleBadgeClass . '">' . htmlspecialchars(ucfirst($node['role'])) . '</span>';
                $html .= $deptHtml;
                $html .= '</div>'; // .orgchart-card

                if (!empty($node['children'])) {
                    $html .= '<div class="orgchart-level">';
                    foreach ($node['children'] as $child) {
                        $html .= renderOrgNode($child);
                    }
                    $html .= '</div>'; // .orgchart-level
                }

                $html .= '</div>'; // .orgchart-item
                return $html;
            }
            ?>
            <?= renderOrgNode($tree) ?>
        <?php else: ?>
            <p class="text-muted text-center"><?= htmlspecialchars($t('orgchart.no_data')) ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    'use strict';

    function drawOrgChartLines() {
        const root = document.getElementById('orgchart-root');
        if (!root) return;

        // Remove any previous SVG overlay
        const oldSvg = root.querySelector('svg.orgchart-lines');
        if (oldSvg) oldSvg.remove();

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.classList.add('orgchart-lines');
        root.appendChild(svg);

        const containerRect = root.getBoundingClientRect();
        const scrollLeft = root.scrollLeft;
        const scrollTop  = root.scrollTop;

        function getPos(el) {
            const r = el.getBoundingClientRect();
            return {
                x:      r.left - containerRect.left + scrollLeft + r.width / 2,
                top:    r.top  - containerRect.top  + scrollTop,
                bottom: r.top  - containerRect.top  + scrollTop + r.height,
            };
        }

        // For each orgchart-item, connect its card to the cards of its direct children
        root.querySelectorAll('.orgchart-item').forEach(function (item) {
            const parentCard = item.querySelector(':scope > .orgchart-card');
            const childLevel = item.querySelector(':scope > .orgchart-level');
            if (!parentCard || !childLevel) return;

            const childCards = Array.from(
                childLevel.querySelectorAll(':scope > .orgchart-item > .orgchart-card')
            );
            if (!childCards.length) return;

            const parentPos = getPos(parentCard);

            childCards.forEach(function (childCard) {
                const childPos = getPos(childCard);
                const midY = (parentPos.bottom + childPos.top) / 2;

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute(
                    'd',
                    'M ' + parentPos.x + ' ' + parentPos.bottom +
                    ' L ' + parentPos.x + ' ' + midY +
                    ' L ' + childPos.x  + ' ' + midY +
                    ' L ' + childPos.x  + ' ' + childPos.top
                );
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', 'var(--gray-300)');
                path.setAttribute('stroke-width', '2');
                svg.appendChild(path);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', drawOrgChartLines);
    window.addEventListener('resize', drawOrgChartLines);
}());
</script>
