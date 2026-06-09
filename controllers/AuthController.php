<?php
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
            return;
        }
        // Render login without layout
        extract(['pageTitle' => 'Login - PEGASUS ERP']);
        ob_start();
        include BASE_PATH . '/views/auth/login.php';
        $content = ob_get_clean();
        echo $content;
    }

    public function login()
    {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            flash('error', 'Username and password are required.');
            $this->redirect('/login');
            return;
        }

        if (Auth::login($username, $password)) {
            $this->redirect('/dashboard');
        } else {
            flash('error', 'Invalid username or password.');
            $this->redirect('/login');
        }
    }

    public function logout()
    {
        Auth::logout();
        $this->redirect('/login');
    }

    public function switchLang($code)
    {
        $allowed = ['en', 'ja', 'th'];
        if (in_array($code, $allowed)) {
            $_SESSION['lang'] = $code;
            // Reset cached translations
            global $_PEGASUS_LANG;
            $_PEGASUS_LANG = null;
        }
        // Redirect back to referring page or dashboard
        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        $this->redirect($referer);
    }
}
