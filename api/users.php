<?php
/**
 * Users API
 * Handles user-related API requests
 */

$userObj = new User();

switch ($method) {
    case 'GET':
        // Get user profile or list of users (admin only)
        if (!isLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        $userId = getCurrentUserId();
        
        if ($id) {
            // Only allow users to access their own profile or admin to access any profile
            if ($id != $userId && !isAdminLoggedIn()) {
                http_response_code(403);
                $response = [
                    'status' => 'error',
                    'message' => 'Forbidden',
                    'data' => null
                ];
                break;
            }
            
            $user = $userObj->getUserById($id);
            
            if ($user) {
                // Don't expose sensitive data
                unset($user['password_hash']);
                unset($user['reset_token']);
                unset($user['reset_token_expiry']);
                unset($user['verification_token']);
                
                $response = [
                    'status' => 'success',
                    'message' => 'User profile retrieved',
                    'data' => $user
                ];
            } else {
                http_response_code(404);
                $response = [
                    'status' => 'error',
                    'message' => 'User not found',
                    'data' => null
                ];
            }
        } else {
            // List all users (admin only)
            if (!isAdminLoggedIn()) {
                http_response_code(403);
                $response = [
                    'status' => 'error',
                    'message' => 'Forbidden',
                    'data' => null
                ];
                break;
            }
            
            $db = Database::getInstance();
            
            // Pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            
            // Filters
            $filters = [];
            $whereClause = "";
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = sanitize($_GET['search']);
                $whereClause .= " WHERE (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                $filters[] = "%$search%";
                $filters[] = "%$search%";
                $filters[] = "%$search%";
            }
            
            // Count total users
            $countSql = "SELECT COUNT(*) as total FROM users" . $whereClause;
            $countStmt = $db->query($countSql, $filters);
            $total = $countStmt->fetch()['total'];
            
            // Get users
            $sql = "SELECT user_id, email, first_name, last_name, phone, date_of_birth, 
                    is_active, created_at, last_login, profile_image, email_verified 
                    FROM users" . $whereClause . 
                    " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            
            $filters[] = $limit;
            $filters[] = $offset;
            
            $stmt = $db->query($sql, $filters);
            $users = $stmt->fetchAll();
            
            $response = [
                'status' => 'success',
                'message' => 'Users retrieved',
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ];
        }
        break;
        
    case 'PUT':
        // Update user profile
        if (!isLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        $userId = getCurrentUserId();
        
        // Check if user is updating their own profile or if admin
        if ($id != $userId && !isAdminLoggedIn()) {
            http_response_code(403);
            $response = [
                'status' => 'error',
                'message' => 'Forbidden',
                'data' => null
            ];
            break;
        }
        
        // Validate email format if provided
        if (isset($data['email']) && !validateEmail($data['email'])) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Invalid email format',
                'data' => null
            ];
            break;
        }
        
        // Handle password change separately if provided
        if (isset($data['current_password']) && isset($data['new_password'])) {
            $success = $userObj->changePassword($id, $data['current_password'], $data['new_password']);
            
            if (!$success) {
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Current password is incorrect',
                    'data' => null
                ];
                break;
            }
            
            // Remove password fields to prevent them from being updated in profile
            unset($data['current_password']);
            unset($data['new_password']);
        }
        
        // Update profile
        $success = $userObj->updateProfile($id, $data);
        
        if ($success) {
            $user = $userObj->getUserById($id);
            
            // Don't expose sensitive data
            unset($user['password_hash']);
            unset($user['reset_token']);
            unset($user['reset_token_expiry']);
            unset($user['verification_token']);
            
            $response = [
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $user
            ];
        } else {
            $response = [
                'status' => 'success',
                'message' => 'No changes made to profile',
                'data' => null
            ];
        }
        break;
        
    case 'POST':
        // Special action endpoints
        switch ($action) {
            case 'addresses':
                // Add a new shipping address
                if (!isLoggedIn()) {
                    http_response_code(401);
                    $response = [
                        'status' => 'error',
                        'message' => 'Unauthorized',
                        'data' => null
                    ];
                    break;
                }
                
                $userId = getCurrentUserId();
                
                // Check if user is adding to their own profile
                if ($id != $userId && !isAdminLoggedIn()) {
                    http_response_code(403);
                    $response = [
                        'status' => 'error',
                        'message' => 'Forbidden',
                        'data' => null
                    ];
                    break;
                }
                
                // Validate required fields
                if (empty($data['address_line1']) || empty($data['city']) || 
                    empty($data['state']) || empty($data['postal_code']) || 
                    empty($data['country'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Missing required address fields',
                        'data' => null
                    ];
                    break;
                }
                
                $db = Database::getInstance();
                
                // If this is marked as default, unset any existing default
                if (isset($data['is_default']) && $data['is_default']) {
                    $sql = "UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?";
                    $db->query($sql, [$id]);
                }
                
                // Insert address
                $sql = "INSERT INTO shipping_addresses (
                            user_id, address_line1, address_line2, city, state, 
                            postal_code, country, is_default, phone
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )";
                
                $params = [
                    $id,
                    $data['address_line1'],
                    $data['address_line2'] ?? null,
                    $data['city'],
                    $data['state'],
                    $data['postal_code'],
                    $data['country'],
                    $data['is_default'] ?? 0,
                    $data['phone'] ?? null
                ];
                
                try {
                    $stmt = $db->query($sql, $params);
                    
                    if ($stmt->rowCount() > 0) {
                        $addressId = $db->getConnection()->lastInsertId();
                        
                        $response = [
                            'status' => 'success',
                            'message' => 'Address added successfully',
                            'data' => [
                                'address_id' => $addressId
                            ]
                        ];
                    } else {
                        throw new Exception('Failed to add address');
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to add address: ' . $e->getMessage(),
                        'data' => null
                    ];
                }
                break;
                
            case 'payment-methods':
                // Add a new payment method
                if (!isLoggedIn()) {
                    http_response_code(401);
                    $response = [
                        'status' => 'error',
                        'message' => 'Unauthorized',
                        'data' => null
                    ];
                    break;
                }
                
                $userId = getCurrentUserId();
                
                // Check if user is adding to their own profile
                if ($id != $userId && !isAdminLoggedIn()) {
                    http_response_code(403);
                    $response = [
                        'status' => 'error',
                        'message' => 'Forbidden',
                        'data' => null
                    ];
                    break;
                }
                
                // Validate required fields
                if (empty($data['payment_type']) || empty($data['provider'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Missing required payment method fields',
                        'data' => null
                    ];
                    break;
                }
                
                $db = Database::getInstance();
                
                // If this is marked as default, unset any existing default
                if (isset($data['is_default']) && $data['is_default']) {
                    $sql = "UPDATE payment_methods SET is_default = 0 WHERE user_id = ?";
                    $db->query($sql, [$id]);
                }
                
                // Insert payment method
                $sql = "INSERT INTO payment_methods (
                            user_id, payment_type, provider, account_number_last4, 
                            expiry_date, is_default, created_at
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, NOW()
                        )";
                
                $params = [
                    $id,
                    $data['payment_type'],
                    $data['provider'],
                    $data['account_number_last4'] ?? null,
                    $data['expiry_date'] ?? null,
                    $data['is_default'] ?? 0
                ];
                
                try {
                    $stmt = $db->query($sql, $params);
                    
                    if ($stmt->rowCount() > 0) {
                        $paymentMethodId = $db->getConnection()->lastInsertId();
                        
                        $response = [
                            'status' => 'success',
                            'message' => 'Payment method added successfully',
                            'data' => [
                                'payment_method_id' => $paymentMethodId
                            ]
                        ];
                    } else {
                        throw new Exception('Failed to add payment method');
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to add payment method: ' . $e->getMessage(),
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
        
    case 'DELETE':
        // Only admin can delete users
        if (!isAdminLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        if (!$id) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'User ID is required',
                'data' => null
            ];
            break;
        }
        
        // Check if user exists
        $user = $userObj->getUserById($id);
        
        if (!$user) {
            http_response_code(404);
            $response = [
                'status' => 'error',
                'message' => 'User not found',
                'data' => null
            ];
            break;
        }
        
        // Check if user has orders
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
        $stmt = $db->query($sql, [$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            // If user has orders, deactivate instead of deleting
            $sql = "UPDATE users SET is_active = 0 WHERE user_id = ?";
            $stmt = $db->query($sql, [$id]);
            
            if ($stmt->rowCount() > 0) {
                $response = [
                    'status' => 'success',
                    'message' => 'User has existing orders and has been deactivated instead of deleted',
                    'data' => null
                ];
            } else {
                http_response_code(500);
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to deactivate user',
                    'data' => null
                ];
            }
        } else {
            // Delete all user data
            $db->getConnection()->beginTransaction();
            
            try {
                // Delete shipping addresses
                $sql = "DELETE FROM shipping_addresses WHERE user_id = ?";
                $db->query($sql, [$id]);
                
                // Delete payment methods
                $sql = "DELETE FROM payment_methods WHERE user_id = ?";
                $db->query($sql, [$id]);
                
                // Delete reviews
                $sql = "DELETE FROM reviews WHERE user_id = ?";
                $db->query($sql, [$id]);
                
                // Delete user
                $sql = "DELETE FROM users WHERE user_id = ?";
                $stmt = $db->query($sql, [$id]);
                
                if ($stmt->rowCount() > 0) {
                    $db->getConnection()->commit();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'User deleted successfully',
                        'data' => null
                    ];
                } else {
                    throw new Exception('Failed to delete user');
                }
            } catch (Exception $e) {
                $db->getConnection()->rollBack();
                
                http_response_code(500);
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to delete user: ' . $e->getMessage(),
                    'data' => null
                ];
            }
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
