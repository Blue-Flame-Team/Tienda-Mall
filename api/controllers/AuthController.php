<?php
/**
 * Auth Controller
 * Handles user authentication operations including login, register, password reset
 */

require_once '../includes/Controller.php';
require_once '../includes/DatabaseHelper.php';

class AuthController extends Controller {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->db = new DatabaseHelper();
    }
    
    /**
     * Route the request to the appropriate handler
     */
    public function handleRequest($action = '', $id = null) {
        switch ($this->method) {
            case 'POST':
                if ($action === 'login') {
                    $this->login();
                } else if ($action === 'register') {
                    $this->register();
                } else if ($action === 'verify') {
                    $this->verifyEmail();
                } else if ($action === 'forgot-password') {
                    $this->forgotPassword();
                } else if ($action === 'reset-password') {
                    $this->resetPassword();
                } else if ($action === 'logout') {
                    $this->logout();
                } else {
                    $this->respondError('Invalid action', null, 400);
                }
                break;
                
            case 'GET':
                if ($action === 'me') {
                    $this->getCurrentUser();
                } else if ($action === 'check-email') {
                    $this->checkEmailExists();
                } else {
                    $this->respondError('Invalid action', null, 400);
                }
                break;
                
            default:
                $this->respondError('Method not allowed', null, 405);
        }
    }
    
    /**
     * Login a user
     */
    private function login() {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $email = $this->getParam('email');
        $password = $this->getParam('password');
        
        // Find user by email
        $sql = "SELECT * FROM users WHERE email = ?";
        $user = $this->db->fetchOne($sql, [$email]);
        
        if (!$user) {
            $this->respondError('Invalid email or password', null, 401);
            return;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->respondError('Invalid email or password', null, 401);
            return;
        }
        
        // Check if user is active
        if ($user['is_active'] !== 'YES') {
            $this->respondError('Your account has been deactivated', null, 403);
            return;
        }
        
        // Update last login timestamp
        $this->db->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], 'user_id = ?', [$user['user_id']]);
        
        // Set user session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        // Remove sensitive fields
        unset($user['password_hash']);
        unset($user['reset_token']);
        unset($user['verification_token']);
        
        $this->respondSuccess([
            'user' => $user,
            'is_logged_in' => true
        ], 'Login successful');
    }
    
    /**
     * Register a new user
     */
    private function register() {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'first_name' => 'required|min:2|max:80',
            'last_name' => 'required|min:2|max:80',
            'phone' => 'min:10|max:20'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        // Check if email already exists
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $result = $this->db->fetchOne($sql, [$this->getParam('email')]);
        
        if ((int)$result['count'] > 0) {
            $this->respondError('Email address is already registered', ['field' => 'email'], 400);
            return;
        }
        
        // Check if phone already exists (if provided)
        if ($this->hasParam('phone') && !empty($this->getParam('phone'))) {
            $sql = "SELECT COUNT(*) as count FROM users WHERE phone = ?";
            $result = $this->db->fetchOne($sql, [$this->getParam('phone')]);
            
            if ((int)$result['count'] > 0) {
                $this->respondError('Phone number is already registered', ['field' => 'phone'], 400);
                return;
            }
        }
        
        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        
        // Prepare user data
        $userData = [
            'email' => $this->getParam('email'),
            'password_hash' => password_hash($this->getParam('password'), PASSWORD_DEFAULT),
            'first_name' => $this->getParam('first_name'),
            'last_name' => $this->getParam('last_name'),
            'phone' => $this->getParam('phone', null),
            'date_of_birth' => $this->getParam('date_of_birth', null),
            'email_verified' => 'NO',
            'verification_token' => $verificationToken,
            'is_active' => 'YES',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert user
        $userId = $this->db->insert('users', $userData);
        
        if (!$userId) {
            $this->respondError('Failed to register user', null, 500);
            return;
        }
        
        // TODO: Send verification email
        // This would typically include a link with the verification token
        // For now, we'll just return the token in the response
        
        $this->respondSuccess([
            'user_id' => $userId,
            'verification_token' => $verificationToken // In production, don't return this
        ], 'Registration successful', 201);
    }
    
    /**
     * Verify user email address
     */
    private function verifyEmail() {
        $rules = [
            'token' => 'required'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $token = $this->getParam('token');
        
        // Find user with this token
        $sql = "SELECT * FROM users WHERE verification_token = ?";
        $user = $this->db->fetchOne($sql, [$token]);
        
        if (!$user) {
            $this->respondError('Invalid verification token', null, 400);
            return;
        }
        
        // Update user to verified status
        $result = $this->db->update('users', [
            'email_verified' => 'YES',
            'verification_token' => null
        ], 'user_id = ?', [$user['user_id']]);
        
        if ($result) {
            $this->respondSuccess(null, 'Email verification successful');
        } else {
            $this->respondError('Failed to verify email', null, 500);
        }
    }
    
    /**
     * Send password reset email
     */
    private function forgotPassword() {
        $rules = [
            'email' => 'required|email'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $email = $this->getParam('email');
        
        // Find user by email
        $sql = "SELECT * FROM users WHERE email = ?";
        $user = $this->db->fetchOne($sql, [$email]);
        
        if (!$user) {
            // For security reasons, still return success even if email doesn't exist
            $this->respondSuccess(null, 'If your email is registered, you will receive a password reset link');
            return;
        }
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiryDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Update user with reset token
        $this->db->update('users', [
            'reset_token' => $resetToken,
            'reset_token_expiry' => $expiryDate
        ], 'user_id = ?', [$user['user_id']]);
        
        // TODO: Send reset email
        // For now, just return the token in the response
        
        $this->respondSuccess([
            'reset_token' => $resetToken // In production, don't return this
        ], 'If your email is registered, you will receive a password reset link');
    }
    
    /**
     * Reset user password
     */
    private function resetPassword() {
        $rules = [
            'token' => 'required',
            'password' => 'required|min:8'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $token = $this->getParam('token');
        $password = $this->getParam('password');
        
        // Find user with this token
        $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $user = $this->db->fetchOne($sql, [$token]);
        
        if (!$user) {
            $this->respondError('Invalid or expired reset token', null, 400);
            return;
        }
        
        // Update user password
        $result = $this->db->update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expiry' => null
        ], 'user_id = ?', [$user['user_id']]);
        
        if ($result) {
            $this->respondSuccess(null, 'Password reset successful');
        } else {
            $this->respondError('Failed to reset password', null, 500);
        }
    }
    
    /**
     * Log out the current user
     */
    private function logout() {
        // Destroy the session
        session_unset();
        session_destroy();
        
        $this->respondSuccess(null, 'Logout successful');
    }
    
    /**
     * Get current authenticated user
     */
    private function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            $this->respondError('Not authenticated', null, 401);
            return;
        }
        
        $userId = $this->getUserId();
        
        // Get user data
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $user = $this->db->fetchOne($sql, [$userId]);
        
        if (!$user) {
            // This should not happen, but just in case
            session_unset();
            session_destroy();
            $this->respondError('User not found', null, 404);
            return;
        }
        
        // Remove sensitive fields
        unset($user['password_hash']);
        unset($user['reset_token']);
        unset($user['verification_token']);
        
        $this->respondSuccess([
            'user' => $user,
            'is_logged_in' => true
        ]);
    }
    
    /**
     * Check if an email address exists
     */
    private function checkEmailExists() {
        $email = $this->getParam('email');
        
        if (!$email) {
            $this->respondError('Email parameter is required', null, 400);
            return;
        }
        
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $result = $this->db->fetchOne($sql, [$email]);
        
        $this->respondSuccess([
            'exists' => (int)$result['count'] > 0
        ]);
    }
}
?>
