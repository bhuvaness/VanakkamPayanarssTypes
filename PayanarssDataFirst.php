<?php
session_start();

$dataFile = __DIR__ . "/data_entry_store.json";

// Load saved data or initialize
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$columns = $data['columns'] ?? ['Column1'];
$rows = $data['rows'] ?? [[]];

// Add new column
if (isset($_POST['add_column'])) {
    $columns[] = 'Column' . (count($columns) + 1);
}

// Add new row
if (isset($_POST['add_row'])) {
    $rows[] = array_fill(0, count($columns), '');
}

// Save data
if (isset($_POST['save_data'])) {
    $rows = $_POST['rows'] ?? [];
}

// Write back to file
file_put_contents($dataFile, json_encode(['columns' => $columns, 'rows' => $rows], JSON_PRETTY_PRINT));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Data-First POC</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="p-6 bg-gray-100 text-sm">
    <div class="max-w-5xl mx-auto bg-white shadow-md rounded p-4">
        <h2 class="text-xl font-bold mb-4">📋 Data-First Entry (Key-Value Structure)</h2>

        <form method="post">
            <div class="flex gap-4 mb-4">
                <button type="submit" name="add_column" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">➕ Add Column</button>
                <button type="submit" name="add_row" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">➕ Add Row</button>
                <button type="submit" name="save_data" class="bg-indigo-500 text-white px-3 py-1 rounded hover:bg-indigo-600">💾 Save</button>
            </div>

            <div class="overflow-auto">
                <table class="min-w-full table-auto border border-gray-300">
                    <thead class="bg-gray-200">
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th class="border px-3 py-2"><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $rowIdx => $row): ?>
                            <tr>
                                <?php foreach ($columns as $colIdx => $col): ?>
                                    <td class="border px-3 py-1">
                                        <input type="text" name="rows[<?= $rowIdx ?>][<?= $colIdx ?>]"
                                            value="<?= htmlspecialchars($row[$colIdx] ?? '') ?>"
                                            class="w-full px-2 py-1 border rounded" />
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</body>

</html>