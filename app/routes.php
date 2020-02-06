<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

// Add dhtmlxGantt CRUD
require __DIR__ . "/gantt.php";

return function (App $app) {
    $app->get("/", function (Request $request, Response $response) {
        $payload = file_get_contents(__DIR__ . "/templates/basic.html");
        $response->getBody()->write($payload);
        return $response;
    });

    $app->get("/data",  "getGanttData");

    $app->post("/data/task", "addTask");
    $app->put("/data/task/{id}", "updateTask");
    $app->delete("/data/task/{id}", "deleteTask");

    $app->post("/data/link", "addLink");
    $app->put("/data/link/{id}", "updateLink");
    $app->delete("/data/link/{id}", "deleteLink");
};
