<?php

require 'config.php';
require 'vendor/autoload.php';

use CanalTP\NavitiaStatExporter\Formatters;

function log_msg($message) {
    if (LOG_ACTIVE) print strftime("%Y-%m-%d %H:%M:%S") . " - $message" . PHP_EOL;
}

define('REQUESTS_PER_BLOCK', 1000);
define('LOG_ACTIVE', ($_ENV["LOG_ACTIVE"] == 1));

$dateArg = $_SERVER['argv'][1];

$startDate = \DateTime::createFromFormat('Y-m-d', $dateArg);
$endDate = \DateTime::createFromFormat('Y-m-d', $dateArg);

$pdoDsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $config['database']['host'], $config['database']['port'],  $config['database']['name']);

$dbConn = new \PDO($pdoDsn, $config['database']['user'], $config['database']['password']);

$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbConn->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY);
$dbConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

$dbConn->exec('set enable_seqscan to false');

$dbConn->beginTransaction(); // needed for cursor
log_msg("Cursor declaration");
$requestQuery = "DECLARE c_requests CURSOR FOR SELECT * FROM stat.requests where request_date >= :start_date and request_date < ( :end_date :: date) + INTERVAL '1 day' order by id";

$requestStmt = $dbConn->prepare($requestQuery);
$requestStmt->bindValue('start_date', $startDate->format('Y-m-d'));
$requestStmt->bindValue('end_date', $endDate->format('Y-m-d'));
$requestStmt->execute();

$requestFormatter = new Formatters\RequestFormatter;
$coverageFormatter = new Formatters\CoverageFormatter;
$errorFormatter = new Formatters\ErrorFormatter;
$infoResponseFormatter = new Formatters\InfoResponseFormatter;
$parameterFormatter = new Formatters\ParameterFormatter;
$interpretedParameterFormatter = new Formatters\InterpretedParameterFormatter;
$journeyRequestFormatter = new Formatters\JourneyRequestFormatter;
$journeyFormatter = new Formatters\JourneyFormatter;

log_msg("Opening file");
$filename = $config['file']['root_dir'] . '/' . $startDate->format('Y/m/d') . '/' . 'stat_log_' . $startDate->format('Ymd') . '.json.log';

if (! is_dir(dirname($filename))) {
    if (! mkdir(dirname($filename), 0777, true)) {
        fprintf(STDERR, 'Unable to create dir ' . dirname($filename));
        exit(1);
    }
}

$fh = fopen($filename, 'w');
if (!$fh) {
    fprintf(STDERR, 'Unable to open file ' . $filename);
    exit(1);
}

function fetchRequestsBlock($nbItems)
{
    global $dbConn;

    log_msg("Fetch $nbItems requests");

    $requestQuery = 'FETCH ' . $nbItems . ' FROM c_requests';
    $requestStmt = $dbConn->query($requestQuery);
    $requestsBlock = $requestStmt->fetchAll(PDO::FETCH_ASSOC);
    return $requestsBlock;
}

