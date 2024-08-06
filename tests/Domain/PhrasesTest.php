<?php declare(strict_types=1);

use PHPUnit\Framework as PHPUnit;
use LSimul\Tyop;

/**
 * Tackles some issues which were not documented in the Fixer test,
 * like conversion to Czech quotes, handling dots in names, usage
 * of dashes.
 */
class PhrasesTest extends PHPUnit\TestCase
{
	#[PHPUnit\Attributes\DataProvider('phrases')]
	public function testPhrases(string $text, string $expected): void
	{
		$corrector = new Tyop\Corrector;
		$corrector->addRule('/(\(Ne\))\s/', '$1');

		$text = $corrector->normalize($text);
		$text = $corrector->fix($text);
		$text = $corrector->fixDomain($text);
		$this->assertSame($expected, $text);
	}

	/**
	 * @return array<array{0: string, 1: string}>
	 */
	public static function phrases()
	{
		return [
			[
				'Příběh „Jak jsem napsal správně uvozovky“',
				'Příběh „Jak jsem napsal správně uvozovky“',
			],
			[
				'Příběh "Jak mi opravili uvozovky"',
				'Příběh „Jak mi opravili uvozovky“',
			],
			['"', '"'],
			['""', '„“',],
			// Really bad edgecase, it is probably better to not touch it.
			// Other approach would be to turn it into '„"“'.
			['"""', '"""'],

			// TODO: Current code limitation, it fixes only when there are
			// exactly two quotes.
			//['"A" i "B"', '„A“ i „B“'],

			[
				'Jak se mně - 5.2. - ze spojovníků stala pomlčka.',
				'Jak se mně – 5. 2. – ze spojovníků stala pomlčka.',
			],

			// The main goal of this test; react just to '(Ne)word' thanks
			// to new rule in fixDomain. Space is removed once again.
			[
				'List (jedna)a lístek',
				'List (jedna) a lístek',
			],
			[
				'(Ne)obyčejné věci',
				'(Ne)obyčejné věci',
			],
			[
				'(Ano)obyčejné věci',
				'(Ano) obyčejné věci',
			],
			[
				'(We)obyčejné věci',
				'(We) obyčejné věci',
			],
			[
				'(ee)obyčejné věci',
				'(ee) obyčejné věci',
			],

			// Simple, but very common.
			[
				'1.A s 1.B se poprvé objevily ve škole.',
				'1. A s 1. B se poprvé objevily ve škole.',
			],

			[
				'Doběhl na 3tím místě.',
				'Doběhl na 3. místě.',
			],

			[
				'J.d.Cimrman',
				'J. d. Cimrman',
			],
			[
				'Ph.D.',
				'Ph.D.',
			],
			[
				'Ph. D.',
				'Ph.D.',
			],
		];
	}
}
