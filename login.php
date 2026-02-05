<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Turtle Dot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Serif:opsz,wght@8..144,300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --input-bg: #f3f4f6;
            --focus-ring: rgba(16, 185, 129, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

        /* Layout */
        .split-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Branding Panel */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -20%;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 480px;
        }

        .brand-logo-large {
            width: 120px;
            height: auto;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 12px 24px rgba(16, 185, 129, 0.2));
            transition: transform 0.5s ease;
        }

        .brand-logo-large:hover {
            transform: scale(1.05) rotate(3deg);
        }

        .brand-title {
            font-family: 'Roboto Serif', serif;
            font-size: 3.5rem;
            font-weight: 700;
            color: #064e3b;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.2), 0 0 30px rgba(16, 185, 129, 0.4);
        }

        .brand-desc {
            font-size: 1.125rem;
            color: #065f46;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Right Form Panel */
        .form-panel {
            flex: 0 0 500px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            box-shadow: -10px 0 40px rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 20;
        }

        .login-header {
            margin-bottom: 3rem;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid transparent;
            background-color: var(--input-bg);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--text-main);
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            background-color: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--focus-ring);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: none;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .alert.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            .split-layout {
                flex-direction: column;
            }

            .brand-panel {
                flex: 0 0 200px;
                padding: 2rem;
            }

            .brand-logo-large {
                width: 60px;
                margin-bottom: 1rem;
            }

            .brand-title {
                font-size: 2rem;
            }

            .brand-desc {
                display: none;
            }

            .form-panel {
                flex: 1;
                width: 100%;
                padding: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="split-layout">
        <div class="brand-panel">
            <div class="brand-content">
                <img src="assets/images/turtle_logo.png" alt="Turtle Dot" class="brand-logo-large">
                <h1 class="brand-title">turtle dot</h1>
                <p class="brand-desc">
                    Empowering your team with secure data management and streamlined associate tracking.
                    Experience efficiency and precision in every interaction.
                </p>
            </div>
        </div>

        <div class="form-panel">
            <div class="login-header">
                <h2 class="login-title">Welcome Back</h2>
                <p class="login-subtitle">Please enter your details to sign in.</p>
            </div>

            <div id="alert" class="alert"></div>

            <form id="loginForm">
                <div id="credentials-section">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control"
                            placeholder="Enter your username" required autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                            required autocomplete="current-password">
                    </div>
                </div>

                <div id="two-fa-section" style="display: none;">
                    <div class="form-group">
                        <label for="two_fa_code" class="form-label">Authenticator Code</label>
                        <input type="text" id="two_fa_code" name="code" class="form-control"
                            placeholder="Enter 6-digit code" autocomplete="off" maxlength="6" pattern="[0-9]*">
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                            Open your Google Authenticator app and enter the code.
                        </p>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="loginBtn">Sign In</button>
            </form>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const alertBox = document.getElementById('alert');
        const credentialsSection = document.getElementById('credentials-section');
        const twoFaSection = document.getElementById('two-fa-section');
        const twoFaInput = document.getElementById('two_fa_code');

        let isTwoFaStep = false;

        function showAlert(message, type = 'error') {
            alertBox.textContent = message;
            alertBox.className = `alert alert-${type} show`;
            if (type !== 'error') { // Auto hide success/info messages, keep errors visible longer if needed
                setTimeout(() => alertBox.classList.remove('show'), 5000);
            }
        }

        function setLoading(isLoading) {
            loginBtn.disabled = isLoading;
            loginBtn.textContent = isLoading ? 'Processing...' : (isTwoFaStep ? 'Verify Code' : 'Sign In');
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const code = twoFaInput.value.trim();

            if (!isTwoFaStep) {
                if (!username || !password) return showAlert('Please fill in all fields');
            } else {
                if (!code) return showAlert('Please enter the verification code');
            }

            setLoading(true);

            const payload = { username, password };
            if (isTwoFaStep) {
                payload.code = code;
            }

            try {
                const response = await fetch('/api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showAlert('Success! Redirecting...', 'success');
                    localStorage.setItem('user', JSON.stringify(data.user));

                    setTimeout(() => {
                        // Enforce 2FA Setup
                        if (!data.user.two_fa_enabled) {
                            window.location.href = '/setup_2fa.php';
                        } else {
                            window.location.href = '/index.php';
                        }
                    }, 1000);
                } else if (data.require_2fa) {
                    // Switch to 2FA mode
                    isTwoFaStep = true;
                    credentialsSection.style.display = 'none';
                    twoFaSection.style.display = 'block';
                    twoFaInput.disabled = false;
                    twoFaInput.focus();

                    document.querySelector('.login-subtitle').textContent = 'Please enter your 2FA code.';
                    showAlert('Two-factor authentication required', 'success'); // Using success style for info
                    setLoading(false); // Reset button text
                } else {
                    showAlert(data.message || data.error || 'Login failed');
                    setLoading(false);
                    // If 2FA code failed, clear it
                    if (isTwoFaStep) {
                        twoFaInput.value = '';
                        twoFaInput.focus();
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('Connection error. Please try again.');
                setLoading(false);
            }
        });

        if (document.cookie.includes('auth_token')) {
            window.location.href = '/index.php';
        }
    </script>
</body>

</html>