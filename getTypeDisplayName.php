<?php
require_once 'PayanarssTypeModel.php';

$input = json_decode(file_get_contents("php://input"), true);

$parentData = $input['payanarssTypes'] ?? null;
$typeData = $input['type'] ?? null;

$parent = new PayanarssTypes();
$type = new PayanarssType();

if ($parentData) {
    foreach ($parentData as $item) {
        $type = new PayanarssType();
        foreach ($item as $k => $v) $type->$k = $v;
        $parent->add($type);
    }
}

if ($typeData) foreach ($typeData as $key => $value) $type->$key = $value;

$parentName = $type->getParentTypeName($parent);

$result = $parentName . "." . $type->Name;

echo json_encode(['displayName' => $result]);
