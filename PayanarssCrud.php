<?php

define('ENK_KEY', '12345678901234567890123456789012');
define('RECORD_FILE', 'datas/');
define('INDEX_FILE', 'indexs/');
define('CIPHER', 'AES-256-CBC');

// Load index
function loadIndex($entityId)
{
    return file_exists(getFileName(INDEX_FILE, $entityId)) ? json_decode(file_get_contents(getFileName(INDEX_FILE, $entityId)), true) : [];
}

// Save index
function saveIndex($index, $entityId)
{
    if (!file_exists(getFileName(INDEX_FILE, $entityId))) {
        file_put_contents(getFileName(INDEX_FILE, $entityId), '');
    }
    file_put_contents(getFileName(INDEX_FILE, $entityId), json_encode($index), JSON_PRETTY_PRINT);
}

// Encrypt and compress JSON
function encryptAndCompress($json, $key)
{
    $ivLen = openssl_cipher_iv_length(CIPHER);
    $iv = openssl_random_pseudo_bytes($ivLen);
    $compressed = gzcompress($json);
    $encrypted = openssl_encrypt($compressed, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    return $iv . $encrypted;
}

// Decrypt and decompress
function decryptAndDecompress($data, $key)
{
    return $data;
    $ivLen = openssl_cipher_iv_length(CIPHER);
    $iv = substr($data, 0, $ivLen);
    $encrypted = substr($data, $ivLen);
    $decrypted = openssl_decrypt($encrypted, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    return gzuncompress($decrypted);
}
function getFileName($path, $fileName): string
{
    $ext = $path === "datas" ? ".dat" : ".idx";
    return $path . $fileName . $ext;
}
// CREATE
function createRecord($id, $record, $entityId)
{
    $index = loadIndex($entityId);

    $payload = json_encode($record); //encryptAndCompress(json_encode($record), ENK_KEY);
    $length = strlen($payload);

    $entry = pack('N', $length) . chr(0) . $payload;

    $offset = file_exists(getFileName(RECORD_FILE, $entityId)) ? filesize(getFileName(RECORD_FILE, $entityId)) : 0;

    file_put_contents(getFileName(RECORD_FILE, $entityId), $entry, FILE_APPEND);

    $index[$id] = [
        'offset' => $offset,
        'length' => strlen($payload),
        'entity' => $entityId,
    ];

    saveIndex($index, $entityId);

    return $id;
}

// READ
function readRecord($id, $entityId)
{
    $index = loadIndex($entityId);
    if (!isset($index[$id])) return null;

    $offset = $index[$id];
    $fp = fopen(getFileName(RECORD_FILE, $entityId), 'rb');
    fseek($fp, $offset);
    $length = unpack('N', fread($fp, 4))[1];
    $flag = ord(fread($fp, 1));

    if ($flag === 1) return null; // Deleted

    $data = fread($fp, $length);
    fclose($fp);
    return json_decode(decryptAndDecompress($data, ENK_KEY), true);
}
function readAllRecords(string $entityId): array
{
    $index = loadIndex($entityId);
    $matches = [];
    foreach ($index as $entry) {
        $row = [];
        foreach ($entry as $id => $meta) {
            //echo $index[$entry];
            if (($meta['entity'] ?? '') === $entityId) {
                $matches[] = [
                    'id' => $id,
                    'offset' => $meta['offset'],
                    'length' => $meta['length']
                ];
            }
        }
    }

    $fp = fopen(getFileName(RECORD_FILE, $entityId), 'rb');
    $results = [];

    foreach ($matches as $match) {
        fseek($fp, $match['offset']);
        $record = fread($fp, $match['length']);
        $decoded = decryptAndDecompress($record, ENK_KEY);
        $json = trim($decoded);
        $json = ltrim($json, "\xEF\xBB\xBF"); // Remove UTF-8 BOM if present
        $json = preg_replace('/[^\x20-\x7E]+/', '', $json); // Remove non-ASCII (optional)
        $results[] = json_decode($json, true);
    }

    fclose($fp);
    return $results;
}
// UPDATE (mark deleted and insert new)
function updateRecord($id, $newData, string $entityId)
{
    $index = loadIndex($entityId);
    $payload = encryptAndCompress(json_encode($newData), ENK_KEY);

    if (!isset($index[$id])) return false;
    if (($index[$id]['entity'] ?? '') !== $entityId) return false;

    // Mark old as deleted
    $fp = fopen(getFileName(RECORD_FILE, $entityId), 'r+b');
    fseek($fp, $index[$id] + 4); // skip length
    fwrite($fp, chr(1)); // mark deleted
    fclose($fp);

    $offset = file_exists(getFileName(RECORD_FILE, $entityId)) ? filesize(getFileName(RECORD_FILE, $entityId)) : 0;
    // Update index to point to new offset
    $index[$id] = [
        'offset'  => $offset,
        'length' => strlen($payload),
        'entity' => $entityId,
    ];

    // Create new
    $newId = createRecord($id, $newData, $entityId);
    unset($index[$id]);
    saveIndex($index, $entityId);
    return $newId;
}

// DELETE
function deleteRecord(string $id, string $entityId)
{
    $index = loadIndex($entityId);
    if (!isset($index[$id])) return false;

    if (($index[$id]['entity'] ?? '') !== $entityId) return false;

    $fp = fopen(getFileName(RECORD_FILE, $entityId), 'r+b');
    fseek($fp, $index[$id] + 4); // skip length
    fwrite($fp, chr(1)); // mark deleted
    fclose($fp);

    unset($index[$id]);
    saveIndex($index, $entityId);
    return true;
}

// COMPACT (Optional: rebuild file to clean deleted records)
function compactRecords($key, $entityId)
{
    $index = loadIndex($entityId);
    $newIndex = [];
    $newFile = '';

    $fp = fopen(getFileName(RECORD_FILE, $entityId), 'rb');
    $newOffset = 0;

    while (!feof($fp)) {
        $pos = ftell($fp);
        $lenData = fread($fp, 4);
        if (strlen($lenData) < 4) break;

        $length = unpack('N', $lenData)[1];
        $flag = ord(fread($fp, 1));
        $data = fread($fp, $length);

        if ($flag === 0) {
            // Valid record, copy
            $record = decryptAndDecompress($data, $key);
            $id = json_decode($record, true)['Id'];
            $entry = pack('N', $length) . chr(0) . $data;
            $newFile .= $entry;
            $newIndex[$id] = $newOffset;
            $newOffset += strlen($entry);
        }
    }

    fclose($fp);
    file_put_contents(getFileName(RECORD_FILE, $entityId), $newFile);
    saveIndex($newIndex, $entityId);
}

function readCustomersLike($search, $key, $entityId)
{
    $index = loadIndex($entityId); // assume format: [ id => [ offset, entity ] ]
    $matches = [];
    $fp = fopen(getFileName(RECORD_FILE, $entityId), 'rb');

    foreach ($index as $id => $meta) {
        if ($meta['entity'] !== 'Customer') continue;

        fseek($fp, $meta['offset']);
        $length = unpack('N', fread($fp, 4))[1];
        $flag = ord(fread($fp, 1));

        if ($flag === 1) continue; // deleted

        $data = fread($fp, $length);
        $json = decryptAndDecompress($data, $key);
        $record = json_decode($json, true);

        if (isset($record['CustomerName']) && stripos($record['CustomerName'], $search) === 0) {
            $matches[] = $record;
        }
    }

    fclose($fp);
    return $matches;
}
