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
        $arr = json_decode($jsonString, true);
        $bobj = new PayanarssTypeBusinessLogics();
        $types = $bobj->convert_to_payanarss_type($arr);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payanarss Type Designer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Resizer handle */
        .resizer {
            width: 5px;
            cursor: col-resize;
            background: transparent;
            position: relative;
            user-select: none;
        }

        .resizer:hover::before,
        .resizer.resizing::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 3px;
            background: #3b82f6;
        }

        .resizer:hover {
            background: #e0e7ff;
        }

        /* Smooth transitions */
        .tab-button {
            transition: all 0.2s ease;
        }

        /* Loading state */
        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #3b82f6;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Collapse/Expand Animation */
        .sidebar {
            transition: width 0.3s ease, min-width 0.3s ease;
        }

        .sidebar.collapsed {
            width: 48px !important;
            min-width: 48px !important;
        }

        .sidebar.collapsed .sidebar-content {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar-content {
            transition: opacity 0.2s ease;
        }

        /* Prompt input focus effect */
        .prompt-input:focus {
            box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.1);
        }
    </style>
</head>

<body class="h-screen bg-gradient-to-br from-gray-50 to-gray-100 overflow-hidden">

    <!-- 🔝 Enhanced Header -->
    <header class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white px-6 py-4 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                <div>
                    <h1 class="text-xl font-bold">Payanarss Type Designer</h1>
                    <p class="text-xs text-blue-200">AI-Powered ERP Metadata Engine</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Status Indicator -->
                <div class="flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-lg">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium">System Active</span>
                </div>

                <!-- Settings Button -->
                <button class="p-2 hover:bg-white/10 rounded-lg transition-colors" title="Settings">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div class="flex h-[calc(100vh-80px)]">

        <!-- 📁 Resizable Left Sidebar -->
        <aside id="sidebar" class="sidebar bg-white border-r border-gray-200 shadow-sm flex" style="width: 480px; min-width: 240px; max-width: 600px;">

            <!-- Sidebar Content -->
            <div class="sidebar-content flex-1 flex flex-col overflow-hidden">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            Type Hierarchy
                        </h2>
                        <button onclick="toggleSidebar()" class="p-1 hover:bg-white rounded transition-colors" title="Collapse">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="relative">
                        <input
                            type="text"
                            id="treeSearch"
                            placeholder="Search types..."
                            class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onkeyup="filterTree(this.value)">
                        <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Tree View -->
                <div class="flex-1 overflow-y-auto p-3">
                    <ul class="space-y-1" id="treeView">
                        <?php include 'PayanarssTypeTreeNode.php'; ?>
                        <?php renderTree($app->RootNodes, $parentId); ?>
                    </ul>
                </div>

                <!-- Sidebar Footer Stats -->
                <div class="p-3 border-t border-gray-200 bg-gray-50">
                    <div class="text-xs text-gray-600 space-y-1">
                        <div class="flex justify-between">
                            <span>Total Types:</span>
                            <span class="font-semibold text-gray-800">
                                <?php echo count($app->RootNodes ?? []); ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Last Updated:</span>
                            <span class="font-semibold text-gray-800">Just now</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resizer Handle -->
            <div class="resizer" id="resizer"></div>

            <!-- Collapsed State Button -->
            <div class="hidden items-center justify-center py-4" id="expandBtn">
                <button onclick="toggleSidebar()" class="p-2 hover:bg-gray-100 rounded transition-colors" title="Expand">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- 📄 Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden bg-white">

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 bg-white shadow-sm">
                <div class="flex items-center px-6 py-3 gap-2">
                    <button id="designerTabBtn" onclick="switchTab('designerTab')"
                        class="tab-button px-4 py-2 rounded-lg font-medium text-sm bg-blue-600 text-white shadow-sm">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z" />
                            </svg>
                            Type Designer
                        </span>
                    </button>
                    <button id="dataEntryTabBtn" onclick="switchTab('dataEntryTab')"
                        class="tab-button px-4 py-2 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-100">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Data Entry
                        </span>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <main class="flex-1 overflow-y-auto p-6 pb-32">
                <div id="designerTab">
                    <?php include 'PayanarssTypeDesigner.php'; ?>
                </div>

                <div id="dataEntryTab" class="hidden">
                    <?php include 'PayanarssDataEntry.php'; ?>
                </div>
            </main>

            <!-- Enhanced AI Prompt Footer -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-r from-purple-50 to-indigo-50 border-t border-purple-200 shadow-lg z-50">
                <form method="post" class="p-4" id="promptForm">
                    <div class="max-w-5xl mx-auto">
                        <div class="flex items-center gap-3">
                            <!-- AI Icon -->
                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full flex items-center justify-center shadow-md">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>

                            <!-- Input Field -->
                            <input
                                type="text"
                                name="ai_prompt"
                                id="aiPromptInput"
                                placeholder="💡 Describe your schema (e.g., 'Create Employee table with Name, Email, Phone, Date of Birth')..."
                                class="prompt-input flex-1 border-2 border-purple-300 px-4 py-3 rounded-xl shadow-sm text-sm focus:outline-none focus:border-purple-500 transition-all"
                                required>

                            <!-- Generate Button -->
                            <button
                                type="submit"
                                name="generate_from_prompt"
                                id="generateBtn"
                                class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-semibold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Generate
                            </button>
                        </div>

                        <!-- Help Text -->
                        <p class="text-xs text-gray-600 mt-2 ml-14">
                            💬 Try: "Customer with Name, Email, Phone" or "Employee with FirstName, LastName, DateOfBirth, Salary"
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Resizer Functionality
        const resizer = document.getElementById('resizer');
        const sidebar = document.getElementById('sidebar');
        let isResizing = false;

        resizer.addEventListener('mousedown', (e) => {
            isResizing = true;
            resizer.classList.add('resizing');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;

            const newWidth = e.clientX;
            const minWidth = 240;
            const maxWidth = 600;

            if (newWidth >= minWidth && newWidth <= maxWidth) {
                sidebar.style.width = newWidth + 'px';
            }
        });

        document.addEventListener('mouseup', () => {
            if (isResizing) {
                isResizing = false;
                resizer.classList.remove('resizing');
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
            }
        });

        // Collapse/Expand Sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const expandBtn = document.getElementById('expandBtn');
            const sidebarContent = sidebar.querySelector('.sidebar-content');

            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
                sidebarContent.classList.add('hidden');
                expandBtn.classList.remove('hidden');
                expandBtn.classList.add('flex');
            } else {
                sidebarContent.classList.remove('hidden');
                expandBtn.classList.remove('flex');
                expandBtn.classList.add('hidden');
            }
        }

        // Tab Switching
        function switchTab(tabId) {
            // Hide all tabs
            document.getElementById('designerTab').classList.add('hidden');
            document.getElementById('dataEntryTab').classList.add('hidden');

            // Show selected tab
            document.getElementById(tabId).classList.remove('hidden');

            // Update button styles
            const designerBtn = document.getElementById('designerTabBtn');
            const dataEntryBtn = document.getElementById('dataEntryTabBtn');

            designerBtn.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
            designerBtn.classList.add('text-gray-700', 'hover:bg-gray-100');

            dataEntryBtn.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
            dataEntryBtn.classList.add('text-gray-700', 'hover:bg-gray-100');

            const activeBtn = document.getElementById(tabId + 'Btn');
            activeBtn.classList.remove('text-gray-700', 'hover:bg-gray-100');
            activeBtn.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
        }

        // Tree Search Filter
        function filterTree(searchTerm) {
            const tree = document.getElementById('treeView');
            const items = tree.querySelectorAll('li');
            searchTerm = searchTerm.toLowerCase();

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Form submission with loading state
        document.getElementById('promptForm').addEventListener('submit', function() {
            const btn = document.getElementById('generateBtn');
            const input = document.getElementById('aiPromptInput');

            btn.classList.add('loading');
            btn.disabled = true;
            input.disabled = true;
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('treeSearch').focus();
            }

            // Ctrl/Cmd + / to focus AI prompt
            if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                e.preventDefault();
                document.getElementById('aiPromptInput').focus();
            }
        });
    </script>
</body>

</html>