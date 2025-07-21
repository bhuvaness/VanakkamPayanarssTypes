<?php

use Dom\NamedNodeMap;

require_once 'PayanarssTypeModel.php';

//session_start();

$app = null;
$parentId = null;
$payanarssTypes = null;

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

$payanarssType = $app->get_type($parentId);
$payanarssTypes = $payanarssType->Children;

// Handle form submission and save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = $_POST['data'];
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents(__DIR__ . "/datas/{$payanarssType->Id}.json", $json);
    $success = true;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-sm">
    <div class="max-w-3xl mx-auto mt-10 p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Enter Payanarss Data</h2>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                ✅ Data saved successfully!
            </div>
        <?php endif; ?>

        <form method="post">
            <?php
            foreach ($payanarssTypes as $type): ?>
                <div class="mb-3">
                    <label class="block font-medium mb-1">
                        <?= htmlspecialchars($type->Name) ?>
                    </label>
                    <input
                        type="<?php
                                $isUniqueId = false;
                                
                                if (isset($type->Attributes)) {
                                    foreach ($type->Attributes as $attr) {
                                        $attrType = $app->get_type($attr);
                                        if ($attrType->Name === "IsUniqueId") {
                                            $isUniqueId = true;
                                            break;
                                        }
                                    }
                                }
                                //$isUniqueId = in_array('IsUniqueId', $type->Attributes ?? []);
                                $value = $isUniqueId ? uniqid() : '';
                                $readonly = $isUniqueId ? 'readonly' : '';

                                $ptype = $app->get_type($type->PayanarssTypeId);
                                if ($ptype->Name === 'Number') echo 'number';
                                else if ($ptype->Name === 'DateTime') echo 'datetime-local';
                                else echo 'text';
                                ?>"
                        name="data[<?= htmlspecialchars($type->Id) ?>]"
                        value="<?= htmlspecialchars($value) ?>"
                        <?= $readonly ?>
                        class="border border-gray-300 px-3 py-1 rounded w-full" />
                </div>
            <?php endforeach; ?>

            <div class="mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    💾 Save Data
                </button>
            </div>
        </form>
    </div>
</body>

</html>