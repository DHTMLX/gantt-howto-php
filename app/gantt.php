<?php

function getConnection()
{
	return new PDO("mysql:host=localhost;dbname=gantt", "root", "root", [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	]);
}

function getGanttData($request, $response, $args)
{
	$db = getConnection();
	$result = [
		"data" => [],
		"links" => []
	];

	foreach ($db->query("SELECT * FROM gantt_tasks ORDER BY sortorder ASC") as $row) {
		$row["open"] = true;
		array_push($result["data"], $row);
	}

	foreach ($db->query("SELECT * FROM gantt_links") as $link) {
		array_push($result["links"], $link);
	}
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
};


// getting a task from the request data
function getTask($data)
{
	return [
		":text" => $data["text"],
		":start_date" => $data["start_date"],
		":duration" => $data["duration"],
		":progress" => isset($data["progress"]) ? $data["progress"] : 0,
		":parent" => $data["parent"]
	];
}

// getting a link from the request data
function getLink($data)
{
	return [
		":source" => $data["source"],
		":target" => $data["target"],
		":type" => $data["type"]
	];
}

// create a new task
function addTask($request, $response, $args)
{
	$task = getTask($request->getParsedBody());
	$db = getConnection();
	$maxOrderQuery = "SELECT MAX(sortorder) AS maxOrder FROM gantt_tasks";
	$statement = $db->prepare($maxOrderQuery);
	$statement->execute();

	$maxOrder = $statement->fetchColumn();
	if (!$maxOrder)
		$maxOrder = 0;

	$task[":sortorder"] = $maxOrder + 1;

	$query = "INSERT INTO gantt_tasks(text, start_date, duration, progress, parent, sortorder) " .
		"VALUES (:text, :start_date, :duration, :progress, :parent, :sortorder)";
	$db->prepare($query)->execute($task);

	$result = [
		"action" => "inserted",
		"tid" => $db->lastInsertId()
	];
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
}

// update a task
function updateTask($request, $response, $args)
{
	$sid = $request->getAttribute("id");
	parse_str(file_get_contents("php://input"), $body);
	$task = [
		"text" => $body["text"],
		"start_date" => $body["start_date"],
		"duration" => $body["duration"],
		"progress" => $body["progress"],
		"parent" => $body["parent"],
		"sortorder" => $body["sortorder"]
	];
	$db = getConnection();
	$query = "UPDATE gantt_tasks " .
		"SET text = :text, start_date = :start_date, duration = :duration," .
		"progress = :progress, parent = :parent, sortorder = :sortorder " .
		"WHERE id = :sid";
	$db->prepare($query)->execute(array_merge($task, [":sid" => $sid]));

	if (isset($params["target"]) && $params["target"])
		updateOrder($sid, $params["target"], $db);

	$result = [
		"action" => "updated"
	];
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
}

function updateOrder($taskId, $target, $db)
{
	$nextTask = false;
	$targetId = $target;

	if (strpos($target, "next:") === 0) {
		$targetId = substr($target, strlen("next:"));
		$nextTask = true;
	}

	if ($targetId == "null")
		return;

	$sql = "SELECT sortorder FROM gantt_tasks WHERE id = :id";
	$statement = $db->prepare($sql);
	$statement->execute([":id" => $targetId]);

	$targetOrder = $statement->fetchColumn();
	if ($nextTask)
		$targetOrder++;

	$sql = "UPDATE gantt_tasks SET sortorder = sortorder + 1 " .
		"WHERE sortorder >= :targetOrder";
	$statement = $db->prepare($sql);
	$statement->execute([":targetOrder" => $targetOrder]);

	$sql = "UPDATE gantt_tasks SET sortorder = :targetOrder WHERE id = :taskId";
	$statement = $db->prepare($sql);
	$statement->execute([
		":targetOrder" => $targetOrder,
		":taskId" => $taskId
	]);
}


// delete a task
function deleteTask($request, $response, $args)
{
	$sid = $request->getAttribute("id");
	$db = getConnection();
	$query = "DELETE FROM gantt_tasks WHERE id = :sid";

	$db->prepare($query)->execute([":sid" => $sid]);
	$result = [
		"action" => "deleted"
	];
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
}

// create a new link
function addLink($request, $response, $args)
{
	$link = getLink($request->getParsedBody());
	$db = getConnection();
	$query = "INSERT INTO gantt_links(source, target, type) " .
		"VALUES (:source,:target,:type)";
	$db->prepare($query)->execute($link);

	$result = [
		"action" => "inserted",
		"tid" => $db->lastInsertId()
	];
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
}

// update a link
function updateLink($request, $response, $args)
{
	$sid = $request->getAttribute("id");
	parse_str(file_get_contents("php://input"), $body);
	$link = [
		"source" => $body["source"],
		"target" => $body["target"],
		"type" => $body["type"]
	];
	$db = getConnection();
	$query = "UPDATE gantt_links SET " .
		"source = :source, target = :target, type = :type " .
		"WHERE id = :sid";

	$db->prepare($query)->execute(array_merge($link, [":sid" => $sid]));
	$result = [
		"action" => "updated"
	];
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
}

// delete a link
function deleteLink($request, $response, $args)
{
	$sid = $request->getAttribute("id");
	$db = getConnection();
	$query = "DELETE FROM gantt_links WHERE id = :sid";

	$db->prepare($query)->execute([":sid" => $sid]);
	$result = [
		"action" => "deleted"
	];
	$payload = json_encode($result);

	$response->getBody()->write($payload);
	return $response->withHeader("Content-Type", "application/json");
}
