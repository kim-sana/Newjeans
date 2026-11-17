<?php

declare(strict_types=1);

use App\Support\Cart;

require dirname(__DIR__) . '/vendor/autoload.php';

session_start_safe();

$checks = 0;
$failures = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $checks, $failures;

    $checks++;

    if ($condition) {
        echo 'PASS  ' . $label . ($detail === '' ? '' : ' (' . $detail . ')') . PHP_EOL;

        return;
    }

    $failures++;
    echo 'FAIL  ' . $label . ($detail === '' ? '' : ' (' . $detail . ')') . PHP_EOL;
}

/**
 * @param array<int, int> $expected
 * @param array<int, int> $actual
 */
function checkItems(string $label, array $expected, array $actual): void
{
    check($label, $expected === $actual, json_encode($actual));
}

Cart::clear();

check('fresh cart is empty', Cart::items() === []);
check('fresh cart count is zero', Cart::count() === 0);
check('fresh cart reports empty', Cart::isEmpty());

Cart::add(1);
checkItems('add creates quantity 1', [1 => 1], Cart::items());

Cart::add(1);
checkItems('add increments an existing item', [1 => 2], Cart::items());

Cart::add(2, 5);
checkItems('add inserts a second item', [1 => 2, 2 => 5], Cart::items());
check('count sums quantities', Cart::count() === 7);
check('non-empty cart reports not empty', !Cart::isEmpty());

Cart::add(0);
Cart::add(-3);
checkItems('add ignores invalid album ids', [1 => 2, 2 => 5], Cart::items());

Cart::add(3, 0);
Cart::add(3, -1);
checkItems('add ignores non-positive quantities', [1 => 2, 2 => 5], Cart::items());

Cart::add(3, 150);
checkItems('add caps quantity at 99', [1 => 2, 2 => 5, 3 => 99], Cart::items());

Cart::add(3, 5);
checkItems('add does not exceed the cap', [1 => 2, 2 => 5, 3 => 99], Cart::items());

Cart::add(4, 98);
Cart::add(4, 5);
checkItems('add clamps an increment at the cap', [1 => 2, 2 => 5, 3 => 99, 4 => 99], Cart::items());

Cart::setQuantity(1, 7);
checkItems('setQuantity replaces the quantity', [1 => 7, 2 => 5, 3 => 99, 4 => 99], Cart::items());

Cart::setQuantity(1, 0);
checkItems('setQuantity zero removes the item', [2 => 5, 3 => 99, 4 => 99], Cart::items());

Cart::setQuantity(2, -4);
checkItems('setQuantity negative removes the item', [3 => 99, 4 => 99], Cart::items());

Cart::setQuantity(5, 500);
checkItems('setQuantity caps at 99', [3 => 99, 4 => 99, 5 => 99], Cart::items());

Cart::setQuantity(0, 5);
Cart::setQuantity(-1, 5);
checkItems('setQuantity ignores invalid album ids', [3 => 99, 4 => 99, 5 => 99], Cart::items());

Cart::setQuantity(5, 12);
checkItems('setQuantity lowers the quantity', [3 => 99, 4 => 99, 5 => 12], Cart::items());

Cart::remove(5);
checkItems('remove deletes an item', [3 => 99, 4 => 99], Cart::items());

Cart::remove(999);
Cart::remove(0);
checkItems('remove ignores missing or invalid ids', [3 => 99, 4 => 99], Cart::items());

Cart::clear();
check('clear empties the cart', Cart::items() === []);
check('clear removes the session key', !array_key_exists('cart', $_SESSION));
check('empty cart after clear', Cart::isEmpty());

$_SESSION['cart'] = [
    10 => '3',
    11 => 4,
    12 => 0,
    13 => -1,
    14 => 'abc',
    15 => 120,
    0 => 9,
    -2 => 5,
    'bad' => 2,
];

checkItems(
    'items normalizes and filters raw session values',
    [10 => 3, 11 => 4, 15 => 99],
    Cart::items()
);
check('count ignores invalid raw session values', Cart::count() === 106, (string) Cart::count());
check('isEmpty is false with valid raw values', !Cart::isEmpty());

Cart::add(11, 2);
checkItems(
    'mutation rewrites the session with normalized values',
    [10 => 3, 11 => 6, 15 => 99],
    Cart::items()
);

$_SESSION['cart'] = 'not-an-array';
check('a corrupted cart value is treated as empty', Cart::items() === []);
check('a corrupted cart does not report a count', Cart::count() === 0);

Cart::clear();
check('clear works after corruption', Cart::items() === []);

$_SESSION = [];
session_destroy();

echo PHP_EOL;
echo $failures === 0
    ? 'OK (' . $checks . ' checks)' . PHP_EOL
    : 'FAILED (' . $failures . ' of ' . $checks . ' checks)' . PHP_EOL;

exit($failures === 0 ? 0 : 1);
