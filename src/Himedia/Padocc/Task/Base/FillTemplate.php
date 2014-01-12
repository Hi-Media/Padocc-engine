<?php

namespace Himedia\Padocc\Task\Base;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task;
use Psr\Log\LoggerInterface;

/**
 * Permet de générer des fichiers sur la base de templates texte incluant des propriétés.
 * Exemple, ici PHP :
 * <?php
 *     define('DEPLOY_PROJECT', '${PROJECT_NAME}');
 *     define('DEPLOY_ENV', '${ENVIRONMENT_NAME}');
 *     define('DEPLOY_EXECUTION_ID', '${EXECUTION_ID}');
 *     define('TWENGABUILD', '${EXECUTION_ID}');
 *     define('DEPLOY_BASEDIR', '${BASEDIR}');
 * Ce qui génèrera pour cet exemple :
 * <?php
 *     define('DEPLOY_PROJECT', 'front');
 *     define('DEPLOY_ENV', 'prod');
 *     define('DEPLOY_EXECUTION_ID', '20111221154051_01652');
 *     define('TWENGABUILD', '20111221154051_01652');
 *     define('DEPLOY_BASEDIR', '/home/httpd/www.twenga');
 *
 * N'importe quelle propriété y est adressable : celles de master_synchro.cfg si chargé,
 * celles provenant des tags property et celles venant des externalproperty.
 * À inclure dans une tâche env ou target.
 *
 * Exemple :
 * <filltemplate
 *     srcfile="${TMPDIR}/inc/deploy_config-template.inc.php"
 *     destfile="${TMPDIR}/inc/deploy_config.inc.php"
 * />
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class FillTemplate extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'filltemplate';
    }

    /**
     * Constructeur.
     *
     * @param \SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Project $oProject Super tâche projet.
     * @param DIContainer $oDIContainer Register de services prédéfinis (ShellInterface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Project $oProject, DIContainer $oDIContainer)
    {
        parent::__construct($oTask, $oProject, $oDIContainer);
        $this->aAttrProperties = array(
            'srcfile' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED
                | AttributeProperties::FILE,
            'destfile' => AttributeProperties::ALLOW_PARAMETER | AttributeProperties::REQUIRED
                | AttributeProperties::FILE,
        );
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws \UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws \DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();

        // Pour l'instant ne gère pas les chemins distants :
        list($bIsSrcRemote, , ) = $this->oShell->isRemotePath($this->aAttributes['srcfile']);
        list($bIsDestRemote, , ) = $this->oShell->isRemotePath($this->aAttributes['destfile']);
        if ($bIsSrcRemote || $bIsDestRemote) {
            throw new \DomainException('Remote paths not yet handled.');
        }
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();
        $sMsg = "+++Generate '" . $this->aAttributes['destfile'] . "' from '" . $this->aAttributes['srcfile'] . "'.";
        $this->oLogger->info($sMsg);

        $sSrcFile = $this->processSimplePath($this->aAttributes['srcfile']);
        $sDestFile = $this->processSimplePath($this->aAttributes['destfile']);
        $sContent = file_get_contents($sSrcFile);

        preg_match_all('/\$\{[^}]+\}/i', $sContent, $aMatches);
        $aParameters = array_unique($aMatches[0]);
        $aValues = array();
        foreach ($aParameters as $sParameter) {
            try {
                $sValue = $this->oProperties->getProperty(substr($sParameter, 2, -1));
                $aValues[] = addslashes($sValue);
            } catch (\UnexpectedValueException $oException) {
                $aValues[] = $sParameter;
                $sMsg = "[WARNING] Parameter '$sParameter' not resolved in '$sSrcFile' ("
                      . $oException->getMessage() . ").";
                $this->oLogger->warning($sMsg);
            }
        }
        $sContent = str_replace($aParameters, $aValues, $sContent);
        file_put_contents($sDestFile, $sContent);

        $this->oLogger->info('---');
    }
}
