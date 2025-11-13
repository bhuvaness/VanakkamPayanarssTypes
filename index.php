<?php

use Dom\NamedNodeMap;

require_once __DIR__ . '/PayanarssTypeModel.php';

session_start();

$app = null;
$parentId = "";
$payanarssTypes = null;

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

$payanarssTypes = $app->load_children_v1($parentId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_from_prompt'])) {
    $prompt = $_POST['ai_prompt'] ?? '';

    if (!empty($prompt)) {
        
        $response = $app->prompt_for_type($prompt, $parentId); 
        $start = strpos($response, '[');
        $end = strrpos($response, ']');
        if ($start !== false && $end !== false) {
            $jsonString = substr($response, $start, $end - $start + 1);
        } else {
            echo "Could not find JSON array!";
        }
        //echo "<pre>$response</pre>";
        //echo "<pre>$jsonString</pre>";
        $arr = json_decode($jsonString, true);
        $bobj = new PayanarssTypeBusinessLogics();
        $types =   $bobj->convert_to_payanarss_type($arr);
        $app->addTypes($parentId, $types);
        $payanarssType = $app->get_type($parentId);
        $app->save_all_types($payanarssType);
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

    <!-- Sidenav -->
    <div class="flex h-[calc(100%-64px)]"> <!-- Adjust height minus header -->
        <!-- 📁 Left Nav (Tree View) -->
        <aside class="w-80 bg-white border-r border-gray-300 overflow-y-auto p-4">
            <h2 class="text-md font-semibold mb-3">📁 Table Designs</h2>
            <ul class="text-xs italic">
                <?php include 'PayanarssTypeTreeNode.php'; ?>
                <?php renderTree($app->RootNodes, $parentId); ?>
            </ul>
        </aside>

        <!-- 📄 Main Content Area -->
        <div class="relative flex-1 flex flex-col">
            <!-- Tabs in Main View -->
            <main class="flex-1 overflow-y-auto p-4">
                <!-- Tab buttons -->
                <!-- <div class="flex space-x-2 mb-4">
                    <button id="designerTabBtn" onclick="switchTab('designerTab')"
                        class="px-4 py-2 border rounded bg-blue-500 text-white">
                        Designer
                    </button>
                    <button id="dataEntryTabBtn" onclick="switchTab('dataEntryTab')"
                        class="px-4 py-2 border rounded">
                        Data Entry
                    </button>
                </div> -->

                <!-- Tab content -->
                <div id="designerTab">
                    <?php include 'PayanarssTypeDesigner.php'; ?>
                </div>

                <div id="dataEntryTab" class="hidden">
                    <?php include 'PayanarssDataEntry.php'; ?>
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