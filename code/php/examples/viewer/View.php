<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Taken from xfreemwork: http://sourceforge.net/projects/xfreemwork/
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Base view class.
 *
 * Responsibilities
 * - deals with rendering and internationalization (i18n)
 * @package xFreemwork
**/
class xView {

    /**
     * The template file,
     * optionally including full or relative path.
     * @var string
     */
    var $template;

    /**
     * The view data.
     * @var array
     */
    var $data = array();

    /**
     * View classes can only be instanciated through the View::load() method.
     * @param array An array of data to be merged to the view instance
     */
    function __construct($template, array $data=array()) {
        $this->template = $template;
        $this->data = array_merge_recursive($this->data, $data);
    }

    function create($template, array $data=array()) {
        return new xView($template, $data);
    }

    /**
     * Renders the given template with the given data.
     * @param string $template The filename of the template to use
              (e.g. tplfile.tpl).
     * @param mixed $data The data to be used within the template context.
              (defaults to instance data property).
     * @return string
     */
    protected function apply() {
        // Disables notices reporting in php template code
        $error_reporting = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE);
        // Create template-wide variables and functions
        $d = $this->data;
        // Loads the template and processes template tags
        if (!file_exists($this->template)) throw new Exception("Template file not found ({$this->template})");
        // Renders the template
        ob_start();
        require($this->template);
        $s = ob_get_contents();
        ob_end_clean();
        // Reverts error reporting level
        error_reporting($error_reporting);
        return $s;
    }

    /**
     * Retrun rendered view.
     * This method should be overridden in subclasses to
     * override the default tpl rendering.
     * @return string
     */
    function render() {
        return $this->apply();
    }
}