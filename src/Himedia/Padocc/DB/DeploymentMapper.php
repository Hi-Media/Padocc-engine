<?php

namespace Himedia\Padocc\DB;

/**
 * Deployment entity.
 *
 *
 *
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
class DeploymentMapper
{
    /**
     * @var DBAdapterInterface
     */
    private $oDB;

    /**
     * @var string Name of SQL table.
     */
    private $sTable;

    /**
     * @var string Primary key column.
     */
    private $sPK;

    /**
     * Constructor.
     *
     * @param DBAdapterInterface $oDB
     */
    public function __construct(DBAdapterInterface $oDB)
    {
        $this->oDB = $oDB;
        $this->sTable = 'deployments';
        $this->sPK = 'exec_id';
    }

    /**
     * Build and execute an INSERT on $this->sTable filling specified columns.
     *
     * @param array $aParameters key-value list of columns and values to insert
     */
    public function insert(array $aParameters)
    {
        if (count($aParameters) > 0) {
            $sColumns = implode(', ', array_keys($aParameters));
            $sPlaceHolders = implode(',', array_fill(0, count($aParameters), '?'));
            $sQuery = "INSERT INTO $this->sTable ($sColumns) VALUES ($sPlaceHolders)";
            /* @var $oStmt \PDOStatement */
            $oStmt = $this->oDB->prepare($sQuery);
            $oStmt->execute(array_values($aParameters));
        }
    }

    /**
     * Build and execute a SELECT, and return all rows.
     *
     * @param array $aFilter in conjunctive normal form (CNF), i.e. a conjunction of clauses where clauses
     * are disjunction of literals:
     *     <filter>  := array(<clause>, …)
     *     <clause>  := array(<literal>, …)
     *     <literal> := array('column' => 'value')
     * @param array $aOrderBy list of 'column ASC|DESC'
     * @param int $iLimit
     * @param int $iOffset
     * @return array all rows with \PDO::FETCH_ASSOC mode
     */
    public function select(array $aFilter, $aOrderBy = array(), $iLimit = 100, $iOffset = 0)
    {
        // WHERE clause:
        if (count($aFilter) > 0) {
            $aWhere = array();
            $aParameters = array();
            foreach ($aFilter as $aClauses) {
                $aClause = array();
                foreach ($aClauses as $aLiteral) {
                    list($sColumn, $sValue) = each($aLiteral);
                    $aClause[] = "$sColumn=?";
                    $aParameters[] = $sValue;
                }
                $aWhere[] = implode(' OR ', $aClause);
            }
            $sWhere = 'WHERE (' . implode(') AND (', $aWhere) . ')';
        } else {
            $sWhere = '';
            $aParameters = array();
        }

        // ORDER BY clause:
        if (count($aOrderBy) > 0) {
            $sOrderBy = 'ORDER BY ' . implode(', ', $aOrderBy);
        } else {
            $sOrderBy = '';
        }

        $sQuery = "SELECT * FROM $this->sTable $sWhere $sOrderBy LIMIT $iLimit OFFSET $iOffset";
        /* @var $oStmt \PDOStatement */
        $oStmt = $this->oDB->prepare($sQuery);
        $oStmt->execute($aParameters);
        $aResult = $oStmt->fetchAll(\PDO::FETCH_ASSOC);
        return $aResult;
    }

    /**
     * Build and execute an UPDATE on $this->sTable.
     *
     * @param array $aParameters key-value list of columns and values to update, including primary key value.
     * @throws \RuntimeException if no value for primary key is found in $aParameters.
     */
    public function update(array $aParameters)
    {
        if (empty($aParameters[$this->sPK])) {
            $sMsg = "Missing primary key '$this->sPK' in parameters: " . print_r($aParameters, true);
            throw new \RuntimeException($sMsg);
        } elseif (count($aParameters) > 1) {
            $sPKValue = $aParameters[$this->sPK];
            unset($aParameters[$this->sPK]);
            $sPlaceHolders = implode('=?, ', array_keys($aParameters)) . '=?';
            $aParameters[] = $sPKValue;
            $sQuery = "UPDATE $this->sTable SET $sPlaceHolders WHERE $this->sPK=?";
            /* @var $oStmt \PDOStatement */
            $oStmt = $this->oDB->prepare($sQuery);
            $oStmt->execute(array_values($aParameters));
        }
    }
}
