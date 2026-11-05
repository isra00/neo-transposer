<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * The song tables as data that moves between databases: read, compared, written as a
 * data migration, applied with guards and exported to song_data.sql.
 *
 * Data is [table => [key => row]], every value a string or null. The chords of a song
 * are one row of the "song_chord" table: ['chords' => 'Am E7 Dm'].
 *
 * A change is [operation, table, key, data]:
 *   ['update', 'song', '181', ['lowest_note' => ['F#2', 'B1']]]  (column => [old, new])
 *   ['insert', 'song', '1301', $row]
 *   ['delete', 'song', '242', $row]
 *
 * verified_at is review state of each database, not song data: it never moves.
 */
final class SongData
{
    public const FILE = 'song_data.sql';

    private const KEYS = ['book' => 'id_book', 'unique_song' => 'id_unique_song', 'song' => 'id_song', 'song_chord' => 'id_song'];

    private const IGNORED_COLUMNS = ['verified_at'];

    /**
     * @return array<string, array<string, array<string, ?string>>>
     */
    public static function read(?string $connection = null): array
    {
        $db = DB::connection($connection);
        $data = [];

        foreach (['book', 'unique_song', 'song'] as $table) {
            $key = self::KEYS[$table];
            $columns = array_values(array_diff(Schema::connection($connection)->getColumnListing($table), self::IGNORED_COLUMNS));
            foreach ($db->table($table)->orderBy($key)->get($columns) as $row) {
                $data[$table][(string) $row->$key] = array_map(fn ($value) => $value === null ? null : (string) $value, (array) $row);
            }
        }

        $chords = [];
        foreach ($db->table('song_chord')->orderBy('id_song')->orderBy('position')->get() as $row) {
            $chords[(string) $row->id_song][] = $row->chord;
        }
        $data['song_chord'] = array_map(fn ($list) => ['chords' => implode(' ', $list)], $chords);

        return $data;
    }

    /**
     * What turns $from into $to. Inserts and updates go parents first, deletes children
     * first, so a song is never left pointing to a unique song that is not there yet.
     *
     * @param  array<string, array<string, array<string, ?string>>>  $from
     * @param  array<string, array<string, array<string, ?string>>>  $to
     * @return list<array{'insert'|'update'|'delete', string, string, array<string, mixed>}>
     */
    public static function changes(array $from, array $to): array
    {
        $changes = [];
        $deletes = [];

        foreach (array_keys(self::KEYS) as $table) {
            $old = $from[$table] ?? [];
            $new = $to[$table] ?? [];

            foreach ($new as $key => $row) {
                if (!isset($old[$key])) {
                    $changes[] = ['insert', $table, (string) $key, $row];

                    continue;
                }

                $columns = [];
                foreach ($row as $column => $value) {
                    if ($value !== ($old[$key][$column] ?? null)) {
                        $columns[$column] = [$old[$key][$column] ?? null, $value];
                    }
                }
                if ($columns) {
                    $changes[] = ['update', $table, (string) $key, $columns];
                }
            }

            foreach (array_diff_key($old, $new) as $key => $row) {
                $deletes[] = ['delete', $table, (string) $key, $row];
            }
        }

        return [...$changes, ...array_reverse($deletes)];
    }

    /**
     * Applies the changes, each only if the database still has the value it had when the
     * change was computed.
     *
     * @param  list<array{'insert'|'update'|'delete', string, string, array<string, mixed>}>  $changes
     * @return string[] The changes that did not apply, and why.
     */
    public static function apply(array $changes, ?string $connection = null): array
    {
        $db = DB::connection($connection);
        $conflicts = [];

        foreach ($changes as [$operation, $table, $key, $data]) {
            $current = self::readRow($db, $table, $key);
            [$expected, $target] = match ($operation) {
                'insert' => [null, $data],
                'delete' => [$data, null],
                'update' => [array_map(fn ($values) => $values[0], $data), array_map(fn ($values) => $values[1], $data)],
            };

            $found = $current === null ? null : array_intersect_key($current, $target ?? $expected);
            // Made in this database too: nothing left to do.
            if (self::same($found, $target)) {
                continue;
            }
            if (!self::same($found, $expected)) {
                $conflicts[] = "$operation $table $key: expected " . json_encode($expected, JSON_UNESCAPED_UNICODE)
                    . ', found ' . json_encode($found, JSON_UNESCAPED_UNICODE);

                continue;
            }

            self::writeRow($db, $operation, $table, $key, $data);
        }

        return $conflicts;
    }

