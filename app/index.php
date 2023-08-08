<?php

include_once("db.php");

$db = new Database();
if ($db->connect() != true) {
    $responseData = array("message" => "Unable to establish database connection.");
    http_response_code(500); // HTTP 500: Internal Server Error
    echo json_encode($responseData);
    exit;
}
// Run if sample DB and data is needed
$db->_bootstrapDB();

// All API endpoints require user account, check if API token is supplied
$headers = apache_request_headers();
if (empty($headers["Authorization"])) {
    $responseData = array("message" => "No API token supplied.");
    http_response_code(400); // HTTP 400: Bad request
    echo json_encode($responseData);
    exit;
}
if ($db->checkToken($headers["Authorization"]) == false) {
    $responseData = array("message" => "Supplied API token is not valid.");
    http_response_code(401); // HTTP 401: Unauthorized
    echo json_encode($responseData);
    exit;
}

$request = explode("/", $_SERVER["REQUEST_URI"]);

// Set header, same for all cases - always returning JSON
header('Content-Type: application/json; charset=utf-8');

// Check if correct endpoint is used
if ($request[1] != "product") {
    $responseData = array("message" => "Unknown endpoint.");
    http_response_code(404); // HTTP 404: Not found
    echo json_encode($responseData);
    exit;
}

// Check if product id is supplied, and if yes, is it numeric value
if (!empty($request[2])) {
    if (!is_numeric($request[2])) {
        $responseData = array("message" => "Product ID must be numeric.");
        http_response_code(400); // HTTP 400: Bad request
        echo json_encode($responseData);
        exit;
    }
    $productID = $request[2];
}

// Get the HTTP method used in the request
$method = $_SERVER["REQUEST_METHOD"];

// Route the request
switch ($method) {
    case 'GET':
        // One product should be returned
        if (!empty($productID)) {
            $responseData = $db->getProduct($productID);

            http_response_code(200); // HTTP 200: OK

            // If response contains "message" key, no product data is present
            if (array_key_exists("message", $responseData)) {
                /*
                The response below will be returned in case product with given ID
                does not exist, or if error was encountered in executing the 
                underlying query to generate product data. This is limitation of
                PDO prepared statement fetch() function. See Return Values section:
                https://www.php.net/manual/en/pdostatement.fetch.php
                */
                http_response_code(404); // HTTP 404: Not found
            }
        }
        // All product should be returned
        else {
            $responseData = $db->getAllProducts();

            http_response_code(200); // HTTP 200: OK
            
            // If response contains "message" key, no product data is present
            if (array_key_exists("message", $responseData)) {
                /*
                The response below will be returned in case product with given ID
                does not exist, or if error was encountered in executing the 
                underlying query to generate product data. This is limitation of
                PDO prepared statement fetch() function. See Return Values section:
                https://www.php.net/manual/en/pdostatement.fetch.php
                */
                http_response_code(404); // HTTP 404: Not found
            }
        }

        echo json_encode($responseData);
        break;
    
    case 'POST':
        $requestContent = file_get_contents("php://input");

        // No POST data, no way to create a new DB record
        if (empty($requestContent)) {
            $responseData = array("message" => "No product data supplied in request.");
            http_response_code(400); // HTTP 400: Bad request
            echo json_encode($responseData);
            exit;
        }

        // POST data present, create a new DB record
        $requestData = json_decode($requestContent, true);
        $responseData = $db->addProduct(
            $requestData["productEANCodes"],
            $requestData["productName"],
            $requestData["productManufacturer"],
            $requestData["productCategory"],
            $requestData["productPrice"]
        );

        // Check if new record was successfully created and respond accordingly
        // If response contains "message" key, no product data is present
        if (array_key_exists("message", $responseData)) {
            http_response_code(400); // HTTP 400: Bad request
            echo json_encode($responseData);
            exit;
        }
        else {
            http_response_code(201); // HTTP 201: Created
            echo json_encode($responseData);
        }
        break;
        
    case 'PUT':
        // Can't update product without product ID
        if (empty($productID)) {
            $responseData = array("message" => "Product ID must be supplied for delete requests.");
            http_response_code(400); // HTTP 400: Bad request
            echo json_encode($responseData);
            exit;
        }
        
        // Get request data
        $requestContent = file_get_contents("php://input");

        // No request data, no way to update a record
        if (empty($requestContent)) {
            $responseData = array("message" => "No product data supplied in request.");
            http_response_code(400); // HTTP 400: Bad request
            echo json_encode($responseData);
            exit;
        }

        // request data present, update a new DB record
        $requestData = json_decode($requestContent, true);
        $responseData = $db->updateProduct(
            $productID,
            $requestData
        );

        // Check if new record was successfully updated and respond accordingly
        // If response contains "message" key, no product data is present
        if (array_key_exists("message", $responseData)) {
            http_response_code(400); // HTTP 400: Bad request
            echo json_encode($responseData);
            exit;
        }
        else {
            http_response_code(200); // HTTP 200: OK
            echo json_encode($responseData);
        }

        break;

    case 'DELETE':
        // Can't delete product without product ID
        if (empty($productID)) {
            $responseData = array("message" => "Product ID must be supplied for delete requests.");
            http_response_code(400); // HTTP 400: Bad request
            echo json_encode($responseData);
            exit;
        }

        // Product ID supplied, delete the product
        $responseData = $db->deleteProduct($productID);

        // Check if new record was successfully deleted and respond accordingly
        // If response contains "message" key, no product data is present
        if (array_key_exists("message", $responseData)) {
            http_response_code(400); // HTTP 400: Bad request
        }
        else {
            http_response_code(200); // HTTP 400: Bad request
        }
        echo json_encode($responseData);

        break;

    default:
        // Ideally, this code should not run :)
        $responseData = array("message" => "Unknown request received.");
        http_response_code(400); // HTTP 400: Bad request
        echo json_encode($responseData);
        break;
}

?>