while (count($requestsBlock = fetchRequestsBlock(REQUESTS_PER_BLOCK)) > 0) {
    $requestIds = array_column($requestsBlock, 'id');

    log_msg("Retrieve coverages");
    $coveragesPerRequest = getCoveragesForRequests($requestIds);

    log_msg("Retrieve errors");
    $errorsPerRequest = getErrorsForRequests($requestIds);

    log_msg("Retrieve parameters");
    $parametersPerRequest = getParametersForRequests($requestIds);

    log_msg("Retrieve interpreted parameters");
    $interpretedParametersPerRequest = getInterpretedParametersForRequests($requestIds);

    log_msg("Retrieve journeys");
    $journeysPerRequest = getJourneysForRequests($requestIds);

    log_msg("Retrieve journey requests");
    $journeyRequestsPerRequest = getJourneyRequestsForRequests($requestIds);

    log_msg("Retrieve info response");
    $infoResponsesPerRequest = getInfoResponseForRequests($requestIds);

    log_msg("Retrieve journey sections");
    $journeySectionsPerRequest = getJourneySectionsForRequests($requestIds);

    log_msg("Retrieve filter");
    $interpretedParametersIds = [];
    foreach ($interpretedParametersPerRequest as $reqId => $interpretedParameters) {
        $interpretedParametersIds += array_map(function($elem) { return $elem['id']; }, $interpretedParameters);
    }
    $filtersPerInterpretedParams = getFiltersForInterpretedParameters($interpretedParametersIds);

    log_msg("Write request block to file");
    foreach($requestsBlock as $requestArray) {
        $request = $requestFormatter->format($requestArray);
        $request['coverages'] = $coverageFormatter->format($coveragesPerRequest[$requestArray['id']]);

        if (array_key_exists($requestArray['id'], $errorsPerRequest)) {
            $errors = $errorsPerRequest[$requestArray['id']];
            $request['error'] = $errorFormatter->format($errors);
        }

        if (array_key_exists($requestArray['id'], $infoResponsesPerRequest)) {
            $infoResponses = $infoResponsesPerRequest[$requestArray['id']];
            $request['info_response'] = $infoResponseFormatter->format($infoResponses);
        }

        if (array_key_exists($requestArray['id'], $parametersPerRequest)) {
            $parameters = $parametersPerRequest[$requestArray['id']];
            $request['parameters'] = $parameterFormatter->format($parameters);
        }

        if (array_key_exists($requestArray['id'], $interpretedParametersPerRequest)) {
            $interpretedParameters = $interpretedParametersPerRequest[$requestArray['id']];
            $request['interpreted_parameters'] = $interpretedParameterFormatter->format($interpretedParameters, $filtersPerInterpretedParams);
        }

        if (array_key_exists($requestArray['id'], $journeyRequestsPerRequest)) {
            $journeyRequests = $journeyRequestsPerRequest[$requestArray['id']];
            $request['journey_request'] = $journeyRequestFormatter->format($journeyRequests);
        }

        if (array_key_exists($requestArray['id'], $journeysPerRequest)) {
            $journeys = $journeysPerRequest[$requestArray['id']];
            $sections = isset($journeySectionsPerRequest[$requestArray['id']]) ? $journeySectionsPerRequest[$requestArray['id']] : [];
            $request['journeys'] = $journeyFormatter->format($journeys, $sections);
        }

        fputs($fh, json_encode($request) . PHP_EOL);
        //if (count($journeys) > 0) { die(); }
    }
}

fclose($fh);

$dbConn->exec('CLOSE c_requests');

$dbConn->rollBack(); // to close transaction


function getCoveragesForRequests(array $requestIds)
{
    global $dbConn;

    $coveragesQuery = "SELECT * from stat.coverages where request_id in (" . implode(',', $requestIds) . ")";

    $stmt = $dbConn->prepare($coveragesQuery);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getErrorsForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT * from stat.errors where request_id in (" . implode(',', $requestIds) . ")";

    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getParametersForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT * from stat.parameters where request_id in (" . implode(',', $requestIds) . ")";

    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getInterpretedParametersForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT * from stat.interpreted_parameters where request_id in (" . implode(',', $requestIds) . ") order by id";

    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getJourneysForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT st_x(first_pt_coord::geometry) as first_pt_x, st_y(first_pt_coord::geometry) as first_pt_y,
              st_x(last_pt_coord::geometry) as last_pt_x, st_y(last_pt_coord::geometry) as last_pt_y, *
              FROM stat.journeys
              WHERE request_id in (" . implode(',', $requestIds) . ")
              ORDER BY id";

    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getJourneyRequestsForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT * from stat.journey_request where request_id in (" . implode(',', $requestIds) . ")";

    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getInfoResponseForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT * from stat.info_response where request_id in (" . implode(',', $requestIds) . ")";

    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][] = $row;
    }
    return $result;
}

function getFiltersForRequest(array $requestIds)
{
    global $dbConn;

    $query = "SELECT * from stat.filter where interpreted_parameter_id in (select id from stat.interpreted_parameters where request_id in (" . implode(',', $requestIds) . ") )";
    $stmt = $dbConn->query($query);

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['interpreted_parameter_id']][] = $row;
    }

    return $result;
}

function getFiltersForInterpretedParameters(array $interpretedParametersIds)
{
    global $dbConn;

    $query = "SELECT * from stat.filter where interpreted_parameter_id in (" . implode(',', $interpretedParametersIds) . ")";
    $stmt = $dbConn->query($query);

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['interpreted_parameter_id']][] = $row;
    }

    return $result;
}

function getJourneySectionsForRequests(array $requestIds)
{
    global $dbConn;

    $query = "SELECT st_x(from_coord::geometry) as from_x, st_y(from_coord::geometry) as from_y,
          st_x(to_coord::geometry) as to_x, st_y(to_coord::geometry) as to_y, *
          from stat.journey_sections
          where request_id in (" . implode(',', $requestIds) . ")
          order by id";
    $stmt = $dbConn->prepare($query);
    $stmt->execute();

    $result = [];
    while(($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
        $result[$row['request_id']][$row['journey_id']][] = $row;
    }
    return $result;
}
