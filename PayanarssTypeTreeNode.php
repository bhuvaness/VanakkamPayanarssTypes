<?php

function isInPath($node, $selectedId): bool
{
    if ($node->Id === $selectedId) return true;
    foreach ($node->Children as $child) {
        if (isInPath($child, $selectedId)) return true;
    }
    return false;
}

function getNodeIcon($type): string
{
    // Return appropriate icon based on type
    $icons = [
        '100000000000000000000000000000000' => '🔢',
        '100000000000000000000000000000001' => '📋',
        '100000000000000000000000000000002' => '📊',
        '100000000000000000000000000000003' => '🔍',
        '100000000000000000000000000000004' => '📁',
        '100000000000000000000000000000005' => '⚙️',
    ];
    
    // Match by PayanarssTypeId or Name
    foreach ($icons as $key => $icon) {
        if (strpos($type->PayanarssTypeId, $key) !== false) return $icon;
    }
    
    return '📄'; // Default icon
}

function getNodeColor($type): string
{
    // Return color class based on type
    $colors = [
        'Agent' => 'text-blue-600',
        'Table' => 'text-green-600',
        'Group' => 'text-purple-600',
        'Type' => 'text-orange-600',
    ];
    
    foreach ($colors as $key => $color) {
        if (stripos($type->Name, $key) !== false) return $color;
    }
    
    return 'text-gray-700'; // Default color
}

function renderTree($types, $selectedId, $level = 0)
{
    foreach ($types as $type) {
        $type->get_children($GLOBALS['app'], $type->Id);
        if($type->Id === $type->PayanarssTypeId) continue; // Skip root types
        
        $shouldExpand = isInPath($type, $selectedId);
        $isSelected = $type->Id === $selectedId;
        $hasChildren = count($type->Children) > 0;
        $icon = getNodeIcon($type);
        $color = getNodeColor($type);
        $nodeId = "node_" . str_replace('-', '_', $type->Id);
?>
        <li class="relative group">
            <div class="flex items-center gap-2 py-1.5 px-2 rounded-lg transition-all duration-200 hover:bg-gray-50 <?= $isSelected ? 'bg-blue-50 border-l-4 border-blue-500' : '' ?>">
                
                <!-- Expand/Collapse Button -->
                <?php if ($hasChildren): ?>
                    <button type="button"
                        onclick="toggleNode('<?= $nodeId ?>')"
                        id="btn_<?= $nodeId ?>"
                        class="toggle-btn flex-shrink-0 w-6 h-6 flex items-center justify-center rounded hover:bg-gray-200 transition-all duration-200 <?= $shouldExpand ? 'rotate-90' : '' ?>">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                <?php else: ?>
                    <span class="flex-shrink-0 w-6 h-6"></span>
                <?php endif; ?>

                <!-- Node Icon -->
                <span class="text-lg flex-shrink-0"><?= $icon ?></span>

                <!-- Node Link -->
                <a href="?parent_id=<?= $type->Id ?>" 
                   class="flex-1 text-sm font-medium <?= $color ?> hover:underline decoration-2 <?= $isSelected ? 'font-bold' : '' ?> truncate"
                   title="<?= htmlspecialchars($type->Name) ?>">
                    <?= htmlspecialchars($type->Name) ?>
                </a>

                <!-- Badge for children count -->
                <?php if ($hasChildren): ?>
                    <span class="flex-shrink-0 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                        <?= count($type->Children) ?>
                    </span>
                <?php endif; ?>

                <!-- Hover Actions -->
                <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex gap-1">
                    <button type="button" 
                            onclick="alert('Edit: <?= addslashes($type->Name) ?>')"
                            class="w-6 h-6 flex items-center justify-center rounded hover:bg-blue-100 text-blue-600"
                            title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button type="button" 
                            onclick="alert('Delete: <?= addslashes($type->Name) ?>')"
                            class="w-6 h-6 flex items-center justify-center rounded hover:bg-red-100 text-red-600"
                            title="Delete">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Children -->
            <?php if ($hasChildren): ?>
                <ul id="<?= $nodeId ?>" 
                    class="ml-8 border-l-2 border-gray-200 pl-2 mt-1 space-y-1 transition-all duration-300 <?= $shouldExpand ? '' : 'hidden' ?>"
                    style="<?= $shouldExpand ? '' : 'max-height: 0; overflow: hidden;' ?>">
                    <?php renderTree($type->Children, $selectedId, $level + 1); ?>
                </ul>
            <?php endif; ?>
        </li>
<?php
    }
}
?>

<!-- Add this JavaScript for smooth toggle animation -->
<script>
function toggleNode(nodeId) {
    const node = document.getElementById(nodeId);
    const btn = document.getElementById('btn_' + nodeId);
    
    if (node.classList.contains('hidden')) {
        // Expand
        node.classList.remove('hidden');
        node.style.maxHeight = node.scrollHeight + 'px';
        btn.classList.add('rotate-90');
    } else {
        // Collapse
        node.style.maxHeight = '0';
        btn.classList.remove('rotate-90');
        setTimeout(() => {
            node.classList.add('hidden');
        }, 300);
    }
}

// Initialize expanded nodes with proper height
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('ul[id^="node_"]:not(.hidden)').forEach(node => {
        node.style.maxHeight = node.scrollHeight + 'px';
    });
});
</script>

<!-- Enhanced Styles -->
<style>
/* Smooth animations */
.toggle-btn {
    transition: transform 0.2s ease;
}

ul[id^="node_"] {
    transition: max-height 0.3s ease-in-out;
}

/* Custom scrollbar for tree view container */
.tree-container::-webkit-scrollbar {
    width: 8px;
}

.tree-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.tree-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

.tree-container::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Hover effect for tree items */
li.group > div:hover {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Selected item animation */
@keyframes selectedPulse {
    0%, 100% { background-color: rgba(219, 234, 254, 0.5); }
    50% { background-color: rgba(219, 234, 254, 0.8); }
}

.bg-blue-50 {
    animation: selectedPulse 2s ease-in-out;
}
</style>
