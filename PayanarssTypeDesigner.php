<?php

use Dom\NamedNodeMap;

enum PayanarssTypeDescription: string
{
    case ValueType = "Value Type";
    case LookupType = "Lookup Type";
    case ChildTableType = "Child Table Type";
}

require_once __DIR__ . '/PayanarssTypeModel.php';

$app = null;
$parentId = "";
$payanarssTypes = null;
$attribute = null;

// Load or initialize rows
if (!isset($_SESSION['PayanarssApp'])) {
    $app = new PayanarssApplication();
    $app->load_all_types();
    $_SESSION['PayanarssApp'] = $app;
    $_SESSION['parent_id'] = "";
} else {
    $app = $_SESSION['PayanarssApp'];
    $parentId = $_SESSION['parent_id'];
}

if (isset($_GET['parent_id'])) {
    $_SESSION['parent_id'] = $_GET['parent_id'];
    $parentId = $_GET['parent_id'];
}

if (isset($_POST['add_new_type'])) {
    $newTyp = $app->add_new_type($parentId);
    $_POST['edit_type'] = $newTyp->Id;
    $_SESSION['PayanarssApp'] = $app;
}

$editingId = false;
if (isset($_POST['edit_type'])) {
    $editingId = $_POST['edit_type'];
}

if (isset($_POST['save_type'])) {
    $id = $_POST['save_type'];
    foreach ($app->Types as $type) {
        if ($type->Id === $id) {
            $type->Name = $_POST['type_name'] ?? $type->Name;
            $type->PayanarssTypeId = $_POST['payanarss_type_id'] ?? $type->PayanarssTypeId;
            $type->Type = null;
            $type->Description = $_POST['type_description'] ?? ($type->Description ?? '');

            $description = getPayanarssTypeDescription($type->Id, $app); // to refresh type info
            if ($description === PayanarssTypeDescription::ValueType) {
                $type->Attributes = ["Id" => "100000000000000000000000000000000", "Value" => "True"];
            } else if ($description === PayanarssTypeDescription::LookupType) {
                $type->Attributes = [
                    ["Id" => "100000000000000000000000000000003", "Value" => "True"]
                ];
            } else if ($description === PayanarssTypeDescription::ChildTableType) {
                $type->Attributes = [
                    ["Id" => "100000000000000000000000000000002", "Value" => "True"]
                ];
            }
            break;
        }
    }

    $parentType = $app->get_type($parentId);
    $app->save_all_types($parentType);
    $_SESSION['PayanarssApp'] = $app;
}

if (isset($_POST['delete_table'])) {
    $id = $_POST['delete_table'];
    $parentType = $app->get_type($parentId);
    $parentType->remove_type($id);
    $app->remove_type($id);
    $app->save_all_types();
    $_SESSION['PayanarssApp'] = $app;
}

if (isset($_POST['add_child_types'])) {
    $selectedId = $_POST['add_child_types'];
    // Add your logic to load or show structure for that ID
}

if (isset($_POST['save_attributes'])) {
    $targetId = $_POST['attribute_target_id'] ?? '';
    $selected = $_POST['attributes'] ?? [];
    $fieldType = $app->get_type($targetId);
    $fieldType->Attributes = $selected;
    $app->save_all_types();
    // Save to session or file
    $_SESSION['PayanarssApp'] = $app;
}

if (isset($_POST['attribute_target_id'])) {
    $_SESSION['attribute_target_id'] = $_POST['attribute_target_id'];
}

function getPayanarssTypeDescription($payanarssTypeId, $app): PayanarssTypeDescription
{
    $type = $app->get_type($payanarssTypeId);
    if ($type) {
        if ($type->Id === $type->ParentId) {
            return PayanarssTypeDescription::ValueType; // Root type cannot be a value type
        } else if ($type->Id !== $type->ParentId) {
            $type = $app->get_type($type->PayanarssTypeId);
            if ($type->PayanarssTypeId === "100000000000000000000000000000001") return PayanarssTypeDescription::ChildTableType;
        } else {
            return PayanarssTypeDescription::LookupType;
        }
    }

    return PayanarssTypeDescription::ValueType;
}

$shouldOpenAttributeModal = isset($_POST['load_attributes']);

