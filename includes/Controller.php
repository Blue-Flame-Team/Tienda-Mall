<?php
/**
 * Base Controller Class
 * Provides common functionality for all API controllers
 */

class Controller {
    protected $request;
    protected $response;
    protected $method;
    protected $contentType;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        $this->parseRequest();
        $this->response = [
            'status' => 'error',
            'message' => '',
            'data' => null
        ];
    }
    
    /**
     * Parse the incoming request
     */
    protected function parseRequest() {
        $this->request = [];
        
        // Parse JSON input
        $input = file_get_contents('php://input');
        if (!empty($input) && strpos($this->contentType, 'application/json') !== false) {
            $this->request = json_decode($input, true) ?? [];
        }
        
        // Merge with $_POST and $_GET
        $this->request = array_merge($_GET, $_POST, $this->request);
    }
    
    /**
     * Get a request parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Parameter value or default
     */
    protected function getParam($key, $default = null) {
        return isset($this->request[$key]) ? $this->request[$key] : $default;
    }
    
    /**
     * Get all request parameters
     * 
     * @return array All parameters
     */
    protected function getAllParams() {
        return $this->request;
    }
    
    /**
     * Check if the request has a specific parameter
     * 
     * @param string $key Parameter key
     * @return bool Whether parameter exists
     */
    protected function hasParam($key) {
        return isset($this->request[$key]);
    }
    
    /**
     * Send a success response
     * 
     * @param array $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     */
    protected function respondSuccess($data = null, $message = 'Success', $statusCode = 200) {
        $this->response['status'] = 'success';
        $this->response['message'] = $message;
        $this->response['data'] = $data;
        
        $this->sendResponse($statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param array $data Additional error data
     * @param int $statusCode HTTP status code
     */
    protected function respondError($message = 'Error', $data = null, $statusCode = 400) {
        $this->response['status'] = 'error';
        $this->response['message'] = $message;
        $this->response['data'] = $data;
        
        $this->sendResponse($statusCode);
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message Not found message
     */
    protected function respondNotFound($message = 'Resource not found') {
        $this->respondError($message, null, 404);
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string $message Unauthorized message
     */
    protected function respondUnauthorized($message = 'Unauthorized access') {
        $this->respondError($message, null, 401);
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string $message Forbidden message
     */
    protected function respondForbidden($message = 'Access forbidden') {
        $this->respondError($message, null, 403);
    }
    
    /**
     * Send a validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Validation error message
     */
    protected function respondValidationError($errors, $message = 'Validation failed') {
        $this->respondError($message, ['errors' => $errors], 422);
    }
    
    /**
     * Send the response
     * 
     * @param int $statusCode HTTP status code
     */
    protected function sendResponse($statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->response);
        exit;
    }
    
    /**
     * Validate request parameters against rules
     * 
     * @param array $rules Validation rules
     * @return array Validation errors (empty if valid)
     */
    protected function validate($rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleParts = explode('|', $rule);
            $value = $this->getParam($field);
            
            foreach ($ruleParts as $rulePart) {
                // Check if rule has parameters
                if (strpos($rulePart, ':') !== false) {
                    list($ruleName, $ruleParam) = explode(':', $rulePart, 2);
                } else {
                    $ruleName = $rulePart;
                    $ruleParam = null;
                }
                
                switch ($ruleName) {
                    case 'required':
                        if (!isset($this->request[$field]) || $value === '') {
                            $errors[$field][] = "$field is required";
                        }
                        break;
                        
                    case 'email':
                        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "$field must be a valid email address";
                        }
                        break;
                        
                    case 'numeric':
                        if ($value !== '' && !is_numeric($value)) {
                            $errors[$field][] = "$field must be a number";
                        }
                        break;
                        
                    case 'integer':
                        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field][] = "$field must be an integer";
                        }
                        break;
                        
                    case 'min':
                        if (is_string($value) && strlen($value) < $ruleParam) {
                            $errors[$field][] = "$field must be at least $ruleParam characters";
                        } elseif (is_numeric($value) && $value < $ruleParam) {
                            $errors[$field][] = "$field must be at least $ruleParam";
                        }
                        break;
                        
                    case 'max':
                        if (is_string($value) && strlen($value) > $ruleParam) {
                            $errors[$field][] = "$field must be at most $ruleParam characters";
                        } elseif (is_numeric($value) && $value > $ruleParam) {
                            $errors[$field][] = "$field must be at most $ruleParam";
                        }
                        break;
                        
                    case 'in':
                        $allowedValues = explode(',', $ruleParam);
                        if ($value !== '' && !in_array($value, $allowedValues)) {
                            $errors[$field][] = "$field must be one of: " . $ruleParam;
                        }
                        break;
                }
            }
        }
        
        // Flatten error array
        foreach ($errors as $field => $fieldErrors) {
            $errors[$field] = $fieldErrors[0];
        }
        
        return $errors;
    }
    
    /**
     * Check if the current user is authenticated
     * 
     * @return bool Whether user is authenticated
     */
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get the ID of the authenticated user
     * 
     * @return int|null User ID or null if not authenticated
     */
    protected function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Require authentication to access a resource
     */
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->respondUnauthorized();
        }
    }
}
?>
