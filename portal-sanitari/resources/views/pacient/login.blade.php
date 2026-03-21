<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIA - Accés Pacient</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
            padding: 20px;
        }

        .login-container { width: 100%; max-width: 440px; animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 48px 40px 40px;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .login-header { text-align: center; margin-bottom: 36px; }
        
        .login-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            transform: rotate(-5deg);
        }
        .login-icon svg { width: 32px; height: 32px; color: #16a34a; transform: rotate(5deg); }

        .login-title { font-size: 26px; font-weight: 800; color: #1e293b; margin-bottom: 8px; letter-spacing: -0.5px; }
        .login-subtitle { font-size: 15px; color: #64748b; }

        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 14px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-input {
            width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px;
            font-size: 16px; font-family: 'Inter', sans-serif; color: #0f172a;
            background: #f8fafc; transition: all 0.2s; outline: none;
        }
        .form-input:focus { border-color: #22c55e; background: #ffffff; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1); }

        .btn-login {
            width: 100%; padding: 16px;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #ffffff; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: all 0.2s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3); }

        .alert-error {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px;
            padding: 14px; margin-bottom: 24px; color: #991b1b; font-size: 14px; font-weight: 500;
        }
        
        .back-link {
            display: block; text-align: center; margin-top: 24px;
            color: #64748b; font-size: 14px; font-weight: 500; text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: #334155; text-decoration: underline; }

        @media (max-width: 480px) {
            .login-card { padding: 32px 20px 24px; border-radius: 20px; }
            .login-title { font-size: 24px; }
            .login-icon { width: 60px; height: 60px; margin-bottom: 20px; }
            .login-icon svg { width: 28px; height: 28px; }
            .form-input { padding: 14px; font-size: 15px; }
            .btn-login { padding: 14px; font-size: 15px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z"></path>
                    </svg>
                </div>
                <h1 class="login-title">Portal del Pacient</h1>
                <p class="login-subtitle">Introdueix el teu identificador personal</p>
            </div>

            @if ($errors->any())
                <div class="alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('pacient.login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="dni">Número d'Identificació (Test ID)</label>
                    <input type="text" class="form-input" id="dni" name="dni" placeholder="Ex: 12396" value="{{ old('dni') }}" required autofocus>
                </div>

                <button type="submit" class="btn-login">Accedir al meu espai</button>
            </form>
            
            <a href="{{ route('login') }}" class="back-link">&larr; Sóc un professional sanitari</a>
        </div>
    </div>
</body>
</html>
