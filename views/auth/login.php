<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= _e('login') ?> - PEGASUS ERP</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&family=Noto+Sans+JP:wght@300;400;500;700&family=Noto+Sans+Thai:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="/css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, #1565C0 0%, #1976D2 40%, #42A5F5 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #FFFFFF;
            width: 400px;
            max-width: 92vw;
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
            padding: 40px 36px 32px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .login-logo-text {
            font-size: 28px;
            font-weight: 700;
            color: #1976D2;
            letter-spacing: 3px;
        }
        .login-logo-sub {
            font-size: 12px;
            color: #9E9E9E;
            letter-spacing: 1px;
            margin-top: 8px;
        }
        .login-form .form-group { margin-bottom: 20px; }
        .login-form .form-label { font-size: 13px; font-weight: 500; color: #424242; margin-bottom: 6px; }
        .login-form .form-input { padding: 10px 14px; font-size: 14px; border-radius: 6px; }
        .login-btn {
            display: block; width: 100%; padding: 11px; font-size: 14px; font-weight: 600;
            color: #FFFFFF; background: #1565C0; border: none; border-radius: 6px;
            cursor: pointer; transition: background 0.2s; letter-spacing: 0.5px; margin-top: 8px;
        }
        .login-btn:hover { background: #0D47A1; }
        .login-flash { margin-bottom: 20px; }
        .login-footer { text-align: center; margin-top: 32px; font-size: 11px; color: rgba(255, 255, 255, 0.7); }
        .login-lang { text-align: center; margin-top: 16px; }
        .login-lang a {
            display: inline-block; padding: 3px 12px; margin: 0 2px; font-size: 12px; font-weight: 600;
            border: 1px solid rgba(255,255,255,0.4); border-radius: 4px; color: rgba(255,255,255,0.8);
            text-decoration: none; transition: all 0.15s;
        }
        .login-lang a:hover { border-color: #fff; color: #fff; }
        .login-lang a.active { background: rgba(255,255,255,0.2); border-color: #fff; color: #fff; }
    </style>
</head>
<body>

    <!-- Flash Messages -->
    <?php if (!empty($_SESSION['_flash'])): ?>
        <div style="position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;width:400px;max-width:90vw;">
            <?php foreach ($_SESSION['_flash'] as $type => $message): ?>
                <div class="alert alert-<?= htmlspecialchars($type) ?>">
                    <span><?= htmlspecialchars($message) ?></span>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php unset($_SESSION['_flash']); ?>
    <?php endif; ?>

    <div class="login-card">
        <div class="login-logo">
            <img src="/assets/PEGASUS_Logo_02.png" alt="PEGASUS" style="max-width:420px;height:auto;margin-bottom:16px;">
            <div class="login-logo-sub"><?= _e('login_subtitle') ?></div>
        </div>

        <form class="login-form" method="POST" action="/login" autocomplete="off">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="username"><?= _e('email') ?></label>
                <input type="email" id="username" name="username" class="form-input" placeholder="<?= _e('email_placeholder') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password"><?= _e('password') ?></label>
                <input type="password" id="password" name="password" class="form-input" placeholder="<?= _e('password') ?>" required>
            </div>

            <button type="submit" class="login-btn"><?= _e('login') ?></button>
        </form>
    </div>

    <!-- Language Switcher -->
    <div class="login-lang">
        <a href="/lang/ja" class="<?= currentLang() === 'ja' ? 'active' : '' ?>">JA</a>
        <a href="/lang/en" class="<?= currentLang() === 'en' ? 'active' : '' ?>">EN</a>
        <a href="/lang/th" class="<?= currentLang() === 'th' ? 'active' : '' ?>">TH</a>
    </div>

    <div class="login-footer">
        <?= __('footer_text', date('Y')) ?>
    </div>

</body>
</html>
