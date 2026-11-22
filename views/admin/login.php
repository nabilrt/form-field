<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1" />
        <title>Merchant sign in — Form & Field</title>
        <link rel="icon" href="/assets/favicon.svg" />
        <link rel="stylesheet" href="/assets/style.css" />
        <script src="/assets/app.js" defer></script>
    </head>
    <body class="admin-login">
        <a class="brand" href="/">
            form
            <span>&</span>
            field.
        </a>

        <?php require __DIR__ . '/../flash.php'; ?>
        <main>
            <div class="login-icon"><?= icon('bag', 30) ?></div>
            <h1>Your store starts here.</h1>
            <p>Sign in to your merchant account.</p>
            <form method="post" data-loading="Signing in…">
                <?= csrf() ?>
                <input type="hidden" name="action" value="login" />
                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="username"
                        required
                        autofocus
                    />
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                </div>
                <button class="button dark full-width">Sign in <?= icon('arrow', 18) ?></button>
            </form>
            <a class="subtle-link" href="/">Back to the storefront</a>
        </main>
        <small>Form & Field · Merchant workspace</small>
    </body>
</html>
