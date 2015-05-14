<?php

/**
 * Author: Roberto Lombi <roberto.lombi@immobiliare.it>
 * Date: 14/05/15
 * Time: 10:52
 *
 * Copyright © Immobiliare S.p.A 2015 All rights reserved.
 * No part of this product may be reproduced without Immobiliare S.p.A. express consent.
 */

namespace Codesleeve\Fixture\Drivers;

use PDO;

class SqlStandard extends BaseDriver implements DriverInterface
{
    /**
     * A PDO connection instance.
     *
     * @var PDO
     */
    protected $db;

    /**
     * An array of tables that have had fixture data loaded into them.
     *
     * @var array
     */
    protected $tables = array();

    /**
     * Constructor method
     *
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Build a fixture record using the passed in values.
     *
     * @param  string $tableName
     * @param  array $records
     * @return array
     */
    public function buildRecords($tableName, array $records)
    {
        $insertedRecords = [];
        $this->tables[$tableName] = $tableName;

        $schemaName = $this->getSchemaName();

        $foreignKeys = $this->getForeignKeys($schemaName, $tableName);

        foreach ($records as $recordName => $recordValues) {

            array_walk($recordValues, function (&$value, $key, $foreignKeys) {
                if (in_array($key, $foreignKeys)) {
                    $value = $this->generateKey($value);
                }

                return $value;
            }, $foreignKeys);

            if (!array_key_exists($this->getPrimaryKey($schemaName, $tableName), $recordValues)) {
                $recordValues = array_merge(
                    $recordValues,
                    [
                        $this->getPrimaryKey($schemaName, $tableName) => $this->generateKey($recordName)
                    ]
                );
            }

            $fields = implode(', ', array_keys($recordValues));

            $values = array_values($recordValues);
            $placeholders = rtrim(str_repeat('?, ', count($recordValues)), ', ');
            $sql = "INSERT INTO $tableName ($fields) VALUES ($placeholders)";

            try {
                $sth = $this->db->prepare($sql);

                $sth->execute($values);
            } catch (\PDOException $e) {
                echo sprintf('Eccezione %s, lanciando %s con valori %s', $e->getMessage(), $sql, print_r($values, 1));
            }

            $insertedRecords[$recordName] = (object)$recordValues;

        }

        return $insertedRecords;
    }

    /**
     * Determine if a string ends with a set of specified characters.
     *
     * @param  string $haystack
     * @param  string $needle
     * @return boolean
     */
    protected function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    protected function getSchemaName()
    {
        return $this->db->query('select database()')->fetchColumn();
    }

    /**
     * @param $schema
     * @param $table
     * @return mixed
     */
    protected function getPrimaryKey($schema, $table)
    {
        $sql = <<<SQL
select column_name from information_schema.columns where table_schema = ? and table_name = ? and column_key = 'pri'
SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(1, $schema);
        $stmt->bindParam(2, $table);
        $stmt->execute();

        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res['column_name'];
    }

    protected function getForeignKeys($schema, $table)
    {
        $sql = <<<SQL
select column_name from information_schema.columns where table_schema = ? and table_name = ? and column_key = 'mul'
SQL;

        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(1, $schema, PDO::PARAM_STR);
        $stmt->bindParam(2, $table, PDO::PARAM_STR);
        $stmt->execute();

        $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fks = array_map(function ($x) {
            return $x['column_name'];
        }, $fks);

        return $fks;
    }
}