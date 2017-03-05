<?php

namespace application;

class Application
{
    /**
     * @var array $config Config array;
     */
    private $config = [];

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->config = new Config('config.inc.php');
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
                require_once __DIR__ . '/../../../pages/' . $params['config']['target'];
            });
        }

        $findUrl = $route->submit();

        if (!$findUrl) {
            header('HTTP/1.0 404 Not Found');
            include_once __DIR__ . '/../../../pages/frontend/errors/404_de.html';
        }
    }

    /**
     * Sets error reporting
     */
    public function checkDebugMode()
    {
        // Display debug messages
        if ($this->config->debug) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        } else {
            error_reporting(0);
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

            $connection = $pdoDb->initializePDOObject();
        } else {
            $connection = "Error - no database connection configured.";
        }

        return $connection;
    }
}