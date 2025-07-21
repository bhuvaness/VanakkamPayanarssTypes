<?php
function isInPath($node, $selectedId): bool
{
    if ($node->Id === $selectedId) return true;
    foreach ($node->Children as $child) {
        if (isInPath($child, $selectedId)) return true;
    }
    return false;
}

function renderTree($types, $selectedId)
{
    foreach ($types as $type) {
        $shouldExpand = isInPath($type, $selectedId);
?>

        <li class="ml-2">
            <div class="flex items-center gap-1">
                <?php if (count($type->Children) > 0): ?>
                    <button type="button"
                        onclick="document.getElementById('node_<?= $type->Id ?>').classList.toggle('hidden')"
                        class="text-xs w-5 h-5 flex justify-center items-center rotate-90 transition-transform">
                        ▶
                    </button>
                <?php else: ?>
                    <span class="inline-block w-5 h-5"></span>
                <?php endif; ?>

                <a href="?parent_id=<?= $type->Id ?>" class="text-sm <?= $type->Id === $selectedId ? 'text-blue-700 font-bold underline' : 'text-gray-800' ?>">
                    <?= htmlspecialchars($type->Name) ?>
                </a>
            </div>

            <?php if (count($type->Children) > 0): ?>
                <ul id="node_<?= $type->Id ?>" class="ml-4 <?= $shouldExpand ? '' : 'hidden' ?>">
                    <?php renderTree($type->Children, $selectedId); ?>
                </ul>
            <?php endif; ?>
        </li>

<?php
    }
}
?>