$targetId = $_SESSION['attribute_target_id'] ?? '';
$parentType = $app->get_type($parentId);
//$payanarssTypes = $app->load_children_v1($parentId);
$payanarssTypes = $app->getChildren($parentId);
$attribute = $app->Attribute;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payanarss Type Designer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .row-enter {
            animation: slideIn 0.3s ease-out;
        }

        /* Hover effects */
        .table-row {
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background-color: #f8fafc;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transform: translateX(2px);
        }

        /* Badge pulse */
        @keyframes badgePulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .badge-pulse {
            animation: badgePulse 2s ease-in-out infinite;
        }

        /* Modal backdrop blur */
        .modal-backdrop {
            backdrop-filter: blur(4px);
        }

        /* Custom scrollbar */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="bg-gray-50">

    <!-- Main Content Area -->
    <main class="p-6 max-w-7xl mx-auto">
        
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-xl p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold"><?= htmlspecialchars($parentType->Name ?? 'Type Designer') ?></h1>
                        <p class="text-blue-100 text-sm mt-1">Design and manage your PayanarssType structure</p>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="flex gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold"><?= count($payanarssTypes) ?></div>
                        <div class="text-blue-100 text-xs">Total Types</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold">
                            <span class="w-3 h-3 bg-green-400 rounded-full inline-block badge-pulse"></span>
                        </div>
                        <div class="text-blue-100 text-xs">Active</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex gap-3">
                <form method="post" class="inline-block">
                    <input type="hidden" name="parent_id" value="<?= htmlspecialchars($parentId) ?>">
                    <button type="submit" name="add_new_type" value="add_new_type" 
                        class="bg-white text-blue-600 px-5 py-2.5 rounded-xl font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add New Type
                    </button>
                </form>
                
                <button type="button" onclick="exportPayanarssJSON()" 
                    class="bg-green-500 text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg hover:shadow-xl hover:bg-green-600 hover:scale-105 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export JSON
                </button>
            </div>
        </div>

        <!-- Types Table -->
        <?php if (count($payanarssTypes) > 0): ?>
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full">
                        <!-- Table Header -->
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        Name
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                        </svg>
                                        Description
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        Type
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                        </svg>
                                        Actions
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($payanarssTypes as $index => $type): ?>
                                <?php $isEditing = isset($_POST['edit_type']) && $_POST['edit_type'] === $type->Id; ?>
                                
                                <tr class="table-row <?= $isEditing ? 'bg-blue-50' : '' ?>">
                                    <form method="post">
                                        <input type="hidden" name="type_id" value="<?= $type->Id ?>">
                                        <input type="hidden" name="parent_id" value="<?= $type->ParentId ?>">

                                        <!-- Name Column -->
                                        <td class="px-6 py-4">
                                            <?php if ($isEditing): ?>
                                                <input type="text" name="type_name" 
                                                    value="<?= htmlspecialchars($type->Name) ?>" 
                                                    class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    placeholder="Enter type name">
                                            <?php else: ?>
                                                <div class="flex items-center gap-3">
                                                    <span class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                                        <?= strtoupper(substr($type->Name, 0, 2)) ?>
                                                    </span>
                                                    <span class="font-semibold text-gray-800">
                                                        <?= htmlspecialchars($type->Name) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Description Column -->
                                        <td class="px-6 py-4">
                                            <?php if ($isEditing): ?>
                                                <textarea name="type_description" rows="2" 
                                                    class="w-full px-3 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                                    placeholder="Enter description"><?= htmlspecialchars($type->Description ?? '') ?></textarea>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-600 line-clamp-2">
                                                    <?= htmlspecialchars($type->Description ?? 'No description') ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Type Column -->
                                        <td class="px-6 py-4">
                                            <?php if ($isEditing): ?>
                                                <div class="space-y-2">
                                                    <input type="hidden" name="payanarss_type_id" id="typ_<?= $type->Id ?>_id" value="<?= $type->PayanarssTypeId ?>">
                                                    <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg border">
                                                        <span class="text-sm font-medium text-gray-700" id="typ_<?= $type->Id ?>_name">
                                                            <?= htmlspecialchars($type->getTypeName($app->Types)) ?>
                                                        </span>
                                                    </div>
                                                    <button type="button" onclick="openTypeSelect('typ_<?= $type->Id ?>')" 
                                                        class="w-full bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors flex items-center justify-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                        </svg>
                                                        Change Type
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    <?= htmlspecialchars($type->getTypeName($app->Types)) ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <button type="button" onclick="submitAttributeForm('<?= $type->Id ?>')"
                                                class="mt-2 w-full bg-yellow-100 text-yellow-700 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-yellow-200 transition-colors flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                Attributes
                                            </button>
                                        </td>

                                        <!-- Actions Column -->
                                        <td class="px-6 py-4">
                                            <?php if ($isEditing): ?>
                                                <div class="flex justify-center gap-2">
                                                    <button type="submit" name="save_type" value="<?= $type->Id ?>"
                                                        class="bg-green-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-600 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Save
                                                    </button>
                                                    <button type="submit" name="cancel_edit"
                                                        class="bg-gray-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-600 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                        Cancel
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex flex-col gap-2">
                                                    <?php if ($type->isTypeOf($app->Types, "GroupType") || $type->isTypeOf($app->Types, "TableType") || $type->isTypeOf($app->Types, "IsChildTable") || $type->isTypeOf($app->Types, "IsLookupType")): ?>
                                                        <a href="index.php?parent_id=<?= urlencode($type->Id) ?>"
                                                            class="bg-purple-100 text-purple-700 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-purple-200 transition-colors text-center flex items-center justify-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                            </svg>
                                                            View Children
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <div class="flex gap-2 justify-center">
                                                        <button type="submit" name="edit_type" value="<?= $type->Id ?>"
                                                            class="bg-blue-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-600 transition-colors flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <button type="submit" name="delete_table" value="<?= $type->Id ?>"
                                                            class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-red-600 transition-colors flex items-center gap-1"
                                                            onclick="return confirm('Are you sure you want to delete this type?')">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Types Found</h3>
                    <p class="text-gray-600 mb-6">Get started by creating your first PayanarssType</p>
                    <form method="post" class="inline-block">
                        <input type="hidden" name="parent_id" value="<?= htmlspecialchars($parentId) ?>">
                        <button type="submit" name="add_new_type" value="add_new_type"
                            class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl hover:bg-blue-700 transition-all flex items-center gap-2 mx-auto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create First Type
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modals remain the same, but with enhanced styling -->
    <!-- Type Select Modal -->
    <div id="typeSelectModal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden justify-center items-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 m-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Select Type
                </h2>
                <button onclick="closeTypeSelect()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="max-h-96 overflow-y-auto custom-scroll border rounded-xl">
                <table class="w-full">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Name</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody id="typeSelectTable" class="divide-y divide-gray-200">
                        <!-- dynamically filled -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Your existing modals and scripts continue here... -->
    <!-- Add the rest of your modals with similar enhanced styling -->

    <script>
        // Add row animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table-row');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.classList.add('row-enter');
                }, index * 50);
            });
        });

        // Your existing JavaScript functions...
    </script>
    <script>
        const payanarssTypes = <?= json_encode($app->Types->all()) ?>;

        let currentInputId = null;

        function openTypeSelect(inputId) {
            currentInputId = inputId;
            const tbody = document.getElementById("typeSelectTable");
            tbody.innerHTML = "";

            payanarssTypes.forEach(type => {
                let displayName = fetch('GetTypeDisplayName.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            payanarssTypes,
                            type
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        const displayName = data.displayName;
                        const row = document.createElement("tr");
                        row.innerHTML = `
        <td class="border px-2 py-1">${displayName}</td>
        <td class="border px-2 py-1 text-center">
          <button type="button" class="text-blue-600 text-xs underline"
            onclick="selectType('${type.Id}', '${type.Name}')">Select</button>
        </td>
      `;
                        tbody.appendChild(row);
                    });
            });

            document.getElementById("typeSelectModal").classList.remove("hidden");
            document.getElementById("typeSelectModal").classList.add("flex");
        }

        function closeTypeSelect() {
            document.getElementById("typeSelectModal").classList.add("hidden");
            document.getElementById("typeSelectModal").classList.remove("flex");
        }

        function selectType(typeId, typeName) {
            if (currentInputId) {
                document.getElementById(currentInputId + "_id").value = typeId;
                document.getElementById(currentInputId + "_name").textContent = typeName;
            }
            closeTypeSelect();
        }

        function loadStructureView(parentId) {
            fetch("PayanarssTypePopupContent.php?parent_id=" + encodeURIComponent(parentId))
                .then(res => res.text())
                .then(html => {
                    document.getElementById("structureContent").innerHTML = html;
                    document.getElementById("structureModal").classList.remove("hidden");
                    document.getElementById("structureModal").classList.add("flex");
                });
        }

        function closeStructureModal() {
            document.getElementById("structureModal").classList.add("hidden");
            document.getElementById("structureModal").classList.remove("flex");
        }
    </script>

    <script>
        function openAttributeModal(columnId) {
            document.getElementById("attributeTargetId").value = columnId;
            document.getElementById("attributeModal").classList.remove("hidden");
            document.getElementById("attributeModal").classList.add("flex");
        }

        function closeAttributeModal() {
            document.getElementById("attributeModal").classList.add("hidden");
        }
    </script>
    <script>
        function submitAttributeForm(typeId) {
            document.getElementById('attributeTargetIdHidden').value = typeId;
            document.getElementById('loadAttributeForm').submit();
        }
    </script>
    <script>
        <?php
        $parentType = $app->get_type($parentId);
        ?>

        // Function to show the modal
        function exportPayanarssJSON() {
            const jsonString = JSON.stringify(<?= isset($parentType->Children) ? json_encode($parentType->Children->all(), JSON_PRETTY_PRINT) : '[]' ?>, null, 2);
            document.getElementById("jsonOutput").textContent = jsonString;
            document.getElementById("jsonModal").classList.remove("hidden");
            document.getElementById("jsonModal").classList.add("flex");
        }

        // Function to close the modal
        function closeJsonModal() {
            document.getElementById("jsonModal").classList.add("hidden");
            document.getElementById("jsonModal").classList.remove("flex");
        }
    </script>
    <?php if ($shouldOpenAttributeModal): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                openAttributeModal('<?= $targetId ?>');
            });
        </script>
    <?php endif; ?>
</body>
</html>