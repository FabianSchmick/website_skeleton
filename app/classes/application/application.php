<?php

namespace application;

class Application
{
    /**
     * @var array $config Config array;
     */
    private $config = [];

    /**
     * @var array $routeConfig Config of the current route
     */
    private $routeConfig = [];

    /**
     * @var \database\PDODatabase $db PDO database object
     */
    private $db;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->config = new Config();
        $this->config->loadFromFile('config.inc.php');
        $this->routeConfig = new Config();
        $this->db = $this->initDbConnection();
    }

    /**
     * Runs the application
     */
    public function run()
    {
        $this->checkDebugMode();
    }

    /**
     * Initialises the routes defined in app/config/routes.inc.php
     */
    public function initRoutes()
    {
        $route = new Route();

        $routesConf = include __DIR__ . '/../../config/routes.inc.php';

        foreach ($routesConf as $routeConf) {

            $uri = $routeConf['uri'];

            if (preg_match_all('/\$(.*?(?=\/)|.*?$)/', $routeConf['uri'], $matches)) {
                $uri = preg_replace('/\$(.*?(?=\/)|.*?$)/', '.*', $routeConf['uri']);
            }

            $route->add($uri, $routeConf, $matches[1], function($params) {
                $this->routeConfig->initFromArr($params);

                /**
                 * TODO Better solution for backend stuff, thought about something like a controller
                 */
                if (isset($this->routeConfig->config['backend'])) {
                    $vars = require_once __DIR__ . "/../../../pages/backend/" . $this->routeConfig->config['backend'];
                } else {
                    $vars = [];
                }

                $this->renderTpl($this->routeConfig->config['template'], $vars);
            });
        }

        $findUrl = $route->submit();

        if (!$findUrl) {
            header('HTTP/1.0 404 Not Found');
            include_once __DIR__ . '/../../../pages/frontend/errors/404_de.html';
        }
    }

    /**
     * Renders a template
     *
     * @param string $tpl The template filename
     * @param array $vars The template variables
     */
    public function renderTpl($tpl, $vars = [])
    {
        // Init the content with optional variables
        $profile = new Template(__DIR__ . '/../../../pages/frontend/' . $tpl);
        $profile->set($vars);

        // Get the layout
        $layout = $this->renderLayout($this->routeConfig->config['layout']);

        // Put layout and content together
        if (!isset($this->routeConfig->config['title'])) {
            $layout['vars']['title'] = $this->routeConfig->config['name'];
        } else {
            $layout['vars']['title'] = $this->routeConfig->config['title'];
        }
        $layout['vars']['content'] = $profile->output();
        $layout['layout']->set($layout['vars']);

        echo $layout['layout']->output();
    }

    /**
     * Renders a layout for templates
     *
     * @param string $file The filename
     * @return array
     */
    public function renderLayout($file)
    {
        // Init the layout for the content
        $layoutVars = $this->config->$file;

        if (isset($layoutVars['header'])) {
            $header = new Template(__DIR__ . '/../../layout/' . $layoutVars['header']);
            $layoutVars['header'] = $header->output();
        }

        $layout = new Template(__DIR__ . '/../../layout/' . $file);

        // Define the layouts variables
        foreach ($layoutVars as $key => $layoutVar) {
            if (preg_match('/\w*(.css)/', $key)) {
                $layoutVars[$key] = preg_replace('/\w*(.css)/', '/../../..' . $this->config->assetsPath .'/css/'. $layoutVar, $key);
            }

            if (preg_match('/\w*(.js)/', $key)) {
                $layoutVars[$key] = preg_replace('/\w*(.js)/', '/../../..' . $this->config->assetsPath .'/js/'. $layoutVar, $key);
            }
        }

        return [
            'layout' => $layout,
            'vars'   => $layoutVars,
        ];
    }

    /**
     * Sets error reporting
     */
    public function checkDebugMode()
    {
        // Display debug messages
        if ($this->config->debug == true) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        } else {
            error_reporting(0);
            ini_set("display_errors", 0);
        }
    }

    /**
     * Include files
     *
     * @param array $files The files to be included
     */
    public function includeFiles($files = [])
    {
        foreach($files as $folder => $file_array) { 	// Loop Array
            foreach($file_array as $file) {
                include_once __DIR__ . "/../../" . $folder . "/" . $file; 	// Include files
            }
        }
    }

    /**
     * Database object initialization
     *
     * @return \PDO|string The Db connection|Error message
     */
    public function initDbConnection()
    {
        if (!empty($this->config->dbType) &&
            !empty($this->config->dbHost) &&
            !empty($this->config->dbName) &&
            !empty($this->config->dbUser) &&
            isset($this->config->dbPassword)) {

            $pdoDb = new \database\PDODatabase(
                $this->config->dbType,
                $this->config->dbHost,
                $this->config->dbName,
                $this->config->dbUser,
                $this->config->dbPassword
            );

            $pdoDb->initializePDOObject();
        } else {
            $pdoDb = "Error - no database connection configured.";
        }

        return $pdoDb;
    }
}