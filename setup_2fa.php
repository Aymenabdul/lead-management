<?php
require_once __DIR__ . '/auth_middleware.php';
$user = AuthMiddleware::requireAuth();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup 2FA | Turtle Dot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto+Serif:opsz,wght@8..144,300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            background-color: #f9fafb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .setup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            text-align: center;
        }

        .brand-header {
            margin-bottom: 2rem;
        }

        .brand-logo {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }

        h2 {
            font-family: 'Roboto Serif', serif;
            font-size: 1.75rem;
            color: #064e3b;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .qr-container {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-code {
            max-width: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
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
            padding: 0.875rem 1rem;
            border: 1px solid #e5e7eb;
            background-color: var(--input-bg);
            border-radius: 12px;
            font-size: 1.1rem;
            color: var(--text-main);
            text-align: center;
            letter-spacing: 0.2em;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            background-color: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--focus-ring);
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            padding: 0.75rem;
            border-radius: 8px;
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
    </style>
</head>

<body>
    <div class="setup-card">
        <div class="brand-header">
            <img src="assets/images/turtle_logo.png" alt="Turtle Dot" class="brand-logo">
            <h2>Secure Your Account</h2>
            <p class="subtitle">
                To continue, you must set up Two-Factor Authentication (2FA).<br>
                Scan the QR code below with Google Authenticator.
            </p>
        </div>

        <div id="loading" style="padding: 2rem;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
        </div>

        <div id="setup-content" style="display: none;">
            <div class="qr-container">
                <div id="qr-code-container" style="display: flex; justify-content: center; margin-bottom: 1rem;"></div>
                <p style="font-size: 0.85rem; color: var(--text-muted);">
                    Can't scan? Manual entry not supported yet.
                </p>
            </div>

            <div id="alert" class="alert"></div>

            <div class="form-group">
                <label for="code" class="form-label">Enter 6-Digit Code</label>
                <input type="text" id="code" class="form-control" maxlength="6" placeholder="000 000" autocomplete="off"
                    pattern="[0-9]*">
            </div>

            <button class="btn-submit" onclick="verifyAndEnable()">Verify & Enable 2FA</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadQRCode);

        async function loadQRCode() {
            try {
                const response = await fetch('/api/setup_2fa.php', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    if (data.enabled) {
                        window.location.href = '/index.php'; // Already enabled
                        return;
                    }

                    // Clear previous content
                    const qrContainer = document.getElementById('qr-code-container');
                    qrContainer.innerHTML = '';

                    // Generate QR Code
                    new QRCode(qrContainer, {
                        text: data.otpauth_url,
                        width: 200,
                        height: 200,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('setup-content').style.display = 'block';
                } else {
                    document.getElementById('loading').style.display = 'none';
                    showAlert(data.error || 'Failed to load QR code', 'error');
                }
            } catch (error) {
                console.error(error);
                document.getElementById('loading').style.display = 'none';
                showAlert('Connection error', 'error');
            }
        }

        // Enter key handler
        document.getElementById('code').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                verifyAndEnable();
            }
        });

        async function verifyAndEnable() {
            const code = document.getElementById('code').value.trim();
            if (code.length !== 6 || !/^\d+$/.test(code)) {
                showAlert('Please enter a valid 6-digit code', 'error');
                return;
            }

            const btn = document.querySelector('.btn-submit');
            btn.disabled = true;
            btn.textContent = 'Verifying...';

            try {
                const response = await fetch('/api/verify_2fa.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Success! Redirecting to dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = '/index.php';
                    }, 1500);
                } else {
                    showAlert(data.error || 'Invalid code. Try again.', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Verify & Enable 2FA';
                }
            } catch (error) {
                showAlert('Connection error', 'error');
                btn.disabled = false;
                btn.textContent = 'Verify & Enable 2FA';
            }
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alert');
            alertBox.textContent = message;
            alertBox.className = `alert alert-${type}`;
            alertBox.style.display = 'block';
        }
    </script>
</body>

</html>