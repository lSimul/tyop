<?php declare(strict_types=1);

use PHPUnit\Framework as PHPUnit;
use LSimul\Tyop;

class NormalizerTest extends PHPUnit\TestCase
{
	#[PHPUnit\Attributes\DataProvider('spaces')]
	public function testSpaces(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;

		$this->assertSame($expected, $corrector->normalize($text));
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function spaces()
	{
		return [
			['A  B', 'A B'],
			['Větší  množství    mezer   je        ZAKÁZÁNO   !', 'Větší množství mezer je ZAKÁZÁNO!'],
			// Brackets
			['Seznam ( raz, dva)', 'Seznam (raz, dva)'],
			['List( raz, dva )', 'List (raz, dva)'],

			['List (jedna), list (dva)', 'List (jedna), list (dva)'],
			['List (jedna) , seznam (konec) .', 'List (jedna), seznam (konec).'],
			['List (jedna)a lístek', 'List (jedna) a lístek'],
			// This is more about other marks.
			['Konec (opravdu) !', 'Konec (opravdu)!'],

			['brambory150 g', 'brambory 150 g'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('commas')]
	public function testCommas(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;

		$this->assertSame($expected, $corrector->normalize($text));
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function commas()
	{
		return [
			[',', ','],

			['Toto , toto se nepovedlo.', 'Toto, toto se nepovedlo.'],
			['Ale,ovšem ,tato věc , ta je snad ještě horší.', 'Ale, ovšem, tato věc, ta je snad ještě horší.'],

			['Nesahat na atp., apod.', 'Nesahat na atp., apod.'],
		];
	}

	#[PHPUnit\Attributes\DataProviderExternal(FixerTest::class, 'numbers')]
	#[PHPUnit\Attributes\DataProvider('currencies')]
	public function testNumbers(string $invariant): void
	{
		// Check that normalization does not ruin commas and dots around numbers
		// and ill-formed currencies.
		$corrector = new Tyop\Corrector;

		$this->assertSame($invariant, $corrector->normalize($invariant));
	}

	/**
	 * Just Czech number and currency formating is interesting.
	 * @return array<string[]>
	 */
	public static function currencies()
	{
		return [
			['100,-'],
			['100,- Kč'],
			['1.234,- Kč'],
			['1 234,- Kč'],
			['100 Kč'],
			['250,52 Kč'],
			['1.000.000 Kč'],
			['1.234.567,89 Kč'],
		];
	}
}
