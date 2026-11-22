#!/usr/bin/env python3
"""Serverless application integration checks. Uses a temporary SQLite SQL adapter.
This validates PHP routes and business logic, not MySQL locking or MySQL execution.
Run tests/mysql-smoke.php separately against your configured MySQL installation.
"""
import os
import json
import tempfile
import shutil
import subprocess
import sqlite3
import re
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
work = Path(tempfile.mkdtemp(prefix='form-field-tests-'))
for folder in ['app', 'views', 'scripts', 'database']:
    shutil.copytree(ROOT / folder, work / folder)
(work / 'public/uploads').mkdir(parents=True)
(work / 'storage').mkdir()
shutil.copy(ROOT / 'public/index.php', work / 'public/index.php')
s = (work / 'app/bootstrap.php').read_text().replace("declare(strict_types=1);",
                                                     "declare(strict_types=1); error_reporting(E_ALL & ~E_DEPRECATED);")
start = s.index('function db(')
end = s.index('function q(', start)
support = ROOT / 'tests/support'
s = s[:start] + (support / 'database.php').read_text().removeprefix('<?php') + s[end:]
s = s.replace(
    '$s = db()->prepare($sql);',
    "$GLOBALS['testQueryCount'] = ($GLOBALS['testQueryCount'] ?? 0) + 1; "
    "$s = db()->prepare(translate_test_sql($sql));"
)
s = re.sub(r"header\('Location: '\s*\.\s*\$url\);\s*exit;", "echo '__REDIRECT__:'.$url;exit;", s)
s += '\nif(isset($GLOBALS[\'testInput\'])){$_SESSION=$GLOBALS[\'testInput\'][\'session\']??[];$_SESSION[\'csrf\']=\'test-csrf\';}\n'
(work / 'app/bootstrap.php').write_text(s)
schema = (work / 'database/schema.sql').read_text().replace('INT AUTO_INCREMENT PRIMARY KEY',
                                                            'INTEGER PRIMARY KEY AUTOINCREMENT')
schema = re.sub(r"ENUM\([^)]*\)", 'TEXT', schema)
schema = re.sub(r' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', '', schema)
schema = re.sub(r',\s*INDEX [a-zA-Z0-9_]+ \([^)]*\)', '', schema)
conn = sqlite3.connect(work / 'storage/test.sqlite')
conn.executescript(schema)
conn.commit()
shutil.copy(support / 'fixture.php', work / 'fixture.php')
subprocess.run(['php', str(work / 'fixture.php')], check=True, capture_output=True)
shutil.copy(support / 'request.php', work / 'request.php')
checks = 0


def check(ok, message):
    global checks
    if not ok:
        raise AssertionError(message)
    checks += 1
    print('PASS', message)


def request(
        uri='/',
        post=None,
        session=None,
        query=None,
        referer='/admin',
        raw_csrf=False,
        files=None,
        accept='',
        ip='127.0.0.1'):
    payload = {
        'ip': ip,
        'method': 'POST' if post is not None else 'GET',
        'uri': uri,
        'query': query or {},
        'post': dict(post or {}),
        'session': session or {},
        'referer': referer, 'files': files or {}, 'accept': accept}
    if post is not None and not raw_csrf:
        payload['post']['csrf'] = 'test-csrf'
    (work / 'input.json').write_text(json.dumps(payload))
    out = subprocess.run(['php',
                          str(work / 'request.php'),
                          str(work / 'input.json')],
                         capture_output=True,
                         text=True)
    state = json.loads((work / 'storage/state.json').read_text())
    if out.returncode:
        raise RuntimeError(out.stderr + out.stdout)
    if 'PHP Warning:' in out.stderr or 'PHP Fatal' in out.stderr:
        raise RuntimeError(out.stderr)
    return out.stdout, state['session'], state['status']


def scalar(sql, args=()):
    return conn.execute(sql, args).fetchone()[0]


def execute(sql, args=()):
    conn.execute(sql, args)
    conn.commit()


