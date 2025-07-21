<?php

use Dom\NamedNodeMap;

require_once 'PayanarssTypeModel.php';

//session_start();

$app = null;
$parentId = null;
$payanarssTypes = null;
$payanarssType = null;
$payanarssTypes = null;
$data = null;

if (!isset($_SESSION['data'])) $_SESSION['data'] = [];
$data = &$_SESSION['data'];

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

if (isset($parentId)) {
    $payanarssType = $app->get_type($parentId);
    if (isset($payanarssType)) {
        $payanarssTypes = $payanarssType->Children;
        $data = &$payanarssType->Rows;
    }
}

if (isset($_GET['parent_id'])) {
    $_SESSION['parent_id'] = $_GET['parent_id'];
    $parentId = $_GET['parent_id'];
    $boj = new PayanarssTypeBusinessLogics();
    $payanarssType->Rows = $boj->read_all_records($parentId);
    $data = &$payanarssType->Rows;
    $_SESSION['data'] = $data;
}

if (!isset($data)) {
    $data = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_row'])) {
        $new = [];
        foreach ($payanarssTypes as $col) {
            $new[$col->Id] = '';
        }
        $data[] = $new;
        $_SESSION['PayanarssApp'] = $app;
    } elseif (isset($_POST['save_row'])) {
        $index = $_POST['save_row'];
        foreach ($payanarssTypes as $col) {
            $id = "";
            $ptype = $app->get_type($col->PayanarssTypeId);
            $isUniqueId = $ptype->Name === "IsUniqueId";
            if ($isUniqueId) {
                $id = $_POST['row'][$col->Id];
            }
            if (!empty($col->Id) && isset($_POST['row'][$col->Id])) {
                $data[$index][$col->Id] = $_POST['row'][$col->Id];
            } else {
                $data[$index][$col->Id] = ''; // or keep existing value
            }
        }
        $bObj = new PayanarssTypeBusinessLogics();
        $bObj->save_data($id, $data, $parentId, $parentId);
    } elseif (isset($_POST['delete_row'])) {
        $index = $_POST['delete_row'];
        array_splice($data, $index, 1);
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="p-4 text-sm">
    <div class="max-w-5xl mx-auto bg-white p-4 shadow rounded">
        <form method="post">
            <div class="flex justify-between mb-2">
                <h2 class="text-lg font-bold">Dynamic Data Entry</h2>
                <button type="submit" name="add_row" class="bg-blue-500 text-white px-3 py-1 rounded">➕ Add Row</button>
            </div>
        </form>
        <table class="w-full border border-gray-300 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <?php if (isset($payanarssTypes)) {
                        foreach ($payanarssTypes as $col): ?>
                            <th class="border px-3 py-2"><?= htmlspecialchars($col->Name) ?></th>
                    <?php endforeach;
                    } ?>
                    <th class="border px-3 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $i => $row): ?>
                    <tr>
                        <form method="post">
                            <?php foreach ($payanarssTypes as $col): ?>
                                <?php
                                $isChildTable = false;
                                $isLookupType = false;

                                $colType = $app->get_type($col->PayanarssTypeId);

                                $childtype = $app->get_type($colType->PayanarssTypeId);
                                $isChildTable = $childtype->Name === "TableType";

                                $colParentTyp = $app->get_type($colType->ParentId);
                                $lookypTyp = $app->get_type($colParentTyp->PayanarssTypeId);
                                $isLookupType = isset($lookypTyp) && $lookypTyp->Name === "TableType";

                                //$ptype = $app->get_type($col->PayanarssTypeId);
                                //$parenttype = $app->get_type($ptype->PayanarssTypeId);
                                if ($isChildTable):
                                ?>
                                    <td class="border px-3 py-1">
                                        <button type="button"
                                            onclick="openChildTableModal('<?= $col->Id ?>')"
                                            class="bg-purple-500 text-white px-3 py-1 rounded text-sm hover:bg-purple-600">
                                            ➕ Enter Data
                                        </button>
                                    </td>
                                <?php elseif ($isLookupType): ?>
                                    <td class="border px-3 py-1">
                                        <button type="button"
                                            onclick="openLookupModal('<?= $type->Id ?>')"
                                            class="bg-indigo-500 text-white px-3 py-1 rounded text-sm hover:bg-indigo-600">
                                            🔍 Lookup
                                        </button>
                                    </td>
                                <?php else: ?>
                                    <td class="border px-3 py-1">
                                        <input
                                            type="<?php
                                                    $isUniqueId = false;
                                                    $ptype = $app->get_type($col->PayanarssTypeId);
                                                    $isUniqueId = $ptype->Name === "IsUniqueId";
                                                    $readonly = '';

                                                    if (!isset($row[$col->Id]) || ($row[$col->Id] === null || $row[$col->Id] === '')) {
                                                        $value = $isUniqueId ? uniqid() : '';
                                                        $row[$col->Id] = $value;
                                                        $readonly = $isUniqueId ? 'readonly' : '';
                                                    }

                                                    $ptype = $app->get_type($col->PayanarssTypeId);
                                                    if ($ptype->Name === 'Number') echo 'number';
                                                    else if ($ptype->Name === 'DateTime') echo 'datetime-local';
                                                    else echo 'text';
                                                    ?>"
                                            name="row[<?= $col->Id ?>]"
                                            value="<?= isset($row[$col->Id]) ? htmlspecialchars($row[$col->Id]) : "" ?>"
                                            <?= $readonly ?>
                                            class="border border-gray-300 px-3 py-1 rounded w-full" />
                                    <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="border px-3 py-1 text-center">
                                    <button type="submit" name="save_row" value="<?= $i ?>" class="text-green-600 hover:underline">💾</button>
                                    <button type="submit" name="delete_row" value="<?= $i ?>" class="text-red-600 hover:underline">🗑️</button>
                                </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="childTableModal" class="fixed inset-0 hidden bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white w-full max-w-2xl p-6 rounded shadow relative">
            <h2 class="text-lg font-bold mb-4">Child Table Data Entry</h2>
            <div id="childTableFormContainer">
                <!-- Content will be loaded here dynamically -->
            </div>
            <div class="mt-4 text-right">
                <button onclick="closeChildTableModal()" class="bg-gray-600 text-white px-4 py-2 rounded">Close</button>
            </div>
        </div>
    </div>

    <div id="lookupModal" class="fixed inset-0 hidden bg-black bg-opacity-50 z-50 justify-center items-center">
        <div class="bg-white w-full max-w-xl p-6 rounded shadow relative">
            <h2 class="text-lg font-bold mb-4">🔍 Lookup Data</h2>
            <div id="lookupModalContent">
                <!-- Lookup data content will be loaded here -->
            </div>
            <div class="mt-4 text-right">
                <button onclick="closeLookupModal()" class="bg-gray-600 text-white px-4 py-2 rounded">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openChildTableModal(typeId) {
            // Load modal content dynamically (replace with real AJAX if needed)
            const html = `
            <form method="post">
                <input type="hidden" name="child_table_type_id" value="${typeId}">
                <table class="w-full border text-sm mb-4">
                    <thead><tr><th class="border px-2">Field</th><th class="border px-2">Value</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="border px-2">Example Field</td>
                            <td class="border px-2"><input type="text" name="child_data[example]" class="border px-2 py-1 w-full"></td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">💾 Save</button>
            </form>
        `;
            document.getElementById('childTableFormContainer').innerHTML = html;
            document.getElementById('childTableModal').classList.remove('hidden');
            document.getElementById('childTableModal').classList.add('flex');
        }

        function closeChildTableModal() {
            document.getElementById('childTableModal').classList.add('hidden');
        }
    </script>

    <script>
        function openLookupModal(typeId) {
            // Example static data; you can replace with AJAX
            const html = `
            <table class="w-full border text-sm mb-4">
                <thead><tr><th class="border px-2">Option</th><th class="border px-2">Value</th></tr></thead>
                <tbody>
                    <tr><td class="border px-2">Option 1</td><td class="border px-2"><button>Select</button></td></tr>
                    <tr><td class="border px-2">Option 2</td><td class="border px-2"><button>Select</button></td></tr>
                </tbody>
            </table>
        `;
            document.getElementById('lookupModalContent').innerHTML = html;
            document.getElementById('lookupModal').classList.remove('hidden');
            document.getElementById('lookupModal').classList.add('flex');
        }

        function closeLookupModal() {
            document.getElementById('lookupModal').classList.add('hidden');
        }
    </script>
</body>

</html>