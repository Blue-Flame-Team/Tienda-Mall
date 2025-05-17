<?php
/**
 * Authentication API
 * Handles authentication-related API requests
 */

$userObj = new User();

switch ($method) {
    case 'POST':
        // Handle different authentication actions
        switch ($action) {
            case 'register':
                // Validate required fields
                if (empty($data['email']) || empty($data['password']) || 
                    empty($data['first_name']) || empty($data['last_name'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Missing required fields',
                        'data' => null
                    ];
                    break;
                }
                
                // Validate email format
                if (!validateEmail($data['email'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid email format',
                        'data' => null
                    ];
                    break;
                }
                
                // Register user
                $userId = $userObj->register($data);
                
                if ($userId) {
                    http_response_code(201);
                    $response = [
                        'status' => 'success',
                        'message' => 'User registered successfully',
                        'data' => [
                            'user_id' => $userId
                        ]
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Registration failed. Email may already be in use.',
                        'data' => null
                    ];
                }
                break;
                
            case 'login':
                // Validate required fields
                if (empty($data['email']) || empty($data['password'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Email and password are required',
                        'data' => null
                    ];
                    break;
                }
                
                // Login user
                $user = $userObj->login($data['email'], $data['password']);
                
                if ($user === 'inactive') {
                    http_response_code(403);
                    $response = [
                        'status' => 'error',
                        'message' => 'Your account has been deactivated',
                        'data' => null
                    ];
                } elseif ($user) {
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Login successful',
                        'data' => $user
                    ];
                } else {
                    http_response_code(401);
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid email or password',
                        'data' => null
                    ];
                }
                break;
                
            case 'logout':
                // Clear session
                session_unset();
                session_destroy();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Logout successful',
                    'data' => null
                ];
                break;
                
            case 'reset-password':
                // Request password reset
                if (empty($data['email'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Email is required',
                        'data' => null
                    ];
                    break;
                }
                
                $success = $userObj->requestPasswordReset($data['email']);
                
                if ($success) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Password reset link has been sent to your email',
                        'data' => null
                    ];
                } else {
                    http_response_code(404);
                    $response = [
                        'status' => 'error',
                        'message' => 'Email not found',
                        'data' => null
                    ];
                }
                break;
                
            case 'reset-password-confirm':
                // Complete password reset
                if (empty($data['token']) || empty($data['password'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Token and new password are required',
                        'data' => null
                    ];
                    break;
                }
                
                $success = $userObj->resetPassword($data['token'], $data['password']);
                
                if ($success) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Password has been reset successfully',
                        'data' => null
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid or expired token',
                        'data' => null
                    ];
                }
                break;
                
            case 'verify-email':
                // Verify email address
                if (empty($data['token'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Token is required',
                        'data' => null
                    ];
                    break;
                }
                
                $success = $userObj->verifyEmail($data['token']);
                
                if ($success) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Email verified successfully',
                        'data' => null
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid verification token',
                        'data' => null
                    ];
                }
                break;
                
            default:
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid action',
                    'data' => null
                ];
        }
        break;
        
    case 'GET':
        // Check if user is logged in
        if ($action === 'check') {
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $user = $userObj->getUserById($userId);
                
                if ($user) {
                    $response = [
                        'status' => 'success',
                        'message' => 'User is logged in',
                        'data' => [
                            'logged_in' => true,
                            'user' => $user
                        ]
                    ];
                } else {
                    // Clear invalid session
                    session_unset();
                    session_destroy();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'User is not logged in',
                        'data' => [
                            'logged_in' => false
                        ]
                    ];
                }
            } else {
                $response = [
                    'status' => 'success',
                    'message' => 'User is not logged in',
                    'data' => [
                        'logged_in' => false
                    ]
                ];
            }
        } else {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Invalid action',
                'data' => null
            ];
        }
        break;
        
    default:
        http_response_code(405);
        $response = [
            'status' => 'error',
            'message' => 'Method not allowed',
            'data' => null
        ];
}
?>
