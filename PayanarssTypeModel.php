<?php
require_once 'PayanarssTypeJsonDAO.php';
require_once 'PayanarssCrud.php';

class PayanarssTypeApplication
{
    public PayanarssTypes $Types;
    public ?PayanarssTypes $RootNodes = null;
    public ?PayanarssType $Attribute = null;
    public ?PayanarssTypes $Attributes = null;
    public ?PayanarssTypes $SelectableTypes = null;

    public function __construct()
    {
        $this->Types = new PayanarssTypes();
        $this->RootNodes = null;
        $this->Attribute = null;
    }
    public function save_all_types()
    {
        $busObj = new PayanarssTypeBusinessLogics();
        $busObj->save_all($this->Types);
        return $this->Types;
    }
    public function remove_type(string $typeId): PayanarssTypes
    {
        $busObj = new PayanarssTypeBusinessLogics();
        $this->Types = $busObj->remove_type($this->Types, $typeId);
        $this->RootNodes = $busObj->map_parent_children($this->Types->copy());
        return $this->Types;
    }
    public function load_all_types(): PayanarssTypes
    {
        $busObj = new PayanarssTypeBusinessLogics();
        $this->Types = $busObj->load_all();
        //$this->Attribute = $this->get_type("68593dc3b71d7");
        $this->Attributes = $busObj->getAttributes($this->Types);
        $busObj->map_payanarss_type($this->Types);
        $this->RootNodes = $busObj->map_parent_children($this->Types->copy());
        return $this->Types;
    }
    public function getChildren(string $parentId = ""): PayanarssTypes
    {
        $roots = new PayanarssTypes();

        foreach ($this->Types as $eachType) {
            if (($parentId === "" && $eachType->Id === $eachType->ParentId)
                || ($eachType->ParentId === $parentId && $eachType->Id !== $eachType->ParentId)
            )
                $roots->add($eachType);
        }
        return $roots;
    }
    public function load_children_v1(string $id): PayanarssTypes
    {
        if (!isset($id) || $id === "") {
            return $this->Types;
        }

        foreach ($this->Types as $eachType) {
            if ($eachType->Id == $id)
                return $eachType->Children;
        }

        return new PayanarssTypes();
    }
    public function add_new_type($parentId = null): PayanarssType
    {
        $busObj = new PayanarssTypeBusinessLogics();
        $new = $busObj->create_new($parentId);
        $this->Types->add($new);
        $this->RootNodes = $this->map_parent_children();
        return $new;
    }
    public function map_parent_children(): PayanarssTypes
    {
        $busObj = new PayanarssTypeBusinessLogics();
        $this->RootNodes = $busObj->map_parent_children($this->Types->copy());
        return $this->RootNodes;
    }
    public function get_type($typeId)
    {
        foreach ($this->Types as $eachTyp) {
            if ($eachTyp->Id === $typeId)
                return $eachTyp;
        }
    }
}

