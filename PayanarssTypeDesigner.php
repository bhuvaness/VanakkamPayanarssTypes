<?php

use Dom\NamedNodeMap;

require_once 'PayanarssTypeModel.php';

//session_start();

$app = null;
$parentId = "";
$payanarssTypes = null;
$attribute = null;

// Load or initialize rows
if (!isset($_SESSION['PayanarssApp'])) {
    $app = new PayanarssTypeApplication();
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
            break;
        }
    }

    $app->save_all_types();
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
    <title>Payanarss Type Designer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-screen bg-gray-50">
    <div class="flex h-[calc(100%-64px)]"> <!-- Adjust height minus header -->
        <!-- 📄 Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6 bg-white">
            <div>
                <table class="w-full border border-gray-300 text-sm mb-6">
                    <!-- Table title and add row button -->
                    <header>
                        <tr>
                            <th class="bg-gray-100 border px-4 py-2 font-semibold text-left" colspan="5">Payanarss Type Designs</th>
                        </tr>
                        <tr>
                            <th class="bg-gray-100 border px-4 py-2 font-semibold text-left" colspan="5">
                                <form method="post">
                                    <input type="hidden" name="parent_id" value="<?= htmlspecialchars($parentId) ?>">
                                    <button type='submit' name='add_new_type' value='add_new_type' class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Add Type</button>
                                </form>
                            </th>
                        </tr>
                        <tr>
                            <th class="bg-gray-100 border px-4 py-2 font-semibold text-left">Name</th>
                            <th class="bg-gray-100 border px-4 py-2 font-semibold text-left">Type</th>
                            <th class="bg-gray-100 border px-4 py-2 font-semibold text-left">Actions</th>
                        </tr>
                    </header>
                    <tbody>
                        <?php if (count($payanarssTypes) > 0) {
                            foreach ($payanarssTypes as $type): ?>
                                
                                <?php $isEditing = isset($_POST['edit_type']) && $_POST['edit_type'] === $type->Id; ?>
                                <tr>
                                    <form method="post">
                                        <input type="hidden" name="type_id" value="<?= $type->Id ?>">
                                        <input type="hidden" name="parent_id" value="<?= $type->ParentId ?>">

                                        <td class="border px-3 py-2">
                                            <?php if ($isEditing): ?>
                                                <input type="text" name="type_name" value="<?= htmlspecialchars($type->Name) ?>" class="border px-2 py-1 w-full">
                                            <?php else: ?>
                                                <?= htmlspecialchars($type->Name) ?>
                                            <?php endif; ?>
                                        </td>

                                        <td class="border px-3 py-2">
                                            <?php if ($isEditing): ?>
                                                <!-- Inside an edit row -->
                                                <input type="hidden" name="payanarss_type_id" id="typ_<?= $type->Id ?>_id" value="<?= $type->PayanarssTypeId ?>">
                                                <span id="typ_<?= $type->Id ?>_name"><?= htmlspecialchars($type->getTypeName($app->Types)) ?></span>
                                                <button type="button" onclick="openTypeSelect('typ_<?= $type->Id ?>')" class="ml-2 bg-blue-500 text-white px-2 py-1 rounded text-xs">Select Type</button>
                                            <?php else: ?>
                                                <?= htmlspecialchars($type->getTypeName($app->Types)) ?>
                                            <?php endif; ?>
                                            <button type="button" onclick="submitAttributeForm('<?= $type->Id ?>')"
                                                class="ml-2 bg-yellow-500 text-white px-2 py-1 rounded text-xs hover:bg-yellow-600">
                                                ⚙️ Set Attributes
                                            </button>
                                        </td>

                                        <td class="border px-3 py-2 text-center" colspan="2">
                                            <?php if ($isEditing): ?>
                                                <button type="submit" name="save_type" value="<?= $type->Id ?>"
                                                    class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">Save</button>
                                                <button type="submit" name="cancel_edit"
                                                    class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">Cancel</button>
                                            <?php else: ?>
                                                <?php if ($type->isTypeOf($app->Types, "GroupType") || $type->isTypeOf($app->Types, "TableType") || $type->isTypeOf($app->Types, "IsChildTable") || $type->isTypeOf($app->Types, "IsLookupType")): ?>
                                                    <a href="index.php?parent_id=<?= urlencode($type->Id) ?>"
                                                        class="ml-2 text-xs text-purple-600 hover:underline">
                                                        ➕ Child Types
                                                    </a>
                                                    <button name="add_child_types" onclick="event.preventDefault(); loadStructureView('<?= $type->Id ?>')" value="<?= $type->Id ?>"
                                                        class="bg-purple-500 text-white px-2 py-1 text-xs rounded ml-1">Add Child Types</button>
                                                <?php endif; ?>
                                                <button type="submit" name="edit_type" value="<?= $type->Id ?>"
                                                    class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">Edit</button>
                                                <button type="submit" name="delete_table" value="<?= $type->Id ?>"
                                                    class="bg-red-500 text-white px-2 py-1 text-xs rounded"
                                                    onclick="return confirm('Are you sure you want to delete this table?')">Delete</button>
                                            <?php endif; ?>
                                        </td>
                                    </form>
                                </tr>
                        <?php endforeach;
                        } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Select Type Modal -->
    <div id="typeSelectModal" class="fixed inset-0 bg-black bg-opacity-40 hidden justify-center items-center z-50">
        <div class="bg-white rounded shadow-lg w-full max-w-md p-4">
            <h2 class="text-sm font-semibold mb-2">📘 Select Type</h2>
            <div class="max-h-64 overflow-y-auto text-sm border rounded">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Id</th>
                            <th class="border px-2 py-1">Name</th>
                            <th class="border px-2 py-1">Select</th>
                        </tr>
                    </thead>
                    <tbody id="typeSelectTable">
                        <!-- dynamically filled -->
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-right">
                <button onclick="closeTypeSelect()" class="text-xs bg-red-500 text-white px-3 py-1 rounded">Close</button>
            </div>
        </div>
    </div>

    <!-- Structure Modal -->
    <div id="structureModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50">
        <div class="bg-white rounded p-4 shadow max-w-2xl w-full h-[80vh] overflow-y-auto">
            <h2 class="text-base font-semibold mb-2">📘 Child Payanarss Types</h2>
            <div id="structureContent" class="text-xs text-gray-800"></div>
            <div class="text-right mt-3">
                <button onclick="closeStructureModal()" class="bg-red-500 text-white px-3 py-1 rounded text-xs">Close</button>
            </div>
        </div>
    </div>

    <!-- Attribute Modal -->
    <div id="attributeModal" class="fixed inset-0 hidden bg-black bg-opacity-40 z-50 justify-center items-center">
        <div class="bg-white w-full max-w-md rounded shadow-lg p-6 relative">
            <h2 class="text-lg font-semibold mb-4">🧩 Select Attributes</h2>
            <form method="post">
                <input type="hidden" name="attribute_target_id" id="attributeTargetId" value="<?= htmlspecialchars($targetId) ?>">
                <div id="attributeList" class="space-y-2 text-sm">
                    <?php
                    $selectedType = $app->get_type($targetId);
                    if (isset($attribute)) {
                        // Assuming $attributeTypes is your list of all types and you filtered 'Attribute' children
                        foreach ($attribute->Children as $attr) {
                            $isSelected = false;

                            if (isset($selectedType->Attributes)) {
                                foreach ($selectedType->Attributes as $selAttr) {
                                    if ($attr->Id == $selAttr) {
                                        $isSelected = true;
                                        break;
                                    }
                                }
                            }

                            //echo $isSelected;
                            $isChecked = $isSelected ? 'checked' : '';
                            echo "<label class='flex items-center space-x-2'>";
                            echo "<input type='checkbox' name='attributes[]' value='{$attr->Id}' class='accent-blue-500' $isChecked>";
                            echo "<span>{$attr->Name}</span>";
                            echo "</label>";
                        }
                    }
                    ?>
                </div>
                <div class="mt-4 text-right space-x-2">
                    <button type="button" onclick="closeAttributeModal()" class="px-3 py-1 bg-gray-400 text-white rounded">Cancel</button>
                    <button type="submit" name="save_attributes" class="px-3 py-1 bg-green-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
    <form method="post" id="loadAttributeForm" style="display:none;">
        <input type="hidden" name="attribute_target_id" id="attributeTargetIdHidden">
        <input type="hidden" name="load_attributes" value="1">
    </form>
    <script>
        const payanarssTypes = <?= json_encode($app->Types->all()) ?>;

        let currentInputId = null;

        function openTypeSelect(inputId) {
            currentInputId = inputId;
            const tbody = document.getElementById("typeSelectTable");
            tbody.innerHTML = "";

            payanarssTypes.forEach(type => {
                let displayName = fetch('getTypeDisplayName.php', {
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
        <td class="border px-2 py-1">${type.Id}</td>
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

    <?php if ($shouldOpenAttributeModal): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                openAttributeModal('<?= $targetId ?>');
            });
        </script>
    <?php endif; ?>
</body>

</html>