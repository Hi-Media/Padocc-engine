<?php

namespace Himedia\Padocc\DB;

interface DBAdapterInterface
{
    /**
     * Returns content of specified column of the first row of query's result.
     *
     * @param string $sQuery Query to execute.
     * @param int $iColumnNumber 0-indexed number of the column you wish to retrieve from the row.
     * If no value is supplied, PDOStatement::fetchColumn() fetches the first column.
     * @return string content of specified column of the first row of query's result
     */
    public function fetchColumn($sQuery, $iColumnNumber = 0);

    /**
     * Fetches the first row of of the specified SQL statement.
     * The row is an array indexed by column name.
     * If a result set row contains multiple columns with the same name,
     * then returns only a single value per column name.
     *
     * @param string $sQuery Statement to execute.
     * @return array returns the first row of of the specified SQL statement.
     */
    public function fetchRow($sQuery);

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
    public function fetchAll($sQuery);

    /**
     * Executes the specified SQL statement, returning a result set as a PDOStatement object.
     *
     * @param string $sQuery Statement to execute.
     * @return \PDOStatement a PDOStatement object
     * @throws \PDOException on error
     */
    public function query($sQuery);

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param string $sQuery The SQL statement to prepare and execute.
     * @throws \PDOException on error
     * @return int the number of rows that were modified
     * or deleted by the SQL statement. If no rows were affected returns 0.
     */
    public function exec($sQuery);

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
    public function prepare($sQuery);

    public function executePreparedStatement (\PDOStatement $oStatement, array $aValues);

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sSequenceName [optional] Name of the sequence object from which the ID should be returned.
     * @return string If a sequence name was not specified returns a
     * string representing the row ID of the last row that was inserted into
     * the database, else returns a string representing the last value retrieved from the specified sequence
     * object.
     */
    public function lastInsertId($sSequenceName = null);

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
    public function quote($sValue, $iType = \PDO::PARAM_STR);

    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction();

    /**
     * Rolls back a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBack();

    /**
     * Commits a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit();

    public function formatValue($mValue);
}