class PayanarssType implements JsonSerializable
{
    public string $Id = "";
    public string $ParentId = "";
    public string $Name = "";
    public string $PayanarssTypeId = "";
    public $Attributes = [];
    public ?string $ParentName = null;
    public ?PayanarssType $Parent = null;
    public ?PayanarssType $Type = null;
    public ?PayanarssTypes $Children = null;
    public $Rows = [];
    public function __construct()
    {
        $this->Id = "";
        $this->ParentId = $this->Id;
        $this->Name = "";
        $this->PayanarssTypeId = "";
        $this->Attributes = null;
        $this->Type = null;
        $this->Parent = null;
        $this->ParentName = null;
        $this->Children = new PayanarssTypes();
        $this->Rows = [];
    }
    public static function create_new($parentId = null): PayanarssType
    {
        $new = new PayanarssType();
        $new->Id = uniqid();
        $new->Name = "Name";
        $new->PayanarssTypeId = "10000000000000000000000000000000";
        $new->ParentId = !isset($parentId) || $parentId === "" ? $new->Id : $parentId;
        return $new;
    }
    public function getTypeName(PayanarssTypes $allTypes): string
    {
        if ($this->Id === $this->PayanarssTypeId) {
            return $this->Name;
        } else if ($this->Type === null) {
            foreach ($allTypes as $eachType) {
                if ($eachType->Id == $this->PayanarssTypeId)
                    return $eachType->Name;
            }
            return $this->Name;
        }
        return $this->Type->Name;
    }
    public function getParentTypeName(PayanarssTypes $allTypes): string
    {
        foreach ($allTypes as $eachType) {
            if ($eachType->Id == $this->ParentId)
                return $eachType->Name;
        }
        return count($allTypes);
    }
    public function isChildTable(PayanarssTypes $allTypes): bool
    {
        foreach ($allTypes as $eachType) {
            if ($eachType->Id == $this->PayanarssTypeId)
                return $eachType->Name === 'IsChildTable';
        }
        return false;
    }
    public function isTypeOf(PayanarssTypes $allTypes, string $typeName): bool
    {
        foreach ($allTypes as $eachType) {
            if ($eachType->Id == $this->PayanarssTypeId)
                return $eachType->Name === $typeName;
        }
        return false;
    }
    public function remove_type($typeId): PayanarssTypes
    {
        $index = 0;
        foreach ($this->Children as $eachTyp) {
            if ($eachTyp->Id === $typeId) {
                $this->Children->remove($index);
                break;
            }
            $index++;
        }
        return $this->Children;
    }
    public function jsonSerialize(): mixed
    {
        return [
            'Id' => $this->Id,
            'ParentId' => $this->ParentId,
            'Name' => $this->Name,
            'PayanarssTypeId' => $this->PayanarssTypeId,
            'Attributes' => $this->Attributes
        ];
    }
}

class PayanarssTypes implements Iterator, Countable
{
    private $items = [];
    private array $keys = [];
    private $position = 0;
    private SplObjectStorage $storage;

    public function __construct()
    {
        $this->storage = new SplObjectStorage();
    }

    public function add(PayanarssType $item)
    {
        if (!$this->contains($item)) {
            $this->items[] = $item;
            //$this->keys = array_keys($this->items);
            $this->storage->attach($item);
        }
    }
    public function getItemById(string $id): ?PayanarssType
    {
        return $this->items[$id] ?? null;
    }
    public function copy(bool $deep = false): self
    {
        $new = new self();

        foreach ($this as $item) {
            if ($deep) {
                $new->add(clone $item);  // deep copy
            } else {
                $new->add($item);        // shallow copy
            }
        }

        return $new;
    }
    public function current(): PayanarssType
    {
        return $this->items[$this->position];
    }

    public function contains(PayanarssType $item)
    {
        foreach ($this->items as $eachItem) {
            if ($eachItem->Id == $item->Id)
                return true;
        }
        return false;
    }
    public function key(): int|string
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    public function count(): int
    {
        return count($this->items);
    }
    public function remove($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        return $this->items;
    }
    public function all()
    {
        return $this->items;
    }
}

