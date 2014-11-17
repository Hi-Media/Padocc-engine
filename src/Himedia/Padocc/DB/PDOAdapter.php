<?php

namespace Himedia\Padocc\DB;

use GAubry\Helpers\Helpers;
use PDO;

/**
 * Copyright (c) 2014 HiMedia Group
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2014 HiMedia Group
 * @author Geoffroy Aubry <gaubry@hi-media.com>
 * @license Apache License, Version 2.0
 */
class PDOAdapter implements DBAdapterInterface
{
    /**
     * List of backend \PDO instances indexed by DSN.
     * @var array
     */
    private static $aPDOInstances = array();

    /**
     * Backend PDO instance.
     * @var PDO
     * @see connect()
     * @see setPDOInstance()
     */
    private $oPDO;

    /**
     * Database source name: array(
     * 		'driver'   => (string) e.g. 'pdo_mysql', 'pdo_pgsql',
     * 		'hostname' => (string),
     * 		'port'     => (int),
     *		'db_name'  => (string),
     *		'username' => (string),
     *		'password' => (string)
     * );
     * @var array
     */
    private $aDSN;

    /**
     * List of instances indexed by DSN.
     * @var PDOAdapter[]
     */
    private static $aInstances = array();

    /**
     * Return a PDOAdapter instance.
     * Lazy connection to backend PDO instance.
     *
     * @param array $aDSN Database source name: array(
     * 		'driver'   => (string) e.g. 'pdo_mysql' ou 'pdo_pgsql',
     * 		'hostname' => (string),
     * 		'port'     => (int),
     *		'db_name'  => (string),
     *		'username' => (string),
     *		'password' => (string)
     * );
     * @param string $sQueriesLogPath path where to log all queries by DB
     * @return PDOAdapter
    */
    public static function getInstance(array $aDSN, $sQueriesLogPath = '')
    {
        $sKey = implode('|', $aDSN);
        if (! isset(self::$aInstances[$sKey])) {
            self::$aInstances[$sKey] = new PDOAdapter($aDSN, $sQueriesLogPath);
        }
        return self::$aInstances[$sKey];
    }

    /**
     * Constructor.
     *
     * @param array $aDSN Database source name: array(
     * 		'driver'   => (string) e.g. 'pdo_mysql' ou 'pdo_pgsql',
     * 		'hostname' => (string),
     * 		'port'     => (int),
     *		'db_name'  => (string),
     *		'username' => (string),
     *		'password' => (string)
     * );
     */
    public function __construct(array $aDSN)
    {
        $this->oPDO = null;
        $this->aDSN = $aDSN;
    }

    /**
     * Set backend PDO instance.
     *
     * @param PDO $oPDO PDO instance
     * @throws \BadMethodCallException iff backend PDO instance is already set.
     */
    public function setPDOInstance(PDO $oPDO)
    {
        if ($this->oPDO === null) {
            $this->oPDO = $oPDO;
        } else {
            throw new \BadMethodCallException('PDO instance already set!');
        }
    }

    private function getPdoInstance($sDSN, $sUsername, $sPassword, array $aPdoOptions)
    {
        try {
            $oPDO = new PDO($sDSN, $sUsername, $sPassword, $aPdoOptions);
        } catch (\PDOException $oException) {
            $sMsg = $oException->getMessage() . ". DSN was: '$sDSN'.";
            throw new \RuntimeException($sMsg, 1, $oException);
        }
        return $oPDO;
    }

    /**
     * Establishes the connection with the database and set backend PDO instance.
     */
    private function connect()
    {
        $aPdoOptions = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::ATTR_TIMEOUT            => 5
        );

