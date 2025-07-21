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
            <ul class="text-xs">
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
                    <?php include 'PayanarssDataEntry.php'; ?>
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