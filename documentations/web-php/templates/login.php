<?php
/**
 * QuickEst - Login Page
 */
?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 style="color: #217346; margin: 0;">QuickEst</h1>
            <p style="color: #666; margin: 5px 0 0 0;">Pre-Engineered Building Estimation</p>
        </div>

        <div id="login-form-container">
            <h2>Sign In</h2>

            <div id="auth-error" class="auth-error" style="display: none;"></div>

            <form id="login-form" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="login-username">Username or Email</label>
                    <input type="text" id="login-username" name="username" required
                           autocomplete="username" placeholder="Enter username or email">
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required
                           autocomplete="current-password" placeholder="Enter password">
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="login-remember" name="remember">
                    <label for="login-remember" style="margin: 0; cursor: pointer;">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="login-btn">
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="#" onclick="showRegisterForm()">Register</a></p>
            </div>
        </div>

        <div id="register-form-container" style="display: none;">
            <h2>Create Account</h2>

            <div id="register-error" class="auth-error" style="display: none;"></div>

            <form id="register-form" onsubmit="handleRegister(event)">
                <div class="form-group">
                    <label for="reg-username">Username</label>
                    <input type="text" id="reg-username" name="username" required
                           pattern="[a-zA-Z0-9_]{3,30}" placeholder="3-30 characters (letters, numbers, _)">
                </div>

                <div class="form-group">
                    <label for="reg-email">Email</label>
                    <input type="email" id="reg-email" name="email" required
                           placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="reg-fullname">Full Name</label>
                    <input type="text" id="reg-fullname" name="full_name"
                           placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="reg-company">Company</label>
                    <input type="text" id="reg-company" name="company"
                           placeholder="Company name">
                </div>

                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" required
                           minlength="6" placeholder="Minimum 6 characters">
                </div>

                <div class="form-group">
                    <label for="reg-password-confirm">Confirm Password</label>
                    <input type="password" id="reg-password-confirm" name="password_confirm" required
                           placeholder="Confirm password">
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="register-btn">
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="#" onclick="showLoginForm()">Sign In</a></p>
            </div>
        </div>

        <div class="auth-demo-info">
            <p style="font-size: 11px; color: #888; text-align: center; margin-top: 20px;">
                Demo credentials: admin / admin123
            </p>
        </div>
    </div>
</div>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 150px);
    padding: 20px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
}

.auth-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 40px;
    width: 100%;
    max-width: 400px;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #217346;
}

.auth-card h2 {
    color: #333;
    margin: 0 0 20px 0;
    font-size: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    color: #555;
    font-size: 13px;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #217346;
    box-shadow: 0 0 0 2px rgba(33, 115, 70, 0.1);
}

.btn-block {
    width: 100%;
    padding: 12px;
    font-size: 14px;
}

.auth-error {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #dc2626;
    padding: 10px 12px;
    border-radius: 4px;
    margin-bottom: 16px;
    font-size: 13px;
}

.auth-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.auth-footer a {
    color: #217346;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}
</style>

<script>
function showLoginForm() {
    document.getElementById('login-form-container').style.display = 'block';
    document.getElementById('register-form-container').style.display = 'none';
}

function showRegisterForm() {
    document.getElementById('login-form-container').style.display = 'none';
    document.getElementById('register-form-container').style.display = 'block';
}

async function handleLogin(event) {
    event.preventDefault();

    const btn = document.getElementById('login-btn');
    const errorDiv = document.getElementById('auth-error');
    btn.disabled = true;
    btn.textContent = 'Signing in...';
    errorDiv.style.display = 'none';

    try {
        const response = await fetch('?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: document.getElementById('login-username').value,
                password: document.getElementById('login-password').value
            })
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '?page=dashboard';
        } else {
            errorDiv.textContent = result.error || 'Login failed';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Connection error. Please try again.';
        errorDiv.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Sign In';
}

async function handleRegister(event) {
    event.preventDefault();

    const btn = document.getElementById('register-btn');
    const errorDiv = document.getElementById('register-error');
    btn.disabled = true;
    btn.textContent = 'Creating account...';
    errorDiv.style.display = 'none';

    const password = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-password-confirm').value;

    if (password !== confirmPassword) {
        errorDiv.textContent = 'Passwords do not match';
        errorDiv.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Create Account';
        return;
    }

    try {
        const response = await fetch('?action=register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: document.getElementById('reg-username').value,
                email: document.getElementById('reg-email').value,
                full_name: document.getElementById('reg-fullname').value,
                company: document.getElementById('reg-company').value,
                password: password
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Account created successfully! Please sign in.');
            showLoginForm();
            document.getElementById('login-username').value = document.getElementById('reg-username').value;
        } else {
            errorDiv.textContent = result.error || 'Registration failed';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Connection error. Please try again.';
        errorDiv.style.display = 'block';
    }

    btn.disabled = false;
    btn.textContent = 'Create Account';
}
</script>
