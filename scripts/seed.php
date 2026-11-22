<?php

$categories = [
    'Living' => 'Objects that bring a room together.',
    'Lighting' => 'A softer kind of light.',
    'Table & kitchen' => 'For ordinary days and shared tables.',
    'Textiles' => 'Natural textures, familiar comforts.',
];
foreach ($categories as $name => $desc) {
    q('INSERT INTO categories(name,slug,description) VALUES(?,?,?)', [$name, slugify($name), $desc]);
}

$data = [
    [
        'Arc lounge chair',
        1,
        18500,
        22000,
        'Oak / natural linen',
        'Solid oak & linen',
        'An easy place to slow down. A sculptural solid oak frame meets soft, woven linen in a chair made for your favorite corner.',
        'photo-1567538096630-e0c55bd6374c',
        'Natural',
        'Walnut',
    ],

    [
        'Everyday ceramic mug',
        3,
        850,
        0,
        'Hand-finished stoneware',
        'Stoneware',
        'Your first cup, made a little better. A generous handle, softly rounded shape, and a tactile glaze make this an everyday companion.',
        'photo-1514228742587-6b1558fcca3d',
        'Chalk',
        'Clay',
    ],

    [
        'Linen cushion cover',
        4,
        1450,
        1800,
        '100% washed linen',
        'Linen',
        'A small change with a lovely effect. Relaxed washed linen with a subtle weave and concealed zip. Cushion insert sold separately.',
        'photo-1584100936595-c0654b55a2e2',
        'Oat',
        'Rust',
    ],

    [
        'Sol table lamp',
        2,
        4650,
        0,
        'Warm light, simple lines',
        'Ceramic & cotton',
        'Good light changes everything. A sculpted base and linen shade bring a warm, unhurried glow to a bedside or reading nook.',
        'photo-1507473885765-e6ed057f782c',
        'Sand',
        'Charcoal',
    ],

    [
        'Pebble bud vase',
        1,
        1250,
        0,
        'A little object of interest',
        'Glazed ceramic',
        'A quiet sculptural piece for a single stem, a collected branch, or simply a shelf. Each hand-finished glaze has a character of its own.',
        'photo-1578500494198-246f612d3b3d',
        'Chalk',
        'Terracotta',
    ],

    [
        'Gather oak side table',
        1,
        6800,
        0,
        'A place for the everyday',
        'Solid oak',
        'Coffee, a book, the things you keep close. A compact oak side table with softly rounded edges and an honest, visible grain.',
        'photo-1499933374294-4584851497cc',
        'Natural oak',
        'Smoked oak',
    ],

    [
        'Sunday woven throw',
        4,
        2850,
        3400,
        'For slow mornings',
        'Cotton blend',
        'A soft, breathable throw with a tactile woven finish. Drape it over the sofa or pull it close on a slow Sunday morning.',
        'photo-1600369671236-e74521d4b6ad',
        'Oat',
        'Olive',
    ],

    [
        'Ritual serving bowl',
        3,
        1950,
        0,
        'Made for the middle of the table',
        'Stoneware',
        'A generous, gently irregular serving bowl. For shared salads, seasonal fruit, and everything that brings people around the table.',
        'photo-1490312278390-ab64016e0aa9',
        'Cream',
        'Slate',
    ],
];
foreach ($data as $i => $p) {
    q(
        'INSERT INTO products(category_id,name,slug,description,material,care,featured) VALUES(?,?,?,?,?,?,?)',
        [
            $p[1],
            $p[0],
            slugify($p[0]),
            $p[6],
            $p[5],
            'Wipe gently with a soft cloth. Keep away from prolonged direct sunlight.',
            ($i < 4 ? 1 : 0),
        ]
    );
    $id = (int) db()->lastInsertId();
    foreach ([$p[8], $p[9]] as $j => $color) {
        q(
            'INSERT INTO variants(product_id,sku,color,size,price,compare_price,stock) VALUES(?,?,?,?,?,?,?)',
            [
                $id,
                'FF-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT) . '-' . ($j + 1),
                $color,
                'Standard',
                $p[2],
                $p[3],
                12 + $i * 2,
            ]
        );
    }
    $image = '/assets/product-' . ($i + 1) . '.jpg';
    q('INSERT INTO product_images(product_id,image,position) VALUES(?,?,0)', [$id, $image]);
}

