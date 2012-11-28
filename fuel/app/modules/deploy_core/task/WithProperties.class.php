<?php
namespace Fuel\Tasks;
/**
 * Couche permettant aux tâches l'implémentant d'importer des propriétés issues de fichiers de configuration INI,
 * de fichiers de configuration shell ou encore de la liste des groupes de serveurs Twenga.
 * Ces propriétés seront ensuite accessibles via $this->_oProperties, instance de Properties_Interface.
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
 * Voir Task_Extended_TwengaServers et Properties_Interface pour plus de détails.
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
abstract class Task_WithProperties extends Task
{

    /**
     * Tâche de chargement des listes de serveurs Twenga sous-jacente.
     * @var Task_Extended_TwengaServers
     */
    private $_oTwengaServersTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (\SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array(
            'loadtwengaservers' => AttributeProperties::BOOLEAN,
            'propertyshellfile' => AttributeProperties::SRC_PATH,
            'propertyinifile' => AttributeProperties::SRC_PATH
        );

        // Création de la tâche de chargement des listes de serveurs Twenga sous-jacente :
        if ( ! empty($this->_aAttributes['loadtwengaservers']) && $this->_aAttributes['loadtwengaservers'] == 'true') {
            $this->_oNumbering->addCounterDivision();
            $this->_oTwengaServersTask = Task_Extended_TwengaServers::getNewInstance(
                array(), $oProject, $oServiceContainer
            );
            $this->_oNumbering->removeCounterDivision();
        } else {
            $this->_oTwengaServersTask = NULL;
        }
    }

    /**
     * Lors de l'exécution de la tâche, charge les propriétés des éventuels fichiers de configuration INI,
     * fichiers de configuration shell ou encore la liste des groupes de serveurs Twenga.
     */
    private function _loadProperties ()
    {
        if ( ! empty($this->_aAttributes['loadtwengaservers']) && $this->_aAttributes['loadtwengaservers'] == 'true') {
            $this->_oTwengaServersTask->execute();
        }
        if ( ! empty($this->_aAttributes['propertyshellfile'])) {
            $this->_oLogger->log('Load shell properties: ' . $this->_aAttributes['propertyshellfile']);
            $this->_oLogger->indent();
            $this->_oProperties->loadConfigShellFile($this->_aAttributes['propertyshellfile']);
            $this->_oLogger->unindent();
        }
        if ( ! empty($this->_aAttributes['propertyinifile'])) {
            $this->_oLogger->log('Load ini properties: ' . $this->_aAttributes['propertyinifile']);
            $this->_oProperties->loadConfigIniFile($this->_aAttributes['propertyinifile']);
        }
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        if ($this->_oTwengaServersTask !== NULL) {
            $this->_oLogger->indent();
            $this->_oTwengaServersTask->setUp();
            $this->_oLogger->unindent();
        }
    }

    /**
     * Phase de pré-traitements de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_preExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _preExecute ()
    {
        parent::_preExecute();
        $this->_oLogger->indent();
        $this->_loadProperties();
        $this->_oLogger->unindent();
    }
}
