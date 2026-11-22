<section class="auth-page">
    <div class="auth-story">
        <img src="/assets/hero-2.jpg" alt="A quiet corner with natural textures" />
        <div>
            <h2>
                Make yourself
                <br />
                at home.
            </h2>
            <p>Your orders, delivery addresses, and account details.</p>
        </div>
    </div>
    <div class="auth-form-wrap">
        <a class="subtle-link" href="/shop">Back to the shop <?= icon('arrow', 15) ?></a>
        <h1><?= $accountPage === 'register' ? 'Create an account.' : 'Welcome back.' ?></h1>
        <p><?= $accountPage === 'register' ? 'Save your details and keep track of your orders.' : 'Sign in to your Form & Field account.' ?></p>
        <form method="post" data-loading="Just a moment…">
            <?= csrf() ?>
            <input type="hidden" name="action" value="<?= $accountPage === 'register' ? 'customer_register' : 'customer_login' ?>" />

            <?php if ($accountPage === 'register'): ?>
            <div class="field">
                <label for="name">Your name</label>
                <input id="name" name="name" autocomplete="name" required maxlength="190" />
            </div>

            <?php endif; ?>
            <div class="field">
                <label for="email">Email address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    maxlength="190"
                />
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="<?= $accountPage === 'register' ? 'new-password' : 'current-password' ?>"
                    <?= $accountPage === 'register' ? 'minlength="12"' : '' ?>
                    required
                />

                <?php if ($accountPage === 'register'): ?>
                <small>Use at least 12 characters.</small>

                <?php endif; ?>
            </div>
            <button class="button dark full-width"><?= $accountPage === 'register' ? 'Create your account' : 'Sign in' ?> <?= icon('arrow', 18) ?></button>
        </form>
        <p class="auth-switch">
            <?= $accountPage === 'register' ? 'Already have an account?' : 'New to Form & Field?' ?>
            <a href="/account/<?= $accountPage === 'register' ? 'login' : 'register' ?>"><?= $accountPage === 'register' ? 'Sign in' : 'Create an account' ?></a>
        </p>
    </div>
</section>