q(
    'INSERT INTO sliders(title,description,image,link,button,position) VALUES(?,?,?,?,?,?)',
    [
        'Good things.\nEveryday living.',
        'Thoughtfully chosen furniture, objects, and little comforts. For a home that feels like you.',
        '/assets/hero.jpg',
        '/shop',
        'Find your everyday',
        0,
    ]
);
q(
    'INSERT INTO sliders(title,description,image,link,button,position) VALUES(?,?,?,?,?,?)',
    [
        'A softer\npoint of view.',
        'Layer in natural textures, warm tones, and a little space to slow down.',
        '/assets/hero-2.jpg',
        '/shop?category=textiles',
        'Explore the soft side',
        1,
    ]
);
q('INSERT INTO coupons(code,type,amount,minimum,active) VALUES(?,?,?,?,?)', ['HELLO10', 'percent', 10, 1500, 1]);
$extraCategories = [
    'Electronics' => 'Considered tech for everyday life.',
    'Everyday carry' => 'Good companions for wherever you go.',
    'Personal care' => 'A little care for your daily routine.',
];
foreach ($extraCategories as $name => $desc) {
    q('INSERT INTO categories(name,slug,description) VALUES(?,?,?)', [$name, slugify($name), $desc]);
}

$extras = [
    [
        'Tempo wireless headphones',
        'Electronics',
        9,
        6800,
        7900,
        'Wireless · over-ear',
        'Cream',
        'Black',
        'Comfortable over-ear headphones for your everyday soundtrack. A considered shape, soft ear cushions, and simple wireless pairing.',
        'Charge with a compatible USB-C adapter. Keep dry. Demo product; replace technical specifications before selling.',
    ],

    [
        'Pocket portable speaker',
        'Electronics',
        10,
        3450,
        0,
        'Bluetooth · portable',
        'Oat',
        'Charcoal',
        'Take a little sound with you. A compact wireless speaker with a tactile fabric finish and an easy carry strap.',
        'Keep dry and clean with a soft cloth. Demo product; confirm device specifications before selling.',
    ],

    [
        'Type wireless keyboard',
        'Electronics',
        11,
        5250,
        6200,
        'Compact · wireless',
        'Cream',
        'Slate',
        'A quieter-looking workspace starts with the small things. A compact keyboard with tactile keys and a thoughtfully simple layout.',
        'Wipe with a dry microfiber cloth. Demo product; confirm keyboard layout and connectivity before selling.',
    ],

    [
        'Daypack canvas backpack',
        'Everyday carry',
        12,
        2950,
        0,
        'Cotton canvas',
        'Olive',
        'Black',
        'A dependable companion for commutes, coffee stops, and wherever the day goes. Clean lines, generous room, and comfortable straps.',
        'Spot clean with a damp cloth. Air dry away from direct sunlight.',
    ],

    [
        'Daylight insulated bottle',
        'Everyday carry',
        13,
        1650,
        1950,
        'Stainless steel · 500 ml',
        'Sand',
        'Black',
        'A simple stainless steel bottle for the daily rhythm. Easy to carry and comfortable to hold, from your desk to your weekend walk.',
        'Hand wash with mild soap. Not suitable for microwave use.',
    ],

    [
        'Everyday hand wash',
        'Personal care',
        14,
        750,
        0,
        'Amber glass · 300 ml',
        'Original',
        'Unscented',
        'A simple hand-washing ritual in a reusable amber glass bottle. A considered addition to your bathroom or kitchen counter.',
        'For external use only. Demo product; add verified ingredients and directions before selling.',
    ],

    [
        'Market canvas tote',
        'Everyday carry',
        15,
        950,
        0,
        'Unbleached cotton canvas',
        'Natural',
        'Black',
        'For groceries, notebooks, and those just-in-case moments. A roomy everyday tote in natural cotton canvas with comfortable wide straps.',
        'Cold hand wash. Air dry. Natural fibers may shrink slightly.',
    ],
];
foreach ($extras as $i => $p) {
    $cid = (int) q('SELECT id FROM categories WHERE name=?', [$p[1]])->fetchColumn();
    q(
        'INSERT INTO products(category_id,name,slug,description,material,care,featured) VALUES(?,?,?,?,?,?,?)',
        [$cid, $p[0], slugify($p[0]), $p[8], $p[5], $p[9], $i < 4 ? 1 : 0]
    );
    $id = (int) db()->lastInsertId();
    foreach ([$p[6], $p[7]] as $j => $color) {
        q(
            'INSERT INTO variants(product_id,sku,color,size,price,compare_price,stock) VALUES(?,?,?,?,?,?,?)',
            [
                $id,
                'FF-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT) . '-' . ($j + 1),
                $color,
                'Standard',
                $p[3],
                $p[4],
                18 + $i,
            ]
        );
    }
    q(
        'INSERT INTO product_images(product_id,image,position) VALUES(?,?,0)',
        [$id, '/assets/product-' . $p[2] . '.jpg']
    );
}

$chairId = (int) q('SELECT id FROM products WHERE slug=?', ['arc-lounge-chair'])->fetchColumn();
q('INSERT INTO product_images(product_id,image,position) VALUES(?,?,1)', [$chairId, '/assets/gallery-1.jpg']);
q(
    'UPDATE sliders SET title=?,description=?,link=?,button=? WHERE position=1',
    [
        'A little more\npossibility.',
        'Considered tech and everyday essentials. Good company for the way you live.',
        '/shop?category=electronics',
        'Explore electronics',
    ]
);