try:
    check(scalar('SELECT COUNT(*) FROM products') == 15, '15 seeded products')
    check(scalar('SELECT COUNT(*) FROM categories') == 7, '7 product collections')
    for route in [
        '/',
        '/shop',
        '/product/arc-lounge-chair',
        '/cart',
        '/account/login',
        '/account/register',
        '/about',
            '/admin/login']:
        html, _, status = request(route)
        check(status == 200 and '<html' in html, 'Render ' + route)
    html, _, status = request('/product/missing')
    check(status == 404, 'Unknown product returns 404')
    html, _, _ = request('/admin')
    check('/admin/login' in html, 'Admin requires authentication')
    html, _, status = request('/cart', {'action': 'cart_add',
                              'variant_id': 1, 'quantity': 1}, raw_csrf=True)
    check(status == 419, 'CSRF protection rejects invalid request')
    html, state, _ = request('/account/register', {'action': 'customer_register',
                             'name': 'Test Customer', 'email': 'customer@test.local', 'password': 'CustomerPassword123'})
    customer = state['customer_id']
    check(customer > 0, 'Customer registration hashes password and signs in')
    check(scalar('SELECT password FROM customers WHERE id=?', (customer,))
          != 'CustomerPassword123', 'Customer password is hashed')
    html, _, _ = request(
        '/account/login', {'action': 'customer_login', 'email': 'customer@test.local', 'password': 'wrong'})
    check('/account/login' in html, 'Invalid customer password rejected')
    html, state, _ = request('/account/login', {'action': 'customer_login',
                             'email': 'customer@test.local', 'password': 'CustomerPassword123'})
    check(state['customer_id'] == customer, 'Customer sign-in works')
    cs = {'customer_id': customer}
    addr = {
        'action': 'customer_address',
        'label': 'Home',
        'first_name': 'Test',
        'last_name': 'Customer',
        'phone': '01700000000',
        'address': '42 Test Road',
        'city': 'Dhaka',
        'postal': '1205',
        'is_default': '1'}
    request('/account/addresses', addr, cs)
    check(scalar('SELECT COUNT(*) FROM addresses WHERE customer_id=?',
          (customer,)) == 1, 'Customer can save delivery address')
    request('/account/addresses', {**addr, 'label': 'Work', 'address': '9 Office Road'}, cs)
    check(scalar('SELECT COUNT(*) FROM addresses WHERE is_default=1')
          == 1, 'Only one default address is retained')
    request('/account/profile', {'action': 'customer_profile',
            'name': 'Updated Customer', 'phone': '01700000001'}, cs)
    check(scalar('SELECT name FROM customers WHERE id=?', (customer,))
          == 'Updated Customer', 'Customer profile updates')
    html, state, _ = request('/cart', {'action': 'cart_add', 'variant_id': 1, 'quantity': 2}, cs)
    cs = state
    check(cs['cart']['1'] == 2, 'Cart adds selected variant and quantity')
    html, state, _ = request('/cart', {'action': 'cart_add', 'variant_id': 1, 'quantity': 999}, cs)
    check(state['cart']['1'] == 2, 'Cart prevents stock overflow')
    html, state, _ = request('/cart', {'action': 'coupon', 'code': 'hello10'}, cs)
    cs = state
    check(cs['coupon'] == 'HELLO10', 'Promo codes are case insensitive')
    html, cs, status = request('/checkout', session=cs)
    check(status == 200 and '৳33,300' in html, 'Checkout calculates 10 percent discount server-side')
    orderPost = {
        'action': 'place_order',
        'email': 'customer@test.local',
        'first_name': 'Test',
        'last_name': 'Customer',
        'phone': '01700000000',
        'address': '42 Test Road',
        'city': 'Dhaka',
        'postal': '1205',
        'payment_method': 'cod',
        'checkout_nonce': cs['checkout_nonce'],
        'total': '1'}
    oldStock = scalar('SELECT stock FROM variants WHERE id=1')
    html, after, _ = request('/checkout', orderPost, cs)
    check('/order?token=' in html, 'Checkout creates order confirmation')
    oid = scalar('SELECT MAX(id) FROM orders')
    token = scalar('SELECT token FROM orders WHERE id=?', (oid,))
    check(scalar('SELECT total FROM orders WHERE id=?', (oid,))
          == 33300, 'Client price tampering is ignored')
    check(scalar('SELECT customer_id FROM orders WHERE id=?', (oid,))
          == customer, 'Signed-in order belongs to customer')
    check(scalar('SELECT stock FROM variants WHERE id=1') ==
          oldStock - 2, 'Checkout decrements selected variant stock')
    check('cart' not in after and 'checkout_nonce' not in after,
          'Checkout clears cart and consumes submission token')
    request('/checkout', orderPost, after)
    check(scalar('SELECT COUNT(*) FROM orders') == 1,
          'Duplicate checkout does not create a second order')
    html, _, status = request('/order?token=' + token, query={'token': token})
    check(status == 200 and 'FF-' in html, 'Guest-safe token opens order confirmation')
    html, _, status = request('/invoice?token=' + token, query={'token': token})
    check(status == 200 and 'Invoice' in html and '৳33,300' in html, 'Invoice shows saved order values')
    html, _, status = request('/invoice?token=invalid', query={'token': 'invalid'})
    check(status == 404, 'Unknown invoice token returns 404')
    html, _, _ = request('/account/orders', session={'customer_id': customer})
    check(token in html, 'Customer order history includes their order')
    html, astate, _ = request(
        '/admin/login', {'action': 'login', 'email': 'admin@test.local', 'password': 'MerchantPassword123'})
    check(astate.get('admin_id') == 1, 'Merchant authentication works')
    admin = {'admin_id': 1, 'admin_name': 'Owner'}
    for route in [
        '/admin',
        '/admin/products',
        '/admin/products/edit',
        '/admin/orders',
        '/admin/customers',
        '/admin/collections',
        '/admin/discounts',
        '/admin/analytics',
        '/admin/sliders',
            '/admin/settings']:
        html, _, status = request(route, session=admin)
        check(status == 200 and '<html' in html and 'We couldn’t load' not in html, 'Render ' + route)
    for route in ['/account', '/account/profile', '/account/addresses']:
        html, _, status = request(route, session={'customer_id': customer})
        check(status == 200 and '<html' in html, 'Render ' + route)
    request('/admin/orders/view', {'action': 'save_order', 'id': oid,
            'status': 'cancelled', 'payment_status': 'unpaid'}, admin)
    check(scalar('SELECT stock FROM variants WHERE id=1')
          == oldStock, 'Cancellation restores stock')
    request('/admin/orders/view', {'action': 'save_order', 'id': oid,
            'status': 'cancelled', 'payment_status': 'unpaid'}, admin)
    check(scalar('SELECT stock FROM variants WHERE id=1') ==
          oldStock, 'Repeated cancellation does not duplicate stock')
    request('/admin/orders/view', {'action': 'save_order', 'id': oid,
            'status': 'shipped', 'payment_status': 'unpaid'}, admin)
    check(scalar('SELECT status FROM orders WHERE id=?', (oid,))
          == 'cancelled', 'Cancelled order cannot be reopened')
    request('/admin/discounts',
            {'action': 'save_coupon',
             'code': 'BIG500',
             'type': 'fixed',
             'amount': '500',
             'minimum': '1000',
             'expires_at': ''},
            admin)
    check(scalar("SELECT amount FROM coupons WHERE code='BIG500'")
          == 500, 'Merchant creates fixed-amount promo code')
    html, cstate, _ = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 2})
    html, cstate, _ = request('/cart', {'action': 'coupon', 'code': 'BIG500'}, cstate)
    html, cstate, _ = request('/checkout', session=cstate)
    check('৳1,320' in html, 'Fixed discount plus shipping calculated correctly')
    execute("UPDATE coupons SET expires_at='2001-01-01' WHERE code='BIG500'")
    html, cstate, _ = request('/cart', {'action': 'coupon', 'code': 'BIG500'}, cstate)
    check(cstate.get('flash', {}).get('type') == 'error', 'Expired promo code rejected')
    html, cstate, _ = request('/cart', {'action': 'cart_update',
                              'quantities': {'3': '2'}, 'remove_variant': '3'}, cstate)
    check(not cstate.get('cart'), 'Cart remove action works')
    slug3 = scalar('SELECT p.slug FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=3')
    html, ux, _ = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 1})
    check('/product/' + slug3 in html and ux.get('flash', {}).get('link', {}).get('href') == '/cart',
          'Add to bag returns to the product with a View bag link')
    html, jx, status = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 1}, accept='application/json')
    data = json.loads(html)
    check(status == 200 and data['ok'] and data['count'] == 1 and 'View bag' in data['flash'] and 'flash' not in jx,
          'Add to bag answers JSON with bag count and rendered notice for scripts')
    html, _, status = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 999}, jx, accept='application/json')
    check(status == 409 and not json.loads(html)['ok'], 'Unavailable quantity is reported to scripts as an error')
    _, ux, _ = request('/cart', {'action': 'cart_add', 'variant_id': 1, 'quantity': 1}, ux)
    html, ux, _ = request('/checkout', {'action': 'coupon', 'code': 'HELLO10', 'return_to': '/checkout',
                          'email': 'typed@test.local', 'city': 'Sylhet', 'first_name': ''}, ux)
    check('/checkout' in html and ux.get('coupon') == 'HELLO10'
          and ux.get('checkout_old', {}).get('city') == 'Sylhet' and 'first_name' not in ux.get('checkout_old', {}),
          'Promo applied at checkout returns there and keeps typed details')
    html, ux, _ = request('/checkout', session=ux)
    check('value="Sylhet"' in html and 'coupon-form' in html, 'Checkout restores typed details and offers promo field')
    html, ux, _ = request('/cart', {'action': 'coupon', 'code': 'HELLO10', 'return_to': 'https://evil.test'}, ux)
    check('evil.test' not in html, 'Promo return path is restricted to bag or checkout')
    html, cstate, _ = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 2})
    html, cstate, _ = request('/checkout', session=cstate)
    bank = {
        **orderPost,
        'payment_method': 'bank',
        'checkout_nonce': cstate['checkout_nonce'],
        'email': 'guest@test.local'}
    html, _, _ = request('/checkout', bank, cstate)
    check('/order?token=' in html and scalar('SELECT payment_method FROM orders ORDER BY id DESC LIMIT 1')
          == 'bank', 'Guest bank-transfer checkout creates unpaid order')
    check(scalar('SELECT customer_id FROM orders ORDER BY id DESC LIMIT 1')
          is None, 'Guest order is separate from customer accounts')
    request('/admin/discounts', {'action': 'save_coupon', 'code': 'TWICE', 'type': 'fixed', 'amount': '100',
            'minimum': '0', 'expires_at': '', 'max_uses': '2', 'per_customer': '1'}, admin)
    couponStock = scalar('SELECT stock FROM variants WHERE id=3')
    twice = scalar("SELECT id FROM coupons WHERE code='TWICE'")
    html, _, status = request('/admin/discounts', session=admin, query={'edit': str(twice)})
    check(status == 200 and 'Edit TWICE' in html and 'value="2"' in html, 'Merchant opens a promo code for editing')
    request('/admin/discounts', {'action': 'save_coupon', 'id': str(twice), 'code': 'RENAMED', 'type': 'fixed',
            'amount': '150', 'minimum': '0', 'expires_at': '', 'max_uses': '', 'per_customer': '1'}, admin)
    check(scalar("SELECT COUNT(*) FROM coupons WHERE code='RENAMED'") == 0
          and scalar('SELECT amount FROM coupons WHERE id=?', (twice,)) == 150
          and scalar('SELECT max_uses FROM coupons WHERE id=?', (twice,)) is None,
          'Editing a promo code updates its terms, clears limits, and keeps its code')
    request('/admin/discounts', {'action': 'save_coupon', 'id': str(twice), 'type': 'fixed', 'amount': '100',
            'minimum': '0', 'expires_at': '', 'max_uses': '2', 'per_customer': '1'}, admin)

    def coupon_checkout(email):
        _, st, _ = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 1})
        _, st, _ = request('/cart', {'action': 'coupon', 'code': 'TWICE'}, st)
        if 'coupon' not in st:
            return None, st
        _, st, _ = request('/checkout', session=st)
        before = scalar('SELECT COUNT(*) FROM orders')
        _, st, _ = request('/checkout', {**orderPost, 'email': email, 'checkout_nonce': st['checkout_nonce']}, st)
        return scalar('SELECT COUNT(*) FROM orders') - before, st

    placed, _ = coupon_checkout('first@test.local')
    check(placed == 1 and scalar("SELECT discount FROM orders ORDER BY id DESC LIMIT 1") == 100,
          'Limited promo code applies on first use')
    placed, st = coupon_checkout('FIRST@test.local')
    check(placed == 0 and 'usage limit' in st.get('flash', {}).get('text', '') and 'coupon' not in st,
          'Per-customer promo limit blocks reuse by the same email at checkout')
    placed, _ = coupon_checkout('second@test.local')
    check(placed == 1, 'Another customer can use the remaining promo allowance')
    placed, st = coupon_checkout('third@test.local')
    check(placed is None and st.get('flash', {}).get('type') == 'error',
          'Promo code is rejected once total uses are exhausted')
    execute("UPDATE orders SET status='cancelled' WHERE email='second@test.local'")
    placed, _ = coupon_checkout('third@test.local')
    check(placed == 1, 'Cancelled orders free up promo code uses')
    execute("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE coupon='TWICE')")
    execute("DELETE FROM orders WHERE coupon='TWICE'")
    execute('UPDATE variants SET stock=? WHERE id=3', (couponStock,))

    # Multi-currency: merchant adds USD, shoppers browse and buy in it, reports stay in BDT.
    request('/admin/currencies', {'action': 'save_currency', 'code': 'usd', 'symbol': '$', 'decimals': '2',
            'rate': '120', 'position': '1'}, admin)
    check(scalar("SELECT decimals FROM currencies WHERE code='USD'") == 2, 'Merchant adds a currency')
    html, _, status = request('/admin/currencies', session=admin)
    check(status == 200 and 'USD' in html and '1 USD = 120 BDT' in html, 'Currencies page lists rates')
    request('/admin/currencies', {'action': 'save_currency', 'code': 'USD', 'symbol': 'US$', 'decimals': '0',
            'rate': '120', 'position': '1'}, admin)
    check(scalar("SELECT decimals FROM currencies WHERE code='USD'") == 2
          and scalar("SELECT symbol FROM currencies WHERE code='USD'") == 'US$',
          'Editing a currency keeps its decimal places fixed')
    execute("UPDATE currencies SET symbol='$' WHERE code='USD'")
    html, usd, _ = request('/', {'action': 'set_currency', 'currency': 'USD', 'return_to': '/shop?sort=newest'})
    check(usd.get('currency') == 'USD' and '/shop?sort=newest' in html, 'Shopper switches currency and returns to the page')
    html, bad, _ = request('/', {'action': 'set_currency', 'currency': 'XXX', 'return_to': '//evil.test'}, usd)
    check(bad.get('currency') == 'USD' and 'evil.test' not in html, 'Unknown currency and off-site return are ignored')
    price3 = scalar('SELECT price FROM variants WHERE id=3')
    usd3 = '$' + format(round(price3 / 120 * 100) / 100, ',.2f')
    html, _, status = request('/product/' + slug3, session=usd)
    check(status == 200 and usd3 in html and 'currency-select' in html, 'Product price converts at the rate: ' + usd3)
    execute("INSERT INTO variant_prices(variant_id,currency,amount,compare_amount) VALUES(3,'USD',2999,3999)")
    html, _, _ = request('/product/' + slug3, session=usd)
    check('$29.99' in html and '$39.99' in html, 'Explicit per-currency price and sale price win over conversion')
    _, usd, _ = request('/cart', {'action': 'cart_add', 'variant_id': 3, 'quantity': 2}, usd)
    for route in ['/', '/shop', '/compare', '/build-a-set', '/recent', '/saved', '/about', '/cart', '/checkout']:
        html, usd, status = request(route, session=usd)
        # The switcher lists BDT and the shop's price filters are in BDT; no price may show taka.
        body = re.sub(r'<form method="post" class="currency-switcher">.*?</form>', '', html, flags=re.S)
        check(status == 200 and '<html' in html and '৳' not in body.split('<footer')[0].replace('(৳)', ''),
              'Storefront renders in USD without taka prices: ' + route)
    html, usd, _ = request('/checkout', session=usd)
    check('$59.98' in html, 'Checkout totals are in the shopper currency')
    _, stale, _ = request('/checkout', {**orderPost, 'email': 'usd@test.local', 'currency': 'BDT',
                          'checkout_nonce': usd['checkout_nonce']}, usd)
    check('currency changed' in stale.get('flash', {}).get('text', ''), 'Checkout refuses a stale currency')
    html, after, _ = request('/checkout', {**orderPost, 'email': 'usd@test.local', 'currency': 'USD',
                             'checkout_nonce': usd['checkout_nonce']}, usd)
    usdOrder = scalar("SELECT id FROM orders WHERE email='usd@test.local'")
    check(usdOrder and scalar('SELECT currency FROM orders WHERE id=?', (usdOrder,)) == 'USD'
          and scalar('SELECT subtotal FROM orders WHERE id=?', (usdOrder,)) == 5998
          and abs(scalar('SELECT rate_to_base FROM orders WHERE id=?', (usdOrder,)) - 1.2) < 1e-9,
          'USD order stores cents, currency and rate to BDT')
    usdToken = scalar('SELECT token FROM orders WHERE id=?', (usdOrder,))
    html, _, _ = request('/order', query={'token': usdToken})
    check('$59.98' in html, 'Order confirmation shows the order currency even in a BDT session')
    html, _, _ = request('/invoice', query={'token': usdToken})
    check('Currency: USD' in html and '$29.99' in html, 'Invoice shows the order currency')
    html, _, _ = request('/admin/orders/view', session=admin, query={'id': str(usdOrder)})
    check('$29.99' in html, 'Merchant order view shows the order currency')
    html, _, _ = request('/admin/analytics', session=admin)
    check(status == 200 and '$' not in html.split('<main')[-1], 'Analytics totals stay in BDT')
    execute('DELETE FROM order_items WHERE order_id=?', (usdOrder,))
    execute('DELETE FROM orders WHERE id=?', (usdOrder,))
    execute('UPDATE variants SET stock=? WHERE id=3', (couponStock,))
    execute('DELETE FROM variant_prices')
    execute('DELETE FROM currencies')
    request('/admin/products/edit',
            {'action': 'save_product',
             'id': '0',
             'name': 'Test Product',
             'slug': 'test-product',
             'description': 'A test product',
             'category_id': '1',
             'material': 'Test',
             'care': 'Test',
             'active': '1',
             'variants': [{'sku': 'TEST-001',
                           'color': 'Black',
                           'size': 'S',
                           'price': '1200',
                           'compare_price': '1500',
                           'stock': '5'},
                          {'sku': 'TEST-002',
                           'color': 'White',
                           'size': 'L',
                           'price': '1300',
                           'compare_price': '1600',
                           'stock': '8'}]},
            admin)
    check(
        scalar("SELECT COUNT(*) FROM variants v JOIN products p ON p.id=v.product_id WHERE p.slug='test-product'") == 2,
        'Merchant creates product with two variants')
    html, _, status = request('/admin/products/edit?id=1', session=admin, query={'id': '1'})
    check(status == 200 and 'Arc lounge chair' in html,
          'Product editor renders stored gallery and variants')
    html, _, status = request('/admin/orders/view?id=1', session=admin, query={'id': '1'})
    check(status == 200 and 'Order items' in html, 'Merchant order details render')
    # Inputs stay values, including quotes, comments, UNIONs, and hostile sort/page strings.
    for payload in [
        "' OR 1=1 -- ",
        "'; DROP TABLE products; --",
            "' UNION SELECT password FROM admins --"]:
        html, _, status = request('/shop', query={'q': payload})
        check(status == 200 and scalar('SELECT COUNT(*) FROM products') == 16,
              'Search treats SQL payload as data: ' + payload)
    html, _, status = request(
        '/shop', query={'sort': 'price ASC; DROP TABLE products', 'page': '1;DROP TABLE orders'})
    check(status == 200 and scalar('SELECT COUNT(*) FROM products') == 16,
          'Hostile sorting and pagination cannot change SQL structure')
    html, _, _ = request('/admin/login', {'action': 'login',
                         'email': "' OR 1=1 --", 'password': 'irrelevant'})
    check('/admin/login' in html, 'SQL-like login identifier does not bypass authentication')
    for n in range(8):
        request('/account/login', {'action': 'customer_login', 'email': 'sprayed@test.local',
                'password': 'wrong-password-%d' % n}, ip='10.0.0.%d' % n)
    _, state, _ = request('/account/login', {'action': 'customer_login', 'email': 'sprayed@test.local',
                          'password': 'wrong'}, ip='10.0.9.9')
    check('Too many' in state.get('flash', {}).get('text', ''),
          'Account lockout holds when attempts come from many IPs')
    html, _, status = request('/invoice', query={'token': "' OR 1=1 --"})
    check(status == 404, 'SQL-like invoice token does not expose an order')
    html, _, status = request('/admin/products', session=admin, query={'q': "' OR 1=1 --"})
    check(status == 200 and scalar('SELECT COUNT(*) FROM products') == 16,
          'Merchant search is parameterized')
    html, _, status = request('/shop', query={'page': '2'})
    check(status == 200 and html.count('class="product-card"') == 4,
          'SQL pagination returns only the second page of products')
    secondPageQueries = json.loads((work / 'storage/state.json').read_text())['queries']
    html, _, status = request('/shop', query={'page': '1'})
    firstPageQueries = json.loads((work / 'storage/state.json').read_text())['queries']
    check(firstPageQueries == secondPageQueries and firstPageQueries <= 8,
          'Product grid query count stays constant for 4 and 12 products')
    addressId = scalar('SELECT MIN(id) FROM addresses WHERE customer_id=?', (customer,))
    request('/account/addresses', {**addr, 'address_id': str(addressId),
            'address': 'Updated street'}, {'customer_id': customer})
    check(scalar('SELECT address FROM addresses WHERE id=?', (addressId,)) == 'Updated street',
          'Customer can edit their saved address')
    execute('INSERT INTO customers(name,email,password) VALUES(?,?,?)',
            ('Other', 'other@test.local', 'not-used'))
    otherId = scalar("SELECT id FROM customers WHERE email='other@test.local'")
    request('/account/addresses',
            {**addr,
             'address_id': str(addressId),
             'address': 'Unauthorized'},
            {'customer_id': otherId})
    check(scalar('SELECT address FROM addresses WHERE id=?', (addressId,)) == 'Updated street',
          'Customer cannot edit another account address')
    html, _, _ = request('/account/orders', session={'customer_id': otherId})
    check(token not in html, 'Customer cannot view another account order history')
    # Uploads must decode as supported images, and stored files must be re-encoded WebP.
    productId = scalar("SELECT id FROM products WHERE slug='test-product'")
    form = {'action': 'save_product', 'id': str(productId), 'name': 'Test Product',
            'slug': 'test-product', 'description': 'Upload test', 'category_id': '1',
            'material': 'Test', 'care': 'Test', 'active': '1',
            'variants': [{'sku': 'UPLOAD-TEST', 'color': 'Black', 'size': 'Standard',
                          'price': '1200', 'compare_price': '1500', 'stock': '5'}]}
    image = ROOT / 'public/assets/product-1.jpg'
    files = {'images': {'name': ['test.jpg'], 'type': ['image/jpeg'],
                        'tmp_name': [str(image)], 'size': [image.stat().st_size], 'error': [0]}}
    request('/admin/products/edit', form, admin, files=files)
    uploaded = scalar(
        'SELECT image FROM product_images WHERE product_id=? ORDER BY id DESC LIMIT 1', (productId,))
    check(uploaded.startswith('/uploads/') and (work / 'public' / uploaded.lstrip('/')
                                                ).read_bytes()[:4] == b'RIFF', 'Valid image upload is re-encoded as a local WebP')
    badImage = work / 'storage/malicious.svg'
    badImage.write_text('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')
    files['images'].update({'name': ['malicious.svg'], 'type': ['image/svg+xml'],
                            'tmp_name': [str(badImage)], 'size': [badImage.stat().st_size]})
    _, invalidUpload, _ = request('/admin/products/edit', form, admin, files=files)
    check(invalidUpload.get('flash', {}).get('type') ==
          'error', 'Executable SVG upload is rejected')
    print(
        'Catalog query counts:',
        firstPageQueries,
        'for 12 cards;',
        secondPageQueries,
        'for 4 cards')
    # Settings tabs must update their own group without clearing unrelated fields.
    settingsCases = [
        ('general', {'store_name': 'Form & Field', 'store_email': 'owner@test.local',
                     'store_phone': '01711111111', 'store_address': '42 Merchant Road'}),
        ('delivery', {'shipping': '150', 'free_shipping': '6000', 'tax_rate': '5'}),
        ('payments', {'bank_details': 'Account 1234, Example Bank'}),
        ('storefront', {'announcement': 'New arrivals are here.'}),
    ]
    for group, values in settingsCases:
        before = dict(conn.execute('SELECT `key`,value FROM settings').fetchall())
        html, _, _ = request('/admin/settings',
                             {'action': 'save_settings', 'settings_group': group, **values}, admin)
        after = dict(conn.execute('SELECT `key`,value FROM settings').fetchall())
        check(html == '__REDIRECT__:/admin/settings#' + group
              and all(after[key] == value for key, value in values.items())
              and all(after[key] == value for key, value in before.items() if key not in values),
              'Settings ' + group + ' tab saves only its own values and keeps active tab')
    before = dict(conn.execute('SELECT `key`,value FROM settings').fetchall())
    request('/admin/settings',
            {'action': 'save_settings',
             'settings_group': 'payments',
             'bank_details': 'Account 5678',
             'store_name': 'Unexpected change'},
            admin)
    check(scalar("SELECT value FROM settings WHERE `key`='store_name'") == before['store_name'],
          'Settings tab ignores injected fields from a different group')
    before = dict(conn.execute('SELECT `key`,value FROM settings').fetchall())
    html, failedSave, _ = request('/admin/settings', {
        'action': 'save_settings', 'settings_group': 'delivery',
        'shipping': '999', 'free_shipping': '1', 'tax_rate': '101'}, admin)
    check(dict(conn.execute('SELECT `key`,value FROM settings').fetchall()) == before
          and failedSave.get('flash', {}).get('type') == 'error'
          and html.endswith('#delivery'),
          'Invalid settings leave all saved values unchanged and preserve error tab')
    _, invalidScope, _ = request('/admin/settings', {
        'action': 'save_settings', 'settings_group': "general'; DROP TABLE settings; --",
        'store_name': 'Unexpected change'}, admin)
    check(invalidScope.get('flash', {}).get('type') == 'error'
          and dict(conn.execute('SELECT `key`,value FROM settings').fetchall()) == before,
          'Invalid settings scope is rejected without database changes')
    html, _, _ = request('/admin/settings', {'action': 'save_settings',
                                             'settings_group': 'payments', 'bank_details': 'Unauthorized'})
    check('/admin/login' in html and dict(conn.execute('SELECT `key`,value FROM settings').fetchall())
          == before, 'Settings saves require merchant authentication')
    html, _, status = request('/admin/settings', session=admin)
    check(status == 200 and html.count('data-settings-tab=') == 5
          and html.count('class="settings-panel"') == 5
          and '<div class="admin-content">' in html,
          'All five settings panels render within the full-height admin content layout')
    # Catalog filters compose against one variant and sort by its matching price.

    def product_ids(html):
        return [int(value) for value in re.findall(r'<article\b[^>]*data-product-id="(\d+)"', html)]

    html, _, status = request(
        '/shop', query={'min_price': '1000', 'max_price': '2000', 'sort': 'price-asc'})
    check(status == 200 and product_ids(html) == [16, 5, 3, 13, 8],
          'Price filtering and ascending price sorting return matching products')
    html, _, _ = request('/shop', query={'min_price': '2000',
                         'max_price': '1000', 'sort': 'price-asc'})
    check(product_ids(html) == [16, 5, 3, 13, 8], 'Reversed price bounds are normalized')
    html, _, status = request(
        '/shop', query={'category': 'electronics', 'min_price': '3000', 'max_price': '7000', 'in_stock': '1'})
    check(status == 200 and set(product_ids(html)) == {9, 10, 11},
          'Category, price, and in-stock filters work together')
    html, _, status = request(
        '/shop', query={'min_price': "0) OR 1=1 --", 'max_price': "0'; DROP TABLE variants; --"})
    check(status == 200 and scalar('SELECT COUNT(*) FROM products') == 16,
          'SQL payloads in price fields do not enter SQL syntax')
    execute(
        'INSERT INTO variants(product_id,sku,color,size,price,compare_price,stock) VALUES(?,?,?,?,?,?,?)',
        (16,
         'FILTER-EXPENSIVE',
         'Slate',
         'Premium',
         9000,
         10000,
         0))
    html, _, _ = request('/shop', query={'min_price': '8000',
                         'max_price': '10000', 'in_stock': '1'})
    check(16 not in product_ids(html), 'In-stock and price filters cannot match different variants')
    html, _, _ = request('/shop', query={'min_price': '8000', 'max_price': '10000'})
    check(product_ids(html) == [16] and '৳9,000' in html,
          'Catalog displays the price of the variant matching its filters')
    execute("UPDATE variants SET stock=1 WHERE sku='FILTER-EXPENSIVE'")
    execute(
        'INSERT INTO variants(product_id,sku,color,size,price,compare_price,stock) VALUES(?,?,?,?,?,?,?)',
        (1,
         'FILTER-CHAIR',
         'Test',
         'Test',
         8500,
         9000,
         2))
    html, _, _ = request('/shop', query={'min_price': '8000', 'max_price': '10000',
                         'in_stock': '1', 'sale': '1', 'sort': 'price-asc'})
    check(
        product_ids(html) == [
            1,
            16],
        'Price sorting uses matching variants with a stable product-ID tiebreaker')
    execute("DELETE FROM variants WHERE sku IN ('FILTER-EXPENSIVE','FILTER-CHAIR')")
    html, _, _ = request('/shop', query={'sale': '1'})
    check(set(product_ids(html)) == {1, 3, 7, 9, 11, 13, 16},
          'Sale filtering excludes full-price products')

    # Wishlist routes accept only bounded numeric product IDs; state is kept by browser JS.
    html, _, status = request('/saved', query={'items': '1,9,999999'})
    check(status == 200 and set(product_ids(html)) == {
          1, 9}, 'Saved page returns only existing active products')
    html, _, status = request('/saved', query={'items': "1,9,0) OR 1=1 --,1"})
    check(status == 200 and set(product_ids(html)) == {
          1, 9}, 'Saved item IDs are deduplicated and reject SQL fragments')
    execute('UPDATE products SET active=0 WHERE id=9')
    html, _, _ = request('/saved', query={'items': '1,9'})
    check(product_ids(html) == [1], 'Archived products are excluded from saved lists')
    execute('UPDATE products SET active=1 WHERE id=9')
    html, _, status = request('/saved')
    check(status == 200 and 'A place for your favorites.' in html and not product_ids(html),
          'Saved page includes a useful empty state without JavaScript or stored items')

    # Buy again must enforce ownership, current prices, and available stock.
    countBefore = scalar('SELECT COUNT(*) FROM orders')
    execute('UPDATE variants SET stock=1,price=19000 WHERE id=1')
    html, readded, _ = request(
        '/account/orders', {
            'action': 'customer_reorder', 'order_id': str(oid)}, {
            'customer_id': customer, 'cart': {
                '3': 1}})
    check(html.endswith('/cart') and readded['cart']['1'] == 1 and readded['cart']['3'] == 1,
          'Buy again caps stock and preserves existing cart items')
    html, _, _ = request('/cart', session=readded)
    check('৳19,000' in html and scalar('SELECT COUNT(*) FROM orders') == countBefore,
          'Buy again uses current prices and does not immediately create an order')
    html, _, _ = request('/invoice', query={'token': token})
    check('৳33,300' in html, 'Buying again leaves the historical invoice unchanged')
    html, refused, _ = request(
        '/account/orders', {
            'action': 'customer_reorder', 'order_id': str(oid)}, {
            'customer_id': otherId, 'cart': {
                '3': 1}})
    check(html.endswith('/account/orders') and refused['cart'] == {'3': 1}
          and refused.get('flash', {}).get('type') == 'error',
          'Customers cannot reorder another account order')
    html, _, _ = request('/account/orders', {'action': 'customer_reorder', 'order_id': str(oid)})
    check('/account/login' in html, 'Buy again requires customer authentication')
    execute('UPDATE products SET active=0 WHERE id=1')
    _, unavailable, _ = request(
        '/account/orders', {
            'action': 'customer_reorder', 'order_id': str(oid)}, {
            'customer_id': customer, 'cart': {
                '3': 1}})
    check(unavailable['cart'] == {'3': 1} and unavailable.get('flash', {}).get('type') == 'error',
          'Unavailable reorder items leave the existing cart unchanged')
    execute('UPDATE products SET active=1 WHERE id=1')
    execute('UPDATE variants SET stock=?,price=18500 WHERE id=1', (oldStock,))

    # CSV export requires admin access, shares filters, and neutralizes spreadsheet formulas.
    import csv
    import io
    html, _, _ = request('/admin/orders/export')
    check('/admin/login' in html, 'Order exports require merchant authentication')
    html, _, status = request('/admin/orders/export', session=admin, query={'status': 'cancelled'})
    rows = list(csv.reader(io.StringIO(html.lstrip('\ufeff'))))
    check(status == 200 and len(rows) == 2 and rows[1][0] == scalar(
        'SELECT number FROM orders WHERE id=?', (oid,)), 'Order CSV export respects fulfillment filters')
    execute('UPDATE orders SET first_name=?,phone=? WHERE id=?', ('=2+2', '+8801711111111', oid))
    html, _, _ = request('/admin/orders/export', session=admin, query={'status': 'cancelled'})
    rows = list(csv.reader(io.StringIO(html.lstrip('\ufeff'))))
    check(rows[1][2].startswith("'=2+2") and rows[1][4].startswith("'+880"),
          'Spreadsheet formulas and leading-plus phone fields are exported as text')
    execute('UPDATE orders SET first_name=?,phone=? WHERE id=?', ('Test', '01700000000', oid))
    html, _, _ = request('/admin/orders/export', session=admin, query={'q': "' OR 1=1 --"})
    check(len(list(csv.reader(io.StringIO(html.lstrip('\ufeff'))))) == 1,
          'CSV search input is bound and cannot broaden export access')
    columns = [column[1] for column in conn.execute(
        'PRAGMA table_info(orders)').fetchall() if column[1] != 'id']
    record = dict(zip(columns, conn.execute('SELECT ' + ','.join(columns) +
                  ' FROM orders WHERE id=?', (oid,)).fetchone()))
    clones = []
    for index in range(501):
        item = {
            **record,
            'number': 'FF-EXPORT-' + str(index),
            'token': 'export-test-' + str(index),
            'status': 'processing'}
        clones.append([item[column] for column in columns])
    conn.executemany(
        'INSERT INTO orders(' +
        ','.join(columns) +
        ') VALUES(' +
        ','.join(
            '?' for _ in columns) +
        ')',
        clones)
    conn.commit()
    html, _, _ = request('/admin/orders/export', session=admin, query={'status': 'processing'})
    rows = list(csv.reader(io.StringIO(html.lstrip('\ufeff'))))
    check(len(rows) == 502 and len({row[0] for row in rows[1:]}) == 501,
          'CSV cursor pagination exports every order once across a 500-row boundary')
    execute("DELETE FROM orders WHERE number LIKE 'FF-EXPORT-%'")
    html, _, status = request('/')
    check(
        status == 200 and 'new-arrivals-section' in html and 'home-tech' in html and 'home-faq' in html,
        'Expanded homepage renders new arrivals, electronics, and practical FAQs')
    check('৳150' in html and '৳6,000' in html, 'Homepage delivery FAQ reads current store settings')

    # New reviews are private until moderated, with one review per customer/product.
    reviewPost = {
        'action': 'customer_review',
        'product_id': '1',
        'rating': '5',
        'title': 'Comfortable chair',
        'body': 'The chair is comfortable and the frame feels sturdy.'}
    html, _, status = request('/product/arc-lounge-chair')
    check(status == 200 and 'No reviews yet.' in html and 'Sign in to review' in html,
          'Products start with an honest empty review state and a sign-in prompt')
    html, _, _ = request('/product/arc-lounge-chair', reviewPost)
    check('/account/login' in html and scalar('SELECT COUNT(*) FROM product_reviews') == 0,
          'Review submission requires customer authentication')
    html, _, status = request('/product/arc-lounge-chair', reviewPost,
                              {'customer_id': customer}, raw_csrf=True)
    check(status == 419 and scalar('SELECT COUNT(*) FROM product_reviews') == 0,
          'Review submissions reject invalid CSRF tokens')
    for rating in ['0', '6', "5 OR 1=1"]:
        _, invalidReview, _ = request('/product/arc-lounge-chair', {**reviewPost, 'rating': rating},
                                      {'customer_id': customer})
        check(invalidReview.get('flash', {}).get('type') == 'error'
              and scalar('SELECT COUNT(*) FROM product_reviews') == 0,
              'Invalid review rating is rejected: ' + rating)
    _, draftState, _ = request('/product/arc-lounge-chair', {**reviewPost, 'body': 'short'},
                               {'customer_id': customer})
    check(draftState.get('flash', {}).get('type') == 'error'
          and scalar('SELECT COUNT(*) FROM product_reviews') == 0,
          'Review text length is validated before any write')
    html, _, _ = request('/product/arc-lounge-chair',
                         session={**draftState, 'customer_id': otherId})
    check('Comfortable chair' not in html, 'Unsubmitted review drafts are private to their customer')
    html, _, _ = request('/product/arc-lounge-chair', reviewPost, {'customer_id': customer})
    reviewId = scalar(
        'SELECT id FROM product_reviews WHERE product_id=1 AND customer_id=?', (customer,))
    check(html.endswith('#review-form') and scalar('SELECT status FROM product_reviews WHERE id=?',
          (reviewId,)) == 'pending', 'Submitted review is stored as pending moderation')
    html, _, _ = request('/product/arc-lounge-chair')
    check('Comfortable chair' not in html and '0 published reviews' in html,
          'Pending reviews do not appear publicly or inflate rating totals')
    html, _, _ = request('/product/arc-lounge-chair', session={'customer_id': customer})
    check('Comfortable chair' in html and 'awaiting moderation' in html,
          'Review author can see and edit their own pending feedback')
    html, _, _ = request('/admin/reviews')
    check('/admin/login' in html, 'Review moderation requires merchant authentication')
    html, _, status = request('/admin/reviews', session=admin)
    check(status == 200 and 'Comfortable chair' in html and 'Publish' in html,
          'Merchant moderation queue displays pending reviews')
    html, _, _ = request('/admin/reviews', {'action': 'moderate_review', 'review_id': str(
        reviewId), 'revision': '1', 'status': 'approved'}, {'customer_id': otherId})
    check('/admin/login' in html and scalar('SELECT status FROM product_reviews WHERE id=?',
          (reviewId,)) == 'pending', 'Customer cannot publish a review using an admin action')
    execute("UPDATE orders SET status='delivered',payment_status='paid' WHERE id=?", (oid,))
    request('/admin/reviews',
            {'action': 'moderate_review',
             'review_id': str(reviewId),
             'revision': '1',
             'status': 'approved',
             'rating': '1',
             'body': 'Admin replacement'},
            admin)
    check(scalar('SELECT verified_purchase FROM product_reviews WHERE id=?', (reviewId,)) == 1,
          'Approval derives verified purchase from a paid delivered order')
    check(scalar('SELECT body FROM product_reviews WHERE id=?', (reviewId,)) == reviewPost['body'],
          'Moderation changes publication state without rewriting customer feedback')
    html, _, _ = request('/product/arc-lounge-chair')
    check('Comfortable chair' in html and 'Verified purchase' in html
          and 'Updated C.' in html and 'customer@test.local' not in html,
          'Published review shows verified status and a privacy-preserving author name')
    html, _, _ = request('/shop')
    check('Rated 5.0 out of 5 from 1 review' in html,
          'Catalog cards show only actual approved rating aggregates')
    _, _, _ = request('/product/arc-lounge-chair', {
        **reviewPost, 'rating': '1', 'title': 'Not the right fit',
        'body': 'The chair did not fit my room as expected.', 'verified_purchase': '1'}, {'customer_id': otherId})
    otherReviewId = scalar(
        'SELECT id FROM product_reviews WHERE product_id=1 AND customer_id=?', (otherId,))
    check(scalar('SELECT verified_purchase FROM product_reviews WHERE id=?', (otherReviewId,)) == 0,
          'Customers cannot forge verified-purchase badges in submitted data')
    request('/admin/reviews', {'action': 'moderate_review', 'review_id': str(otherReviewId),
                               'revision': '1', 'status': 'approved'}, admin)
    html, _, _ = request('/product/arc-lounge-chair')
    check('Not the right fit' in html and '3.0/5' in html and '2 published reviews' in html,
          'Approved negative and positive reviews both contribute to the real average')
    request('/admin/reviews', {'action': 'moderate_review', 'review_id': str(reviewId),
                               'revision': '1', 'status': 'rejected'}, admin)
    html, _, _ = request('/product/arc-lounge-chair')
    check('Comfortable chair' not in html and '1.0/5' in html,
          'Rejected reviews are excluded from public text and rating aggregates')
    request('/product/arc-lounge-chair',
            {**reviewPost,
             'rating': '3',
             'title': 'Updated experience'},
            {'customer_id': customer})
    check(
        scalar(
            'SELECT COUNT(*) FROM product_reviews WHERE product_id=1 AND customer_id=?',
            (customer,
             )) == 1 and scalar(
            'SELECT revision FROM product_reviews WHERE id=?',
            (reviewId,
             )) == 2 and scalar(
            'SELECT status FROM product_reviews WHERE id=?',
            (reviewId,
             )) == 'pending',
        'Editing reuses the same review and returns it to moderation with a new revision')
    _, staleApproval, _ = request('/admin/reviews', {'action': 'moderate_review', 'review_id': str(
        reviewId), 'revision': '1', 'status': 'approved'}, admin)
    check(staleApproval.get('flash', {}).get('type') == 'error'
          and scalar('SELECT status FROM product_reviews WHERE id=?', (reviewId,)) == 'pending',
          'Stale moderation cannot publish text edited after the admin opened it')
    request('/admin/reviews', {'action': 'moderate_review', 'review_id': str(reviewId),
                               'revision': '2', 'status': 'approved'}, admin)
    _, refusedWithdraw, _ = request(
        '/product/arc-lounge-chair', {
            'action': 'customer_review_delete', 'product_id': '1', 'review_id': str(reviewId)}, {
            'customer_id': otherId})
    check(refusedWithdraw.get('flash', {}).get('type') == 'error'
          and scalar('SELECT COUNT(*) FROM product_reviews WHERE id=?', (reviewId,)) == 1,
          'Customers cannot withdraw another account review')
    request('/product/arc-lounge-chair', {'action': 'customer_review_delete', 'product_id': '1',
                                          'review_id': str(reviewId)}, {'customer_id': customer})
    check(scalar('SELECT COUNT(*) FROM product_reviews WHERE id=?', (reviewId,)) == 0,
          'Authors can withdraw their own published review')
    html, _, _ = request('/product/arc-lounge-chair')
    check('Updated experience' not in html and '1.0/5' in html,
          'Withdrawn reviews disappear immediately from the storefront and average')
    xssPost = {
        **reviewPost,
        'product_id': '2',
        'rating': '4',
        'title': '<script>alert(1)</script>',
        'body': 'Review body <img src=x onerror=alert(1)> is only text.'}
    request('/product/everyday-ceramic-mug', xssPost, {'customer_id': customer})
    xssId = scalar('SELECT id FROM product_reviews WHERE product_id=2 AND customer_id=?', (customer,))
    request('/admin/reviews',
            {'action': 'moderate_review',
             'review_id': str(xssId),
             'revision': '1',
             'status': 'approved'},
            admin)
    html, _, _ = request('/product/everyday-ceramic-mug')
    check('&lt;script&gt;alert(1)&lt;/script&gt;' in html and '&lt;img src=x' in html
          and '<script>alert(1)</script>' not in html and '<img src=x' not in html,
          'Review titles and bodies are escaped against stored XSS')
    for index in range(6):
        execute('INSERT INTO customers(name,email,password) VALUES(?,?,?)',
                ('Reviewer ' + str(index), 'reviewer' + str(index) + '@test.local', 'not-used'))
        reviewerId = scalar('SELECT MAX(id) FROM customers')
        execute(
            'INSERT INTO product_reviews(product_id,customer_id,rating,title,body,status) VALUES(?,?,?,?,?,?)',
            (1,
             reviewerId,
             index %
             5 +
             1,
             'Pagination review ' +
             str(index),
                'A genuine fixture for review pagination.',
                'approved'))
    html, _, status = request('/product/arc-lounge-chair', query={'reviews_page': '2'})
    check(status == 200 and html.count('class="published-review"') == 2 and 'Page 2 of 2' in html,
          'Public reviews paginate five at a time with stable ordering')
    html, _, status = request('/product/arc-lounge-chair', query={'reviews_page': '999999999'})
    check(status == 200 and 'Page 2 of 2' in html,
          'Review pagination clamps out-of-range page values')
    html, _, status = request('/admin/reviews', session=admin,
                              query={'status': "approved'; DROP TABLE product_reviews; --", 'q': "' OR 1=1 --"})
    check(status == 200 and scalar('SELECT COUNT(*) FROM product_reviews') == 8,
          'Moderation search and status inputs cannot alter SQL structure')
    _, invalidModeration, _ = request('/admin/reviews', {'action': 'moderate_review', 'review_id': str(xssId),
                                                         'revision': '1', 'status': 'invented'}, admin)
    check(invalidModeration.get('flash', {}).get('type') == 'error'
          and scalar('SELECT status FROM product_reviews WHERE id=?', (xssId,)) == 'approved',
          'Moderation status values are allowlisted')
    execute('ALTER TABLE product_reviews RENAME TO reviews_test_hidden')
    html, _, status = request('/product/arc-lounge-chair')
    check(status == 200 and 'Add to bag' in html and 'id="reviews"' not in html,
          'Existing storefront keeps working when review migration is not installed')
    html, _, status = request('/admin/reviews', session=admin)
    check(status == 200 and 'php scripts/migrate-reviews.php' in html,
          'Merchant sees actionable setup instructions when review storage is missing')
    execute('ALTER TABLE reviews_test_hidden RENAME TO product_reviews')

    # Session-backed comparison supports native forms and progressive JSON updates.
    html, _, status = request('/compare')
    check(status == 200 and 'Start with a few possibilities.' in html,
          'Comparison has a useful empty state')
    html, compareSession, _ = request('/shop', {'action': 'compare_add', 'product_id': '1',
                                                'return_to': '/shop?category=living'})
    check(
        html == '__REDIRECT__:/shop?category=living' and compareSession['compare_products'] == [1],
        'Native comparison forms retain the current shopping route')
    _, compareSession, _ = request(
        '/shop', {'action': 'compare_add', 'product_id': '9'}, compareSession)
    _, compareSession, _ = request(
        '/shop', {'action': 'compare_add', 'product_id': '1'}, compareSession)
    check(compareSession['compare_products'] == [1, 9],
          'Adding an already compared product is idempotent')
    _, compareSession, _ = request(
        '/shop', {'action': 'compare_add', 'product_id': '3'}, compareSession)
    html, fullComparison, status = request('/shop', {'action': 'compare_add', 'product_id': '2'},
                                           compareSession, accept='application/json')
    response = json.loads(html)
    check(status == 409 and response['ok'] is False and fullComparison['compare_products'] == [
          1, 9, 3], 'Comparison rejects a fourth product without losing existing selections')
    check(all(set(item) == {'id', 'name', 'image', 'url', 'price'} for item in response['items']),
          'Comparison JSON includes catalog fields only, without customer or order data')
    html, _, status = request('/compare', session=fullComparison)
    check(status == 200 and 'comparison-table' in html and 'Arc lounge chair' in html
          and 'Tempo wireless headphones' in html and 'data-compare-equal="true"' in html,
          'Side-by-side comparison renders real details and identifies identical rows')
    html, _, _ = request('/shop', session=fullComparison)
    check('class="store compare-active"' in html,
          'Native comparison tray reserves page space without requiring JavaScript')
    html, reducedComparison, status = request('/shop', {'action': 'compare_remove', 'product_id': '9'},
                                              fullComparison, accept='application/json')
    check(status == 200 and reducedComparison['compare_products'] == [1, 3]
          and [item['id'] for item in json.loads(html)['items']] == [1, 3],
          'JSON comparison removal returns the updated live selection')
    html, unchangedComparison, status = request('/shop', {
        'action': 'compare_add', 'product_id': "1 OR 1=1"}, reducedComparison, accept='application/json')
    check(status == 409 and unchangedComparison['compare_products'] == [1, 3],
          'Comparison IDs reject SQL fragments and keep the session unchanged')
    for target in [
        'https://example.com',
        '//example.com',
        '/\\example.com',
        '/admin',
            '/shop\nX: test']:
        html, _, _ = request('/shop', {'action': 'compare_clear',
                             'return_to': target}, reducedComparison)
        check(
            html == '__REDIRECT__:/shop',
            'Comparison return route rejects unsafe target: ' +
            repr(target))
    execute('UPDATE products SET active=0 WHERE id=9')
    _, freshComparison, _ = request('/shop', {'action': 'compare_add', 'product_id': '2'},
                                    {'compare_products': [1, 3, 9]})
    check(freshComparison['compare_products'] == [1, 3, 2],
          'Archived comparison entries are removed before applying the three-product limit')
    execute('UPDATE products SET active=1 WHERE id=9')
    html, _, status = request('/shop', {'action': 'compare_clear', 'csrf': 'invalid'},
                              reducedComparison, raw_csrf=True)
    check(status == 419, 'Comparison actions require valid CSRF protection')

    # Set creation validates every selection before changing any bag item.
    html, _, status = request('/build-a-set')
    check(status == 200 and 'data-set-builder' in html and 'data-set-budget' in html and html.count(
        'data-set-submit') == 2, 'Set builder renders live choices, budget tracking, and a mobile action bar')
    stockBefore = scalar('SELECT stock FROM variants WHERE id=3')
    orderCountBefore = scalar('SELECT COUNT(*) FROM orders')
    setPost = {'action': 'cart_add_set', 'selected': ['2', '9'],
               'set_variants': {'2': '3', '9': '17'}, 'set_quantities': {'2': '1', '9': '2'},
               'total': '1', 'price': '1'}
    html, setCart, _ = request('/build-a-set', setPost, {'cart': {'1': 1}})
    check(html.endswith('/cart') and setCart['cart'] == {'1': 1, '3': 1, '17': 2},
          'A valid set adds every selected variant and preserves existing bag items')
    html, _, _ = request('/cart', session=setCart)
    check('৳13,600' in html and scalar('SELECT stock FROM variants WHERE id=3') == stockBefore
          and scalar('SELECT COUNT(*) FROM orders') == orderCountBefore,
          'Set prices come from the catalog; adding a set does not reserve stock or create an order')
    badSet = {**setPost, 'set_variants': {'2': '3', '9': '3'}}
    html, rejectedSet, _ = request('/build-a-set', badSet, {'cart': {'1': 1}})
    check(html.endswith('/build-a-set') and rejectedSet['cart'] == {'1': 1}
          and rejectedSet.get('flash', {}).get('type') == 'error',
          'Mismatched product/variant selection rejects the whole set without partial changes')
    _, limitedSet, _ = request('/build-a-set', {**setPost, 'selected': ['2'], 'set_variants': {
                               '2': '3'}, 'set_quantities': {'2': '1'}}, {'cart': {'3': stockBefore}})
    check(limitedSet['cart'] == {'3': stockBefore} and limitedSet.get('flash', {}).get(
        'type') == 'error', 'Set stock validation includes quantities already in the bag')
    _, excessiveSet, _ = request(
        '/build-a-set', {**setPost, 'set_quantities': {'2': '11', '9': '1'}}, {'cart': {'1': 1}})
    check(excessiveSet['cart'] == {'1': 1} and excessiveSet.get('flash', {}).get('type') == 'error',
          'Set quantities are restricted to 1–10 for each product')
    _, tooMany, _ = request(
        '/build-a-set', {**setPost, 'selected': ['1', '2', '3', '4', '5', '6', '7']}, {'cart': {'1': 1}})
    check(tooMany['cart'] == {'1': 1} and tooMany.get('flash', {}).get('type') == 'error',
          'Set selection is limited to six different products')
    _, duplicateSet, _ = request('/build-a-set', {**setPost, 'selected': ['2', '2']},
                                 {'cart': {'1': 1}})
    check(duplicateSet['cart'] == {'1': 1} and duplicateSet.get('flash', {}).get('type') == 'error',
          'Duplicate set products cannot bypass selection and quantity limits')
    execute('UPDATE products SET active=0 WHERE id=9')
    _, archivedSet, _ = request('/build-a-set', setPost, {'cart': {'1': 1}})
    check(archivedSet['cart'] == {'1': 1} and archivedSet.get('flash', {}).get('type') == 'error',
          'An archived product prevents the set from being partially added')
    execute('UPDATE products SET active=1 WHERE id=9')
    html, _, status = request('/build-a-set', {**setPost, 'csrf': 'invalid'}, raw_csrf=True)
    check(status == 419, 'Set creation is protected by CSRF')

    # Recently viewed history is ordered, bounded, session-local, and clearable.
    browsing = {}
    for slug in [
        'arc-lounge-chair',
        'everyday-ceramic-mug',
        'tempo-wireless-headphones',
            'arc-lounge-chair']:
        _, browsing, status = request('/product/' + slug, session=browsing)
        check(status == 200, 'Product browsing remains available with recent history: ' + slug)
    check(browsing['recent_products'] == [1, 9, 2],
          'Revisiting a product moves it to the front without duplicating history')
    html, _, status = request('/recent', session=browsing)
    check(
        status == 200 and product_ids(html) == [
            1,
            9,
            2],
        'Recently viewed page preserves browsing order and current product data')
    for (slug,) in conn.execute('SELECT slug FROM products ORDER BY id LIMIT 10').fetchall():
        _, browsing, _ = request('/product/' + slug, session=browsing)
    check(len(browsing['recent_products']) == 8 and browsing['recent_products'] == [
          10, 9, 8, 7, 6, 5, 4, 3], 'Recently viewed history retains only the latest eight products')
    html, clearedHistory, _ = request('/recent', {'action': 'recent_clear'}, browsing)
    check(html.endswith('/recent') and 'recent_products' not in clearedHistory,
          'Browsing history can be cleared without changing cart or account records')
    html, _, _ = request('/recent')
    check('A fresh start.' in html, 'Recently viewed history does not leak across independent sessions')
    html, _, _ = request('/cart', session={'cart': {'3': 1}})
    check('delivery-planner' in html and 'max_price=' in html and 'name="remove_variant"' in html,
          'Bag shows actionable delivery progress and an unambiguous remove button')

    # Save render artifacts for optional browser QA. Assets stay in the real project.
    for route, name in [('/', 'home'), ('/shop', 'shop'), ('/admin', 'admin'),
                        ('/product/arc-lounge-chair', 'product'), ('/account', 'account')]:
        html, _, _ = request(route, session=admin if route ==
                             '/admin' else {'customer_id': customer})
        assetBase = (ROOT / 'public').as_uri()
        html = html.replace('href="/assets/',
                            'href="' + assetBase + '/assets/').replace('src="/assets/',
                                                                       'src="' + assetBase + '/assets/')
        (work / (name + '.html')).write_text(html)
    print(f'\n{checks} checks passed. Temporary harness and HTML renders: {work}')
except Exception:
    print(f'\nTest failure. Temporary harness retained at {work}')
    raise
finally:
    conn.close()
