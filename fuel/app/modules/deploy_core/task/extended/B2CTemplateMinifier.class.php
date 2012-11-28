<?php
namespace Fuel\Tasks;

/**
 * Concatène et compresse les fichiers JS et CSS selon les blocs {combine} présents dans les templates.
 * Un seul template peut donc générer plusieurs compilations de JS et de CSS.
 * Ces paquets sont ensuite dupliqués sur les différents sous-domaines (' ', 'c', 'cn').
 * Enfin redistribue les URLs des images au format #s0.c4tw.net/([^\'”)]*)/([^\'”)]*)\.(png|gif|jpg)#i
 * sur les serveurs de statique s0 et s1, en respectant les sous-domaines.
 *
 * Exemple de bloc {combine} de fichier .tpl :
 * {combine compress=true}
 *     <script type="text/javascript" src="/js/google/analytics_controllerv4.js"></script>
 *     ...
 * {/combine}
 * Ou encore :
 * {combine compress=true}
 *     <link media="all" href="/css/search/noscript.css" rel="stylesheet" type="text/css" />
 *     ...
 * {/combine}
 *
 * Exemple d'URL modifiée, ici dans du CSS :
 * - avant : background:url(http://s0.c4tw.net/images/sprites/search.png) no-repeat;
 * - après : background:url(http://s1cn.c4tw.net/20110914184627_12723/webv4/css/images/sprites/search.png) no-repeat;
 *
 * Dans chaque archive JS ou CSS générée est inséré en tout début une ligne commentée
 * renseignant sur les fichiers sources.
 *
 * À inclure dans une tâche env ou target.
 *
 * Attributs :
 * - 'tpldir' : répertoire contenant les templates
 * - 'cssparentdir' : répertoire hébergeant les CSS sources
 * - 'jsparentdir' : répertoire hébergeant les JS sources
 * - 'destdir' : répertoire de destination des JS et CSS compactés
 * - 'imgoutpath' : sous-répertoire devant être inséré dans les URLs des images
 *
 * Exemples :
 * <tplminify tpldir="${TMPDIR}/webv4/templates" cssparentdir="${TMPDIR}/webv4"
 *     jsparentdir="${TMPDIR}/webv4" destdir="${TMPDIR}" imgoutpath="/${EXECUTION_ID}/webv4" />
 *
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_B2CTemplateMinifier extends Task
{

    /**
     * Instance Minifier_Interface.
     * @var Minifier_Interface
     */
    private $_oTplMinifier;

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'tplminify';
    }

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
            'tpldir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'cssparentdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'jsparentdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'destdir' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER,
            'imgoutpath' => AttributeProperties::DIR | AttributeProperties::REQUIRED
                | AttributeProperties::ALLOW_PARAMETER
        );
        $oMinifier = Minifier_Factory::getInstance(Minifier_Factory::TYPE_JSMIN, $this->_oShell);
        $this->_oTplMinifier = new Minifier_TemplateMinifier($oMinifier, $this->_oLogger);
    }

    /**
     * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
     * Lance une exception si tel n'est pas le cas.
     *
     * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
     * doit permettre de remonter au plus tôt tout dysfonctionnement.
     * Appelé avant la méthode execute().
     *
     * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
     * @throws DomainException en cas de valeur non permise
     */
    public function check ()
    {
        parent::check();

        // Suppression de l'éventuel slash terminal :
        $this->_aAttributes['tpldir'] = preg_replace('#/$#', '', $this->_aAttributes['tpldir']);
        $this->_aAttributes['cssparentdir'] = preg_replace('#/$#', '', $this->_aAttributes['cssparentdir']);
        $this->_aAttributes['jsparentdir'] = preg_replace('#/$#', '', $this->_aAttributes['jsparentdir']);
        $this->_aAttributes['destdir'] = preg_replace('#/$#', '', $this->_aAttributes['destdir']);
        $this->_aAttributes['imgoutpath'] = '/' . preg_replace('#^/|/$#', '', $this->_aAttributes['imgoutpath']);
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $sMsg = "Minify '" . $this->_aAttributes['tpldir'] . "' templates directory into '"
              . $this->_aAttributes['destdir'] . "'.";
        $this->_oLogger->log($sMsg);
        $this->_oLogger->indent();

        $sTplDir = $this->_processSimplePath($this->_aAttributes['tpldir']);
        $sCSSParentDir = $this->_processSimplePath($this->_aAttributes['cssparentdir']);
        $sJSParentDir = $this->_processSimplePath($this->_aAttributes['jsparentdir']);
        $sDestDir = $this->_processSimplePath($this->_aAttributes['destdir']);
        $sImgOutPath = $this->_processSimplePath($this->_aAttributes['imgoutpath']);
        $this->_oTplMinifier->process($sTplDir, $sCSSParentDir, $sJSParentDir, $sDestDir, $sImgOutPath);

        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
