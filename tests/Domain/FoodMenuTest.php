<?php declare(strict_types=1);

use PHPUnit\Framework as PHPUnit;
use Simul\Tyop;

/**
 * Test contains some gems associated with food,
 * which are further enhanced to cover more issues.
 * Some of these errors are not as wild and might end
 * up in the set of normalize-fix.
 */
class FoodMenuTest extends PHPUnit\TestCase
{
	#[PHPUnit\Attributes\DataProvider('units')]
	public function testUnits(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;
		$corrector->addRule('/(\d+)(ks[\s\.\,\?\!])/', '$1 $2');
		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);
		$this->assertSame($expected, $text);
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function units()
	{
		return [
			// Fine
			['Párky 2 ks', 'Párky 2 ks'],
			['3 kssss', '3 kssss'],
			['3kssss', '3kssss'],

			['Máme 2ks.', 'Máme 2 ks.'],
			['Máme 2ks!', 'Máme 2 ks!'],
			['Máme 2ks?', 'Máme 2 ks?'],

			['Stripsy 3ks', 'Stripsy 3 ks'],
			['Mini sýry 6ks, hranolky', 'Mini sýry 6 ks, hranolky'],
			[
				'Palačinky 2ks malin. džem, čokol. , šlehačka',
				'Palačinky 2 ks malin. džem, čokol., šlehačka',
			],
		];
	}

	#[PHPUnit\Attributes\DataProvider('volume')]
	public function testVolume(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;
		$corrector->addRule('/(\d+\s*)lt([\s\.\,\?\!])/', '$1l$2');
		$corrector->addRule('/(\d+,)\s+(\d+l\s*[\s\.\,\?\!])/', '$1$2');

		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);
		$this->assertSame($expected, $text);
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function volume()
	{
		return [
			['Voda 0,3 lt', 'Voda 0,3 l'],
			['1 lt', '1 l'],
			['1lt', '1l'],

			['Voda 0,3 lt', 'Voda 0,3 l'],
			['Voda 0,3lt', 'Voda 0,3l'],
			['Voda 0,3 lt.', 'Voda 0,3 l.'],
			['Voda 0,3 lt!', 'Voda 0,3 l!'],
			['Voda 0,3 lt?', 'Voda 0,3 l?'],

			// Do not touch extra weird cases.
			['Voda 0,3ltr', 'Voda 0,3ltr'],

			// More of the domain-specific rules.
			['Natura voda neperlivá, jemně perlivá 0, 3l', 'Natura voda neperlivá, jemně perlivá 0,3l'],
			['Coca Cola Zero, Fanta 0, 33l', 'Coca Cola Zero, Fanta 0,33l'],
			['0, 33l', '0,33l'],
			['0, 33l.', '0,33l.'],
			['0, 33l!', '0,33l!'],
			['!0, 33l!', '!0,33l!'],

			// What about combination?
			['0, 33lt', '0,33l'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('slashes')]
	public function testSlashes(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;
		$corrector->addRule('/\s+\/$/', '/');

		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);
		$this->assertSame($expected, $text);
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function slashes()
	{
		return [
			// These two test are causing issues right now. In the first case it
			// should probably mean something like "brackets", in the second one
			// "selection".
			/*
			[
				// Wildly assume that last slash should be glued to the last word.
				'Langoš /kečup, česnek, sýr /',
				'Langoš /kečup, česnek, sýr/',
			],
			[
				'Kuřecí stehno pečené na slanince a česneku, rýže/ šťouchaný brambor',
				'Kuřecí stehno pečené na slanince a česneku, rýže / šťouchaný brambor',
			],
			*/

			// Do not touch these.
			['Bramborový salát, kuřecí/vepřový řízek', 'Bramborový salát, kuřecí/vepřový řízek'],
			['Kuřecí čína, rýže / hranolky', 'Kuřecí čína, rýže / hranolky'],
		];
	}
}
