<?php

namespace Himedia\Padocc\Task\Extended;

use Himedia\Padocc\AttributeProperties;
use Himedia\Padocc\DIContainer;
use Himedia\Padocc\Minifier\Factory;
use Himedia\Padocc\Minifier\MinifierInterface;
use Himedia\Padocc\Task;
use Himedia\Padocc\Task\Base\Project;

/**
 * Minifie/compacte un ou plusieurs fichiers soit JS soit CSS.
 * À inclure dans une tâche ''env'' ou ''target''.
 *
 * @author Geoffroy AUBRY <gaubry@hi-media.com>
 */
class Minifier extends Task
{

    /**
     * Instance MinifierInterface.
     * @var MinifierInterface
     */
    private $oMinifier;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'minify';
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
            'srcfile' => AttributeProperties::FILEJOKER | AttributeProperties::ALLOW_PARAMETER,
            'destfile' => AttributeProperties::FILE | AttributeProperties::ALLOW_PARAMETER
        );
        $this->oMinifier = Factory::getInstance(Factory::TYPE_JSMIN, $this->oShell);
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::centralExecute();".
     * Appelé par execute().
     * @see execute()
     */
    protected function centralExecute ()
    {
        parent::centralExecute();

        $sMsg = "+++Minify '" . $this->aAttValues['srcfile'] . "' into '" . $this->aAttValues['destfile'] . "'.";
        $this->oLogger->info($sMsg);

        $aSrcPaths = $this->processPath($this->aAttValues['srcfile']);
        $sDestPaths = $this->processSimplePath($this->aAttValues['destfile']);
        $this->oMinifier->minify($aSrcPaths, $sDestPaths);

        $this->oLogger->info('---');
    }
}
