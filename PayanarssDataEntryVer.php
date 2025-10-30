<?php

use Dom\NamedNodeMap;

require_once __DIR__ . '/PayanarssTypeModel.php';

//session_start();

$app = null;
$parentId = null;
$payanarssTypes = null;
$payanarssType = null;
$payanarssTypes = null;
$data = [];

// Load or initialize rows
if (!isset($_SESSION['PayanarssApp'])) {
    $app = new PayanarssApplication();
    $app->load_all_types();
    $_SESSION['PayanarssApp'] = $app;
    $_SESSION['parent_id'] = "";
    $_SESSION['PayanarssData'] = $data;
} else {
    $app = $_SESSION['PayanarssApp'];
    $parentId = $_SESSION['parent_id'];
    $data = isset($_SESSION['PayanarssData']) ? $_SESSION['PayanarssData'] : [];
}

if (isset($parentId)) {
    $payanarssType = $app->get_type($parentId);
    if (isset($payanarssType)) {
        $payanarssTypes = $payanarssType->Children;
    }
}

if (isset($_SESSION['PayanarssData'])) {
    $data = $_SESSION['PayanarssData'];
    if (isset($payanarssType)) {
        $payanarssType->Rows = $data; // ✅ Always re-link to the active type
    }
} else {
    $boj = new PayanarssTypeBusinessLogics();
    if (isset($payanarssType)) {
        $payanarssType->Rows = $boj->read_all_records($parentId);
        $data = &$payanarssType->Rows;
    }
    $_SESSION['PayanarssData'] = $data;
}

if (isset($_GET['parent_id'])) {
    $_SESSION['parent_id'] = $_GET['parent_id'];
    $parentId = $_GET['parent_id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['data'])) {
        $data = $_POST['data']; // Now this is a full array of rows with column IDs
        $payanarssType->Rows = $data;
        $_SESSION['PayanarssData'] = $data;
        echo "Data saved successfully!";
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
                <h2 class="text-lg font-bold"> <?= htmlspecialchars($payanarssType->Name) ?> Data Entry</h2>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button type="button"
                    style="background-color: #4CAF50; color: white; padding: 10px 18px; 
               border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;"
                    onclick="newRecord()">New</button>

                <button type="button"
                    style="background-color: #007BFF; color: white; padding: 10px 18px; 
               border: none; border-radius: 5px; cursor: pointer;"
                    onclick="saveRecord()">Save</button>
            </div>

            <script>
                async function newRecord() {
                    try {

                        // These PHP variables come from your current page
                        const parentId = <?= json_encode($parentId, JSON_PRETTY_PRINT) ?>;

                        // Create payload
                        const payload = {
                            parent_id: parentId,
                        };

                        // Send to prompt_api.php
                        const response = await fetch('prompt_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.text();
                        console.log("Response from server:", result);
                        alert("New record initialized!");

                        // Clear all input controls
                        document.querySelectorAll('input, select').forEach(el => {
                            if (el.type === 'checkbox') el.checked = false;
                            else el.value = '';
                        });

                    } catch (error) {
                        console.error("Error:", error);
                        alert("Failed to create new record!");
                    }
                }

                function saveRecord() {
                    // Gather form data
                    const data = {};
                    document.querySelectorAll('input, select').forEach(el => {
                        if (el.type === 'checkbox') data[el.name] = el.checked;
                        else data[el.name] = el.value;
                    });

                    console.log("Saving Data:", data);
                    alert("Record saved! (Check console for output)");
                    // TODO: send data to PHP backend via fetch()
                }
            </script>

        </form>
        <?php foreach ($parentType->Children as $col): ?>
            <div style="display:flex; align-items:center; margin-bottom:10px;">
                <!-- Label -->
                <label style="width:200px; font-weight:bold;">
                    <?= htmlspecialchars($col->Name) ?>
                    <?php
                    // Check if field is required
                    $isRequired = false;
                    if (!empty($col->Attributes)) {
                        foreach ($col->Attributes as $attr) {
                            if (isset($attr->{'100000000000000000000000000000016'}) && $attr->{'100000000000000000000000000000016'} === "True") {
                                $isRequired = true;
                                break;
                            }
                        }
                    }
                    if ($isRequired) echo " *";
                    ?>
                </label>

                <!-- Input Control -->
                <div style="flex:1;">
                    <?php
                    $value = htmlspecialchars($col->Value ?? '');
                    switch ($col->PayanarssTypeId) {
                        case "100000000000000000000000000000006": // Text
                            echo "
                            <div style='display:flex; align-items:center; margin-bottom:8px; border:1px solid #ccc; padding:8px; border-radius:6px;'>
                                <input type='text' name='{$col->Name}' value='{$value}' style='width:100%; padding:5px;'>
                            </div>
                            ";
                            break;
                        case "100000000000000000000000000000007": // Number
                            echo "
                            <div style='display:flex; align-items:center; margin-bottom:8px; border:1px solid #ccc; padding:8px; border-radius:6px;'>
                                <input type='number' name='{$col->Name}' value='{$value}' style='width:100%; padding:5px;'>
                            </div>
                            ";
                            break;
                        case "100000000000000000000000000000008": // DateTime
                            echo "
                            <div style='display:flex; align-items:center; margin-bottom:8px; border:1px solid #ccc; padding:8px; border-radius:6px;'>
                                <input type='datetime-local' name='{$col->Name}' value='{$value}' style='width:100%; padding:5px;'>
                            </div>
                            ";
                            break;
                        case "100000000000000000000000000000009": // Boolean
                            echo "
                            <div style='display:flex; align-items:center; margin-bottom:8px; border:1px solid #ccc; padding:8px; border-radius:6px;'>
                                <input type='checkbox' name='{$col->Name}' " . ($value === "True" ? "checked" : "") . " style='width:100%; padding:5px;'>
                            </div>
                            ";
                            break;
                        case "100000000000000000000000000000002": // Lookup / dropdown
                            echo "
                            <div style='display:flex; align-items:center; margin-bottom:8px; border:1px solid #ccc; padding:8px; border-radius:6px;'>
                            <select name='{$col->Name}'  style='width:100%; padding:5px;'>
                            <option value=''>--Select--</option>
                            <option value='{$value}' selected>{$value}</option>
                            </select>
                            </div>";
                            break;
                        default:
                            echo "
                            <div style='display:flex; align-items:center; margin-bottom:8px; border:1px solid #ccc; padding:8px; border-radius:6px;'>
                                <input type='text' name='{$col->Name}' value='{$value}' style='width:100%; padding:5px;'>
                            </div>
                            ";
                            break;
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
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