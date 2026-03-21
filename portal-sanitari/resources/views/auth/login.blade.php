<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal Sanitari - Accedeix al teu panell de control">
    <title>Portal Sanitari - Iniciar Sessió</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e0f4ff 0%, #d4f0ff 25%, #c8ecff 50%, #d8f2ff 75%, #e4f6ff 100%);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 460px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 48px 40px 40px;
            box-shadow: 0 4px 30px rgba(0, 100, 200, 0.08), 0 1px 3px rgba(0, 50, 100, 0.04);
            border: 1px solid rgba(200, 225, 255, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .login-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #eef4ff 0%, #dfe9f8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .login-icon svg {
            width: 28px;
            height: 28px;
            color: #3b6fcc;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a2c4e;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .login-subtitle {
            font-size: 14px;
            color: #5b8cd4;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1e2d45;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #334155;
            background: #f8fafc;
            transition: all 0.25s ease;
            outline: none;
        }

        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-input:focus {
            border-color: #4285f4;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.1);
        }

        .form-input:hover:not(:focus) {
            border-color: #cbd5e1;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4285f4 0%, #3574e2 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 8px;
            letter-spacing: 0.01em;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #3574e2 0%, #2a63cc 100%);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.35);
            transform: translateY(-1px);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(66, 133, 244, 0.3);
        }

        /* Error alert */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shakeX 0.5s ease;
        }

        @keyframes shakeX {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .alert-error svg {
            width: 18px;
            height: 18px;
            color: #dc2626;
            flex-shrink: 0;
        }

        .alert-error p {
            font-size: 13px;
            color: #991b1b;
            font-weight: 500;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 36px 24px 32px;
                border-radius: 16px;
            }

            .login-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <h1 class="login-title">Portal Sanitari</h1>
                <p class="login-subtitle">Accedeix al teu panell de control</p>
            </div>

            @if ($errors->any())
                <div class="alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <p>{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="login-form">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="username">Nom d'usuari</label>
                    <input
                        type="text"
                        class="form-input"
                        id="username"
                        name="username"
                        placeholder="Introdueix el teu usuari"
                        value="{{ old('username') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contrasenya</label>
                    <input
                        type="password"
                        class="form-input"
                        id="password"
                        name="password"
                        placeholder="Introdueix la teva contrasenya"
                        required
                    >
                </div>

                <button type="submit" class="btn-login" id="btn-login">
                    Iniciar Sessió
                </button>
            </form>
        </div>
    </div>
</body>
</html>
