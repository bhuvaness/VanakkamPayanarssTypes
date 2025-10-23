<?php
class PayanarssTypeJsonDAO
{
    private string $file = "";
    private array $records = [];
    public function __construct($fileName, $record = [], $path = '')
    {
        $path = $path === '' ? "meta_data" : $path;
        $this->file = __DIR__ . "/{$path}/{$fileName}";
        $this->records = $record;
    }
    public function load(): array
    {
        $json = file_get_contents($this->file);
        $this->records = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "JSON Decode Error: " . json_last_error_msg();
        }
        return $this->records;
    }

    public function getByParent(string $tableId): array
    {
        return array_filter($this->records, fn($c) => $c['ParentId'] === $tableId);
    }

    public function add(array $record): void
    {
        $this->records[] = $record;
    }

    public function save(): void
    {
        file_put_contents($this->file, json_encode($this->records, JSON_PRETTY_PRINT));
    }
}
