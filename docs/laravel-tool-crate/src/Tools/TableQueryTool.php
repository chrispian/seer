<?php

namespace HollisLabs\ToolCrate\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use HollisLabs\ToolCrate\Tools\Contracts\SummarizesTool;
use PDO;

class TableQueryTool extends Tool implements SummarizesTool
{
    protected string $name = 'table.query';
    protected string $title = 'Query CSV/TSV with SQL';
    protected string $description = 'Load a CSV/TSV into SQLite (in-memory) and run a SELECT.';

    public function schema(JsonSchema $s): array
    {
        return [
            'file' => $s->string()->required(),
            'sql'  => $s->string()->required()->description('SQL query, using table name "t" unless overridden'),
            'delimiter' => $s->string()->default('auto')->description('auto, csv, tsv, or single-char delimiter'),
            'header' => $s->boolean()->default(true),
            'table' => $s->string()->default('t'),
            'max_rows' => $s->integer()->default(200000),
            'limit_output' => $s->integer()->default(500),
        ];
    }

    public function handle(Request $r): Response
    {
        $path = (string) $r->get('file');
        if (!is_file($path)) return Response::error("File not found: {$path}");
        $sql = (string) $r->get('sql');
        $del = (string) $r->get('delimiter', 'auto');
        $header = (bool) $r->get('header', true);
        $table = (string) $r->get('table', 't');
        $maxRows = (int) $r->get('max_rows', 200000);
        $limit = (int) $r->get('limit_output', 500);

        $delimiter = self::resolveDelimiter($del, $path);
        $fh = fopen($path, 'rb');
        if ($fh === false) return Response::error("Unable to open: {$path}");

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $columns = [];
        $first = true;
        $rowCount = 0;

        while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
            if ($first) {
                if ($header) {
                    foreach ($row as $col) $columns[] = self::sanitizeColumn($col);
                } else {
                    for ($i = 0; $i < count($row); $i++) $columns[] = 'c' . ($i+1);
                    // treat this first row as data
                    self::maybeCreate($pdo, $table, $columns);
                    self::insert($pdo, $table, $columns, $row);
                    $rowCount++;
                }
                if ($header) {
                    self::maybeCreate($pdo, $table, $columns);
                }
                $first = false;
                continue;
            }

            self::insert($pdo, $table, $columns, $row);
            $rowCount++;
            if ($rowCount >= $maxRows) break;
        }
        fclose($fh);

        // Execute query
        $stmt = $pdo->query($sql);
        $outRows = [];
        $cols = [];
        if ($stmt) {
            $colCount = $stmt->columnCount();
            for ($i=0; $i<$colCount; $i++) {
                $meta = $stmt->getColumnMeta($i);
                $cols[] = $meta['name'] ?? ('col' . ($i+1));
            }
            $i = 0;
            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $outRows[] = $row;
                $i++;
                if ($i >= $limit) break;
            }
        }

        return Response::json([
            'table' => $table,
            'columns' => $cols,
            'rows' => $outRows,
            'loaded_rows' => $rowCount,
            'delimiter' => $delimiter,
        ]);
    }

    private static function resolveDelimiter(string $del, string $path): string
    {
        if ($del === 'csv') return ',';
        if ($del === 'tsv') return "\t";
        if ($del === 'auto') {
            // simple heuristic on first line
            $fh = fopen($path, 'rb');
            $line = $fh ? fgets($fh, 4096) : '';
            if ($fh) fclose($fh);
            $c = ["," => substr_count($line, ","), "\t" => substr_count($line, "\t"), ";" => substr_count($line, ";")];
            arsort($c);
            return array_key_first($c);
        }
        return $del;
    }

    private static function sanitizeColumn(string $name): string
    {
        $n = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $name));
        return $n === '' ? 'col' : $n;
    }

    private static function maybeCreate(PDO $pdo, string $table, array $columns): void
    {
        $cols = array_map(fn($c) => '"' . $c . '" TEXT', $columns);
        $sql = 'CREATE TABLE IF NOT EXISTS "' . $table . '" (' . implode(',', $cols) . ')';
        $pdo->exec($sql);
    }

    private static function insert(PDO $pdo, string $table, array $columns, array $row): void
    {
        // pad/truncate to columns length
        $vals = array_slice(array_pad($row, count($columns), null), 0, count($columns));
        $ph = implode(',', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO "' . $table . '" ("' . implode('","', $columns) . '") VALUES (' . $ph . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($vals);
    }

    public static function name(): string { return 'table.query'; }
    public static function title(): string { return 'Query CSV/TSV with SQL'; }
    public static function shortDescription(): string { return 'Load CSV/TSV to SQLite and SELECT.'; }
    public static function schemaSummary(): array
    {
        return [
            'file' => 'path to CSV/TSV',
            'sql' => 'SQL query to run',
            'delimiter' => 'auto/csv/tsv/char',
            'header' => 'first row is header',
            'table' => 'table name (default t)',
        ];
    }
}