        if ($this->oPDO === null) {
            $aDSN = $this->aDSN;
            $sKey = implode('|', $aDSN);
            if (! isset(self::$aPDOInstances[$sKey])) {
                if ($aDSN['driver'] == 'pdo_mysql') {
                    $sDSN = sprintf(
                        '%s:host=%s;port=%s;dbname=%s',
                        substr($aDSN['driver'], 4),
                        $aDSN['hostname'],
                        $aDSN['port'],
                        $aDSN['db_name']
                    );
                    $oPDO = $this->getPdoInstance($sDSN, $aDSN['username'], $aDSN['password'], $aPdoOptions);
                    $oPDO->query("SET NAMES 'UTF8';");

                    // Set UTC timezone:
                    $oPDO->query("SET time_zone = '+00:00';");
                    $oPDO->query("SET SESSION time_zone = '+00:00';");

                } elseif ($aDSN['driver'] == 'pdo_pgsql') {
                    $sDSN = sprintf(
                        '%s:host=%s;port=%s;dbname=%s',
                        substr($aDSN['driver'], 4),
                        $aDSN['hostname'],
                        $aDSN['port'],
                        $aDSN['db_name']
                    );
                    $oPDO = $this->getPdoInstance($sDSN, $aDSN['username'], $aDSN['password'], $aPdoOptions);
                    $oPDO->query("SET NAMES 'UTF8';");

                    // Set application_name if Postgresql AND v9+:
                    $oPDOStatement = $oPDO->query("SHOW server_version_num;");
                    $iPgVersion    = (int)$oPDOStatement->fetchColumn(0);
                    if ($iPgVersion >= 90000) {
                        $oPDO->query("SET application_name TO 'Padocc';");
                    }

                } elseif ($aDSN['driver'] == 'pdo_sqlite') {
                    $oPDO = $this->getPdoInstance('sqlite:' . $aDSN['hostname'], null, null, $aPdoOptions);

                } else {
                    throw new \UnexpectedValueException("Unknown driver type '{$aDSN['driver']}'", 1);
                }

                self::$aPDOInstances[$sKey] = $oPDO;
            }

            $this->oPDO = self::$aPDOInstances[$sKey];
        }
    }

    /**
     * Returns content of specified column of the first row of query's result.
     *
     * @param string $sQuery Query to execute.
     * @param int $iColumnNumber 0-indexed number of the column you wish to retrieve from the row.
     * If no value is supplied, PDOStatement::fetchColumn() fetches the first column.
     * @return string content of specified column of the first row of query's result
     */
    public function fetchColumn($sQuery, $iColumnNumber = 0)
    {
        $oPDOStatement = $this->query($sQuery);
        $sResult = $oPDOStatement->fetchColumn($iColumnNumber);
        return $sResult;
    }

    /**
     * Fetches the first row of of the specified SQL statement.
     * The row is an array indexed by column name.
     * If a result set row contains multiple columns with the same name,
     * then returns only a single value per column name.
     *
     * @param string $sQuery Statement to execute.
     * @return array returns the first row of of the specified SQL statement.
     */
    public function fetchRow($sQuery)
    {
        $oPDOStatement = $this->query($sQuery);
        $aRow = $oPDOStatement->fetch(PDO::FETCH_ASSOC);
        return $aRow;
    }

    /**
     * Returns an array containing all of the result set rows of the specified SQL statement.
     * Each row is an array indexed by column name.
     * If a result set row contains multiple columns with the same name,
     * then returns only a single value per column name.
     *
     * @param string $sQuery Statement to execute.
     * @return array returns an array containing
     * all of the remaining rows in the result set. The array represents each
     * row as an array of column values.
     */
    public function fetchAll($sQuery)
    {
        $oPDOStatement = $this->query($sQuery);
        $aRows = $oPDOStatement->fetchAll(PDO::FETCH_ASSOC);
        return $aRows;
    }

    /**
     * Executes the specified SQL statement, returning a result set as a PDOStatement object.
     *
     * @param string $sQuery Statement to execute.
     * @return \PDOStatement a PDOStatement object
     * @throws \PDOException on error
     */
    public function query($sQuery)
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        try {
            $oPDOStatement = $this->oPDO->query($sQuery);
        } catch (\PDOException $oException) {
            $sMsg = $oException->getMessage() . " Query was: $sQuery.";
            throw new \PDOException($sMsg, (int)$oException->getCode(), $oException);
        }
        return $oPDOStatement;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $sQuery The SQL statement to prepare and execute.
     * @throws \PDOException on error
     * @return int the number of rows that were modified
     * or deleted by the SQL statement. If no rows were affected returns 0.
     */
    public function exec($sQuery)
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        try {
            $iNbRows = $this->oPDO->exec($sQuery);
        } catch (\PDOException $oException) {
            $sMsg = $oException->getMessage() . " Query was: $sQuery.";
            throw new \PDOException($sMsg, (int)$oException->getCode(), $oException);
        }
        return $iNbRows;
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * Emulated prepared statements does not communicate with the database server
     * so prepare() does not check the statement.
     *
     * @param string $sQuery SQL statement
     * @throws \PDOException if error
     * @return \PDOStatement a PDOStatement object.
     */
    public function prepare($sQuery)
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        return $this->oPDO->prepare($sQuery);
    }

    public function executePreparedStatement(\PDOStatement $oStatement, array $aValues)
    {
        try {
            $bResult = $oStatement->execute($aValues);
        } catch (\PDOException $oException) {
            $sMsg = $oException->getMessage()
                  . " Query was: $oStatement->queryString. Values was: " . print_r($aValues, true);
            throw new \PDOException($sMsg, (int)$oException->getCode(), $oException);
        }
        return $bResult;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sSequenceName [optional] Name of the sequence object from which the ID should be returned.
     * @return string If a sequence name was not specified returns a
     * string representing the row ID of the last row that was inserted into
     * the database, else returns a string representing the last value retrieved from the specified sequence
     * object.
     */
    public function lastInsertId($sSequenceName = null)
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        return $this->oPDO->lastInsertId($sSequenceName);
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $sValue The string to be quoted.
     * @param int $iType [optional] Provides a data type hint for drivers that have alternate quoting styles.
     * @return string a quoted string that is theoretically safe to pass into an
     * SQL statement.
     *
     * Returns <b>FALSE</b> if the driver does not support quoting in
     * this way.
     */
    public function quote($sValue, $iType = \PDO::PARAM_STR)
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        return ($this->oPDO->quote($sValue, $iType) ?: $sValue);
    }

    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction()
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        return $this->oPDO->beginTransaction();
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBack()
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        return $this->oPDO->rollBack();
    }

    /**
     * Commits a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit()
    {
        if ($this->oPDO === null) {
            $this->connect();
        }

        return $this->oPDO->commit();
    }

    public function formatValue($mValue)
    {
        if ($mValue === null) {
            return 'NULL';
        } elseif ($mValue === true) {
            return "'t'";
        } elseif ($mValue === false) {
            return "'f'";
        } else {
            if ($this->oPDO === null) {
                $this->connect();
            }
            return $this->oPDO->quote(Helpers::utf8Encode($mValue));
        }
    }
}