class PayanarssTypeBusinessLogics
{
    public function create_new($parentId = null): PayanarssType
    {
        return PayanarssType::create_new($parentId);
    }
    function convertToArray($collection)
    {
        $array = is_object($collection) && method_exists($collection, 'all')
            ? $collection->all()
            : (is_iterable($collection) ? iterator_to_array($collection) : []);
        return $array;
        /*return array_map(function ($obj) {
            return is_object($obj) ? json_encode($obj) : $obj;
        }, $array);*/
    }
    function getAttributes(PayanarssTypes $types)
    {
        $attributes = new PayanarssTypes();

        foreach ($types as $eachType) {
            if (!isset($eachType->Attributes) || count($eachType->Attributes) === 0)
                continue;

            foreach ($eachType->Attributes as $attr) {
                $attrTyp = $this->get_type($types, $attr);
                if (isset($attrType) && $attrTyp->Id !== $attr && $attrTyp->Name === "AttributeType") {
                    $alreadyAdded = false;

                    foreach ($attributes as $eachAttr) {
                        if ($eachAttr->Id === $attr) {
                            $alreadyAdded = true;
                            break;
                        }
                    }

                    if (!$alreadyAdded) {
                        $attributes->add($eachType);
                        break;
                    }
                }
            }
        }
        return $attributes;
    }
    function map_payanarss_type(PayanarssTypes $types)
    {
        foreach ($types as $eachType) {
            if (isset($eachType->Type))
                continue;
            if ($eachType->Id !== $eachType->PayanarssTypeId)
                $eachType->Type = $this->get_type($types, $eachType->PayanarssTypeId);
            else
                $eachType->Type = $eachType;
        }
    }
    function map_parent_children(PayanarssTypes $allTypes): PayanarssTypes
    {
        $rootNodes = new PayanarssTypes();
        $processed = [];

        foreach ($allTypes as $eachTyp) {
            // Avoid processing the same node again
            if (isset($processed[$eachTyp->Id])) {
                continue;
            }
            if ($eachTyp->Id === $eachTyp->ParentId)
                $rootNodes->add($eachTyp);
            else {
                $parent_tbl = $this->get_type($allTypes, $eachTyp->ParentId);
                if ($parent_tbl && $parent_tbl->Id !== $eachTyp->Id) {
                    // Avoid adding duplicates
                    $alreadyAdded = false;
                    foreach ($parent_tbl->Children as $child) {
                        if ($child->Id === $eachTyp->Id) {
                            $alreadyAdded = true;
                            break;
                        }
                    }

                    if (!$alreadyAdded) {
                        //$eachTyp->Parent = $parent_tbl;
                        $eachTyp->ParentName = $parent_tbl->Name;
                        $parent_tbl->Children->add($eachTyp);
                    }
                }
            }
            $processed[$eachTyp->Id] = true;
        }

        return $rootNodes;
    }
    function get_type(PayanarssTypes $types, string $typeId): ?PayanarssType
    {
        foreach ($types as $eachType) {
            if ($eachType->Id === $typeId) {
                return $eachType;
            }
        }
        return null;
    }
    function save_all(PayanarssTypes $types, string $entityId = "1000000000000000000000000000000000", ?string $fileName = null)
    {
        foreach ($types as $type) {
            createRecord($type->Id, $type, $entityId);
        }
        $fileName = (!isset($fileName)) ? "VanakkamPayanarssTypes.json" : $fileName;
        $dao = new PayanarssTypeJsonDAO($fileName, $this->convertToArray($types));
        $dao->save();
    }
    function save_data($id, $datas, string $entityId, ?string $fileName = null)
    {
        foreach ($datas as $data) {
            createRecord($id, $data, $entityId);
        }
        $fileName = (!isset($fileName)) ? "VanakkamPayanarssDataa.json" : $fileName;
        $dao = new PayanarssTypeJsonDAO($fileName, $this->convertToArray($datas), "datas");
        $dao->save();
    }
    function read_all_records(string $entityId): array {
        return readAllRecords($entityId);
    }
    function load_all(): PayanarssTypes
    {
        $dao = new PayanarssTypeJsonDAO("VanakkamPayanarssTypes.json");
        $loadedTypes = $dao->load();
        return $this->convert_to_payanarss_type($loadedTypes);
    }
    function remove_type(PayanarssTypes &$types, $typeId): PayanarssTypes
    {
        $index = 0;
        foreach ($types as $eachTyp) {
            if ($eachTyp->Id === $typeId) {
                $types->remove($index);
                break;
            }
            $index++;
        }
        return $types;
    }
    function convert_to_payanarss_type($loadedTypes): PayanarssTypes
    {
        $types = new PayanarssTypes();
        foreach ($loadedTypes as $typeData) {
            $typ = new PayanarssType();
            $typ->Id = $typeData['Id'] ?? uniqid();
            $typ->ParentId = $typeData['ParentId'] ?? $typ->Id;
            $typ->Name = $typeData['Name'] ?? '';
            $typ->PayanarssTypeId = isset($typeData['PayanarssTypeId']) ? $typeData['PayanarssTypeId'] ?? '' : '';
            //$typ->IsGroupType = isset($typeData['IsGroupType']) ? $typeData['IsGroupType'] ?? 0 : 0;
            //$typ->IsTableType = isset($typeData['IsTableType']) ? $typeData['IsTableType'] ?? 0 : 0;

            if (isset($typeData['Attributes']) && is_array($typeData['Attributes'])) {
                $typ->Attributes = $typeData['Attributes'];
            }

            $types->add($typ);
        }
        return $types;
    }
}
