<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MensaPanel — Acceso</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1B4F8A 0%, #0D2D54 100%); min-height: 100vh; display: flex; align-items: center; }
        .login-card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.3); max-width: 400px; width: 100%; }
        .login-header { background: #1B4F8A; border-radius: 16px 16px 0 0; padding: 2rem; text-align: center; color: #fff; }
        .login-header h1 { font-size: 1.8rem; font-weight: 700; margin: 0; }
        .login-header p  { opacity: .8; font-size: .9rem; margin: 0; }
        .login-body { padding: 2rem; }
        .btn-login { background: #1B4F8A; border: none; padding: .75rem; font-size: 1rem; font-weight: 600; }
        .btn-login:hover { background: #123566; }
        .form-control:focus { border-color: #1B4F8A; box-shadow: 0 0 0 .2rem rgba(27,79,138,.25); }
    </style>
</head>
<body>
<div class="container">
    <div class="mx-auto login-card bg-white">
        <div class="login-header">
            <i class="fas fa-paper-plane fa-2x mb-2"></i>
            <h1>MensaPanel</h1>
            <p>Sistema de Mensajería Interna</p>
        </div>
        <div class="login-body">
            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="/login" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-envelope me-1 text-secondary"></i> Email del administrador
                    </label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" placeholder="admin@mensapanel.com" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-lock me-1 text-secondary"></i> Contraseña
                    </label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-4 form-check">
                    <input type="checkbox" name="recordar" id="recordar" class="form-check-input">
                    <label for="recordar" class="form-check-label small">Recordar sesión</label>
                </div>
                <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                    <i class="fas fa-sign-in-alt me-2"></i> Ingresar al Panel
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
