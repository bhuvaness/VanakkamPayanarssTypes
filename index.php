<?php

use Dom\NamedNodeMap;

require_once 'PayanarssTypeModel.php';

session_start();

$app = null;
$parentId = "";
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

$payanarssTypes = $app->load_children_v1($parentId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_from_prompt'])) {
    $prompt = $_POST['ai_prompt'] ?? '';

    if (!empty($prompt)) {
        require_once 'OpenAIHelper.php'; // your helper file

        $response = $app->prompt_for_type($prompt); // this function should return JSON (see below)
        //echo "<pre>AI Response: " . htmlspecialchars($response) . "</pre>";
        /*
        if (preg_match('/json(.*?)/s', $response, $matches)) {
            $jsonString = trim($matches[1]);
            echo($jsonString);
        } else {
            echo "JSON block not found!";
        }
        */
        $start = strpos($response, '[');
        $end = strrpos($response, ']');
        if ($start !== false && $end !== false) {
            $jsonString = substr($response, $start, $end - $start + 1);
        } else {
            echo "Could not find JSON array!";
        }
        $arr = json_decode($jsonString, true);
        $bobj = new PayanarssTypeBusinessLogics();
        $types =   $bobj->convert_to_payanarss_type($arr);
        $app->addTypes($parentId, $types);
        $app->save_all_types();
        /*
        if ($schema && isset($schema['Table'])) {
            $table = new PayanarssType();
            $table->Id = uniqid();
            $table->ParentId = $table->Id;
            $table->Name = $schema['Table'];

            foreach ($schema['Columns'] ?? [] as $col) {
                $colObj = new PayanarssType();
                $colObj->Id = uniqid();
                $colObj->ParentId = $table->Id;
                $colObj->Name = $col['Name'] ?? '';
                $colObj->PayanarssTypeId = $app->get_type_id_by_name($col['Type'] ?? 'Text');
                foreach ($col['Attributes'] ?? [] as $attrName) {
                    $colObj->Attributes[] = $app->get_type_id_by_name($attrName);
                }
                $table->Children->add($colObj);
            }

            $app->Types->add($table);
            

            $_SESSION['PayanarssApp'] = $app;
            $success = "✅ Table '{$table->Name}' generated successfully!";
        } else {
            $error = "⚠️ Unable to parse AI response. Please try a simpler prompt.";
        }
            */
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payanarss Type Designer</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-screen bg-gray-50">
    <!-- 🔝 Header -->
    <header class="bg-blue-700 text-white px-6 py-4 shadow">
        <h1 class="text-xl font-semibold">🧠 Payanarss Type Designer</h1>
    </header>

    <div class="flex h-[calc(100%-64px)]"> <!-- Adjust height minus header -->
        <!-- 📁 Left Nav (Tree View) -->
        <aside class="w-64 bg-white border-r border-gray-300 overflow-y-auto p-4">
            <h2 class="text-md font-semibold mb-3">📁 Table Designs</h2>
            <ul class="text-xs italic">
                <?php include 'PayanarssTypeTreeNode.php'; ?>
                <?php renderTree($app->RootNodes, $parentId); ?>
            </ul>
        </aside>

        <!-- 📄 Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Tabs in Main View -->
            <main class="flex-1 overflow-y-auto p-4">
                <!-- Tab buttons -->
                <div class="flex space-x-2 mb-4">
                    <button id="designerTabBtn" onclick="switchTab('designerTab')"
                        class="px-4 py-2 border rounded bg-blue-500 text-white">
                        Designer
                    </button>
                    <button id="dataEntryTabBtn" onclick="switchTab('dataEntryTab')"
                        class="px-4 py-2 border rounded">
                        Data Entry
                    </button>
                </div>

                <!-- Tab content -->
                <div id="designerTab">
                    <?php include 'PayanarssTypeDesigner.php'; ?>
                </div>

                <div id="dataEntryTab" class="hidden">
                    <?php include 'PayanarssDataEntryVer.php'; ?>
                </div>

                <!-- Footer for Prompt-Based Schema Generation -->
                <div class="absolute bottom-0 left-0 right-0 bg-white border-t p-4 shadow flex items-center gap-4 z-50">
                    <form method="post" class="flex w-full items-center gap-2">
                        <input
                            type="text"
                            name="ai_prompt"
                            placeholder="💬 Describe your table (e.g., Customer with Name, Email, Phone)..."
                            class="flex-1 border px-3 py-2 rounded shadow text-sm"
                            required>
                        <button
                            type="submit"
                            name="generate_from_prompt"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                            🔮 Generate
                        </button>
                    </form>
                </div>

            </main>
        </div>

        <!-- Tab Switch Script -->
        <script>
            function switchTab(tabId) {
                document.getElementById('designerTab').classList.add('hidden');
                document.getElementById('dataEntryTab').classList.add('hidden');

                document.getElementById(tabId).classList.remove('hidden');

                document.getElementById('designerTabBtn').classList.remove('bg-blue-500', 'text-white');
                document.getElementById('dataEntryTabBtn').classList.remove('bg-blue-500', 'text-white');

                document.getElementById(tabId + 'Btn').classList.add('bg-blue-500', 'text-white');
            }
        </script>
</body>

</html>