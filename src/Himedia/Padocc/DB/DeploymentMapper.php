<?php

namespace Himedia\Padocc\DB;

/**
 * Deployment entity
 */
class DeploymentMapper
{
//    public static $TABLE = array(
//        'exec_id'             => \PDO::PARAM_STR,
//        'xml_path'            => \PDO::PARAM_STR,
//        'project_name'        => \PDO::PARAM_STR,
//        'env_name'            => \PDO::PARAM_STR,
//        'external_properties' => \PDO::PARAM_STR,
//        'status'              => \PDO::PARAM_STR,
//        'date_queue'          => \PDO::PARAM_STR,
//        'date_start'          => \PDO::PARAM_STR,
//        'date_end'            => \PDO::PARAM_STR,
//        'is_rollbackable'     => \PDO::PARAM_BOOL
//    );

    /**
     * @var DBAdapterInterface
     */
    private $oDB;

    public function __construct (DBAdapterInterface $oDB)
    {
        $this->oDB = $oDB;
    }

    public function insert (array $aParameters)
    {
        $sColumns = implode(', ', array_keys($aParameters));
        $sPlaceHolders = implode(',', array_fill(0, count($aParameters), '?'));
        $sQuery = "INSERT INTO deployments ($sColumns) VALUES ($sPlaceHolders)";
        /* @var $oStmt \PDOStatement */
        $oStmt = $this->oDB->prepare($sQuery);
        $oStmt->execute(array_values($aParameters));
    }

    /**
     * @param array $aFilter in conjunctive normal form (CNF)
     */
    public function select (array $aFilter, $aOrderBy = array(), $iLimit = 100, $iOffset = 0)
    {
        // WHERE clause:
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
        $sWhere = '(' . implode(') AND (', $aWhere) . ')';

        // ORDER BY clause:
        if (count($aOrderBy) > 0) {
            $sOrderBy = 'ORDER BY ' . implode(', ', $aOrderBy);
        } else {
            $sOrderBy = '';
        }

        $sQuery = "SELECT * FROM deployments WHERE $sWhere $sOrderBy LIMIT $iLimit OFFSET $iOffset";
        var_dump($sQuery);
        /* @var $oStmt \PDOStatement */
        $oStmt = $this->oDB->prepare($sQuery);
        $oStmt->execute($aParameters);
        $aResult = $oStmt->fetchAll(\PDO::FETCH_ASSOC);
        return $aResult;
    }

    public function update (array $aParameters)
    {
        $sExecId = $aParameters['exec_id'];
        unset($aParameters['exec_id']);
        $sPlaceHolders = implode('=? ,', array_keys($aParameters)) . '=?';
        $aParameters[] = $sExecId;
        $sQuery = "UPDATE deployments SET $sPlaceHolders WHERE exec_id=?";
        /* @var $oStmt \PDOStatement */
        $oStmt = $this->oDB->prepare($sQuery);
        $oStmt->execute(array_values($aParameters));
    }
}
