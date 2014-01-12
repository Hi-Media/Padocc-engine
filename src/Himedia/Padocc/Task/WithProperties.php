<?php

namespace Himedia\Padocc\Task;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Task\Base\Project;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Extended\TwengaServers;

/**
 * Couche permettant aux tâches l'implémentant d'importer des propriétés issues de fichiers de configuration INI,
 * de fichiers de configuration shell ou encore de la liste des groupes de serveurs Twenga.
 * Ces propriétés seront ensuite accessibles via $this->oProperties, instance de PropertiesInterface.
 *
 * Toute tâche dérivant Task_WithProperties se voit ainsi dotée de trois attributs XML optionnels et
 * cumulables : 'loadtwengaservers', 'propertyshellfile' et 'propertyinifile'.
 * Les voici illustrés dans l'ordre dans lequel ils sont traités si présents au sein de la même tâche :
 * - 'loadtwengaservers', "true" ou "false" (défaut), chargera la liste des groupes de serveurs Twenga
 * - 'propertyshellfile', chemin système, par ex. "/path/to/shell_file.cfg", importera en tant que propriétés
 * 		les variables du fichier de configuration shell
 * - 'propertyinifile', chemin système, par ex. "/path/to/config.ini", importera en tant que propriétés
 * 		les variables du fichier .INI
 *
 * Voir TwengaServers et PropertiesInterface pour plus de détails.
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
abstract class WithProperties extends Task
{

    /**
     * Tâche de chargement des listes de serveurs Twenga sous-jacente.
     * @var TwengaServers
     */
    private $oTwengaServersTask;

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
        $this->_aAttrProperties = array(
            'loadtwengaservers' => AttributeProperties::BOOLEAN,
            'propertyshellfile' => AttributeProperties::SRC_PATH,
            'propertyinifile' => AttributeProperties::SRC_PATH
        );

        // Création de la tâche de chargement des listes de serveurs Twenga sous-jacente :
        if (! empty($this->aAttValues['loadtwengaservers']) && $this->aAttValues['loadtwengaservers'] == 'true') {
            $this->oNumbering->addCounterDivision();
            $this->oTwengaServersTask = TwengaServers::getNewInstance(
                array(), $oProject, $oDIContainer
            );
            $this->oNumbering->removeCounterDivision();
        } else {
            $this->oTwengaServersTask = null;
        }
    }

    /**
     * Lors de l'exécution de la tâche, charge les propriétés des éventuels fichiers de configuration INI,
     * fichiers de configuration shell ou encore la liste des groupes de serveurs Twenga.
     */
    private function loadProperties ()
    {
        if (! empty($this->aAttValues['loadtwengaservers']) && $this->aAttValues['loadtwengaservers'] == 'true') {
            $this->oTwengaServersTask->execute();
        }
        if (! empty($this->aAttValues['propertyshellfile'])) {
            $this->oLogger->info('Load shell properties: ' . $this->aAttValues['propertyshellfile'] . '+++');
            $this->oProperties->loadConfigShellFile($this->aAttValues['propertyshellfile']);
            $this->oLogger->info('---');
        }
        if (! empty($this->aAttValues['propertyinifile'])) {
            $this->oLogger->info('Load ini properties: ' . $this->aAttValues['propertyinifile']);
            $this->oProperties->loadConfigIniFile($this->aAttValues['propertyinifile']);
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        if ($this->oTwengaServersTask !== null) {
            $this->oLogger->info('+++');
            $this->oTwengaServersTask->setUp();
            $this->oLogger->info('---');
        }
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::preExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function preExecute ()
    {
        parent::preExecute();
        $this->oLogger->info('+++');
        $this->loadProperties();
        $this->oLogger->info('---');
    }
}