    /**
     * The body of a data migration: all the changes or none.
     *
     * @param  list<array{'insert'|'update'|'delete', string, string, array<string, mixed>}>  $changes
     */
    public static function migrate(array $changes): void
    {
        DB::transaction(function () use ($changes) {
            $conflicts = self::apply($changes);
            if ($conflicts) {
                throw new RuntimeException(
                    "The song data was edited in this database after the migration was generated. Nothing was applied.\n"
                    . 'Make local and this database agree on these values, then generate the migration again:' . "\n"
                    . implode("\n", $conflicts)
                );
            }
        });
    }

    public static function export(string $file, ?string $connection = null): void
    {
        $db = DB::connection($connection);
        $sql = "-- Song data: books, unique songs, songs and chords. Data only: the tables are in create_tables.sql.\n"
            . "-- Generated by app:song-data-migration and app:pull-song-data. Do not edit by hand.\n\n"
            . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n";

        foreach (['book' => ['id_book'], 'unique_song' => ['id_unique_song'], 'song' => ['id_song'], 'song_chord' => ['id_song', 'position']] as $table => $order) {
            $columns = Schema::connection($connection)->getColumns($table);
            $columns = array_values(array_filter($columns, fn ($column) => !in_array($column['name'], self::IGNORED_COLUMNS)));
            $names = array_column($columns, 'name');
            $numeric = array_column(array_filter($columns, fn ($column) => str_contains($column['type_name'], 'int')), 'name');

            $sql .= "\n-- $table\n";
            $query = $db->table($table);
            foreach ($order as $column) {
                $query->orderBy($column);
            }
            foreach ($query->get($names) as $row) {
                $values = array_map(fn ($name) => match (true) {
                    $row->$name === null => 'NULL',
                    in_array($name, $numeric) => (string) $row->$name,
                    default => $db->getPdo()->quote((string) $row->$name),
                }, $names);
                $sql .= "INSERT INTO `$table` (`" . implode('`, `', $names) . '`) VALUES (' . implode(',', $values) . ");\n";
            }
        }

        file_put_contents($file, $sql . "\nSET FOREIGN_KEY_CHECKS = 1;\n");
    }

    /**
     * A copy of the song tables in a throwaway database, loaded from a SQL file.
     *
     * @return string The connection name.
     */
    public static function loadSnapshot(string $name, string $sqlFile): string
    {
        $main = config('database.connections.mysql.database');
        $connection = "snapshot_$name";
        $database = "{$main}_$connection";

        DB::statement("DROP DATABASE IF EXISTS `$database`");
        DB::statement("CREATE DATABASE `$database` COLLATE 'utf8_general_ci'");
        config(["database.connections.$connection" => ['database' => $database] + config('database.connections.mysql')]);
        DB::purge($connection);

        foreach (['book', 'unique_song', 'song', 'song_chord'] as $table) {
            DB::connection($connection)->statement("CREATE TABLE `$table` LIKE `$main`.`$table`");
        }
        DB::connection($connection)->unprepared(file_get_contents($sqlFile));

        return $connection;
    }

    public static function dropSnapshot(string $connection): void
    {
        DB::statement('DROP DATABASE IF EXISTS `' . config("database.connections.$connection.database") . '`');
        DB::purge($connection);
    }

    /**
     * @param  ?array<string, ?string>  $a
     * @param  ?array<string, ?string>  $b
     */
    private static function same(?array $a, ?array $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        ksort($a);
        ksort($b);

        return $a === $b;
    }

    /**
     * @return ?array<string, ?string>
     */
    private static function readRow(Connection $db, string $table, string $key): ?array
    {
        if ($table === 'song_chord') {
            $chords = $db->table('song_chord')->where('id_song', $key)->orderBy('position')->pluck('chord')->all();

            return $chords ? ['chords' => implode(' ', $chords)] : null;
        }

        $row = $db->table($table)->where(self::KEYS[$table], $key)->first();
        if ($row === null) {
            return null;
        }

        $row = array_diff_key((array) $row, array_flip(self::IGNORED_COLUMNS));

        return array_map(fn ($value) => $value === null ? null : (string) $value, $row);
    }

    /**
     * @param  'insert'|'update'|'delete'  $operation
     * @param  array<string, mixed>  $data
     */
    private static function writeRow(Connection $db, string $operation, string $table, string $key, array $data): void
    {
        $new = $operation === 'update' ? array_map(fn ($values) => $values[1], $data) : $data;

        if ($table === 'song_chord') {
            $db->table('song_chord')->where('id_song', $key)->delete();
            $chords = $operation === 'delete' ? [] : explode(' ', $new['chords']);
            foreach ($chords as $position => $chord) {
                $db->table('song_chord')->insert(['id_song' => $key, 'chord' => $chord, 'position' => $position]);
            }

            return;
        }

        match ($operation) {
            'insert' => $db->table($table)->insert($new),
            'update' => $db->table($table)->where(self::KEYS[$table], $key)->update($new),
            'delete' => $db->table($table)->where(self::KEYS[$table], $key)->delete(),
        };
    }
}
