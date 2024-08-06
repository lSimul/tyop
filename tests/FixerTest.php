<?php declare(strict_types=1);

use PHPUnit\Framework as PHPUnit;
use LSimul\Tyop;

class FixerTest extends PHPUnit\TestCase
{
	#[PHPUnit\Attributes\DataProvider('dates')]
	public function testDateFixes(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;

		$this->assertSame($expected, $corrector->fix($text));
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function dates()
	{
		return [
			['10.9.', '10. 9.'],
			['3.12.', '3. 12.'],
			['8.ledna', '8. ledna'],

			// No fixes needed.
			['10.09.', '10.09.'],
			['10. 09.', '10. 09.'],
			['15. října', '15. října'],

			// Bloated
			['K chybě došlo 10.9.', 'K chybě došlo 10. 9.'],
			['Marketingově je 3.12. vlastně po Vánocích.', 'Marketingově je 3. 12. vlastně po Vánocích.'],
			['8.ledna je loni.', '8. ledna je loni.'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('numbers')]
	public function unaffectedNumbers(string $invariant): void
	{
		// Goal is to not break numbers; crucial when dates are formatted, too.
		// Tied with testCurrencyFixes, it is basically dependent on this.
		$corrector = new Tyop\Corrector;

		$this->assertSame($invariant, $corrector->fix($invariant));
	}

	/**
	 * @return array<string[]>
	 */
	public static function numbers()
	{
		return [
			['100,00'],
			['999.000'],
			['999 000'],
			['123.456.78,00'],
			['1.234'],
			['5 678'],

			// Bloated
			['Hodnota vzrostla z 100,23 na 450,98.'],
			['Místo 100 to bylo 1.000, chyběla jim tam nula.'],
			['200 – takový byl začátek.'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('currencies')]
	public function testCurrencyFixes(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;

		$this->assertSame($expected, $corrector->fix($text));
	}

	/**
	 * Just Czech number and currency formating is interesting.
	 * @return array<array{0: string, 1: string}>
	 */
	public static function currencies()
	{
		return [
			// Do not be nice and remove ',-' instead
			// of fixing it to ',—'.
			['100,-', '100 Kč'],
			['100,- Kč', '100 Kč'],
			['1.234,- Kč', '1.234 Kč'],
			['1 234,- Kč', '1 234 Kč'],

			// No fixes needed.
			['100 Kč', '100 Kč'],
			['250,52 Kč', '250,52 Kč'],
			['1.000.000 Kč', '1.000.000 Kč'],
			['1.234.567,89 Kč', '1.234.567,89 Kč'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('dateRanges')]
	public function testDateRangesFixes(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;

		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);

		$this->assertSame($expected, $text);
	}

	/**
	 * Just Czech number and currency formating is interesting.
	 * @return array<array{0: string, 1: string}>
	 */
	public static function dateRanges()
	{
		return [
			['3.3. - 8.3. 2024', '3. 3. – 8. 3. 2024'],
			['3.11. 2024 - 8. 11. 2024', '3. 11. 2024 – 8. 11. 2024'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('times')]
	public function testTimes(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;
		$text = $corrector->fix($text);

		$this->assertSame($expected, $text);
	}

	/**
	 * Just Czech number and currency formating is interesting.
	 * @return array<array{0: string, 1: string}>
	 */
	public static function times()
	{
		return [
			['15.30hod.', '15.30hod.'],
			['15.30 hod.', '15.30 hod.'],

			['15  .  30 hod.', '15.30 hod.'],
			['15 .30 hod.', '15.30 hod.'],
			['15 .30hod.', '15.30hod.'],

			['15  .  30 h.', '15.30 h.'],
			['15 .30 h.', '15.30 h.'],
			['15 .30h.', '15.30h.'],

			// Some edge cases are not checked. But prefix should be good enough.
			['15 :3 hod.', '15:3 hod.'],
			['15.3 h.', '15.3 h.'],

			// Unknown shortcut
			['15. 30 ho.', '15. 30 ho.'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('links')]
	public function testIgnoreLinks(string $text): void
	{
		$corrector = new Tyop\Corrector;

		$expected = $text;
		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);

		$this->assertSame($expected, $text);
	}

	/**
	 * @return array<string[]>
	 */
	public static function links()
	{
		return [
			['https://www.example.com/path?key=value'],
			['URL ve větě, umístěna na konci https://www.example.com/'],
			['Kdo v dnešní době začíná example.com s www aby to bylo www.example.com'],
			// Rules have to be loosen up a little bit.
			['tn.cz'],
			['What about multiple levels? https://www.example.co.uk'],
			['sk.example.cz – jiný prefix než www'],
		];
	}

	#[PHPUnit\Attributes\DataProvider('nbsps')]
	public function testNonbreakableSpaces(string $text): void
	{
		$corrector = new Tyop\Corrector;

		$expected = $text;
		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);

		$this->assertSame($expected, $text);
	}

	/**
	 * @return array<string[]>
	 */
	public static function nbsps()
	{
		return [
			['Skóre 0:2'],
		];
	}
}
