<?php
// public/api.php

// The entire application now runs from within the /public directory.
// We can use a simple relative path to include the configuration.
require_once __DIR__ . '/../config/config.php';

// This script acts as a simple router for API calls.
// It determines the controller and method from the URL and executes it.

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api/';
$requestPath = substr($requestUri, strlen($basePath));
$parts = explode('/', $requestPath);

$controllerName = ucfirst(strtolower($parts[0] ?? '')) . 'Controller';
$methodName = strtolower($parts[1] ?? '');

$controllerFile = __DIR__ . '/../src/controllers/' . $controllerName . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    if (class_exists($controllerName) && method_exists($controllerName, $methodName)) {
        $controller = new $controllerName();
        $controller->$methodName();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found.']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'API controller not found.']);
}