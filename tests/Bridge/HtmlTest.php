<?php declare(strict_types=1);

use PHPUnit\Framework as PHPUnit;
use Simul\Tyop\Bridge;

class HtmlTest extends PHPUnit\TestCase
{
	#[PHPUnit\Attributes\DataProvider('corrector')]
	public function testCorrector(string $text, $expected)
	{
		$bridge = new Html;
		$text = $bridge->with($text)
			->normalize()
			->fix()
			->fetch();

		$this->assertSame($expected, $text);
	}

	public static function corrector()
	{
		return [
			[
				<<<HTML
				<p><strong>Mezera na konci odstavce? Nepřijatelné. </strong></p>
				HTML,
				<<<HTML
				<p>
				<strong>Mezera na konci odstavce? Nepřijatelné.</strong>
				</p>
				HTML,
			],
			[
				<<<HTML
				<p>Nevalidní HTML? <strong><u>Nevadí </p>
				HTML,
				<<<HTML
				<p>
				Nevalidní HTML? <strong><u>Nevadí
				</p>
				HTML,
			],
			[
				<<<HTML
				<p><strong>Mezera na konci odstavce? Nepřijatelné. </strong></p>
				<p><br></p>
				<p>
					Podle §1500900 odst. 13 zákona č. 561/1970 Sb., o &nbsp;
				</p>
				HTML,
				<<<HTML
				<p>
				<strong>Mezera na konci odstavce? Nepřijatelné.</strong>
				</p>
				<p>
				<br>
				</p>
				<p>
				Podle § 1500900 odst. 13 zákona č. 561/1970 Sb., o
				</p>
				HTML,
			],
			[
				<<<HTML
				<p>Ahoj</p>
				<p><br></p>
				<p>Sbohem</p>
				<p><br></p>
				<p><br></p>
				HTML,

				<<<HTML
				<p>
				Ahoj
				</p>
				<p>
				<br>
				</p>
				<p>
				Sbohem
				</p>
				HTML,
			],
			[
				<<<HTML
				<p>Zaplatíte si. <strong><u>Zatím ne všichni, vy náhradníci!</u></strong> Ale do<em> konce roku to bude </em>(3000,- Kč, platební údaje půjdou určitě někde stáhnout). Je <strong><u>nutné napsat jméno přihlášeného!</u></strong></p>
				HTML,
				<<<HTML
				<p>
				Zaplatíte si. <strong><u>Zatím ne všichni, vy náhradníci!</u></strong> Ale do<em> konce roku to bude </em>(3000 Kč, platební údaje půjdou určitě někde stáhnout). Je <strong><u>nutné napsat jméno přihlášeného!</u></strong>
				</p>
				HTML,
			],
			[
				<<<HTML
				<p><em><u>Interval 3.3. - 8.3. 2024</u></em></p><p><br></p>
				HTML,

				<<<HTML
				<p>
				<em><u>Interval 3. 3. – 8. 3. 2024</u></em>
				</p>
				HTML,
			],
			[
				<<<HTML
				<p>Ve čtvrtek 20. 4. je exkurze do pivovaru.</p><ul><li>I. kolo - od 15. 30 hod. </li><li>II. kolo - od 16 hod.</li></ul>
				HTML,
				<<<HTML
				<p>
				Ve čtvrtek 20. 4. je exkurze do pivovaru.
				</p>
				<ul>
				<li>
				I. kolo – od 15.30 hod.
				</li>
				<li>
				II. kolo – od 16 hod.
				</li>
				</ul>
				HTML,
			],
			[
				<<<HTML
				<a data-title="Opravdu chcete smazat záznam z 2.5.?">Smazat</a>
				HTML,
				<<<HTML
				<a data-title="Opravdu chcete smazat záznam z 2.5.?">Smazat</a>
				HTML,
			],
		];
	}

	#[PHPUnit\Attributes\DataProvider('tokens')]
	public function testTokenize(string $text, $expected): void
	{
		$bridge = new Html;
		$this->assertSame($expected, $bridge->testTokenize($text));
	}

	/**
	 * @return array<array{0: string, 1: string[]}>
	 */
	public static function tokens()
	{
		return [
			[
				'<p><strong>Vyhlašuji!!!</strong></p> I když radši nic.',
				[
					'<p>',
					'<strong>Vyhlašuji!!!</strong>',
					'</p>',
					' I když radši nic.',
				],
			],
			[
				'<p>Vy<strong>HLAS</strong>uj</p><p><br></p>',
				[
					'<p>',
					'Vy<strong>HLAS</strong>uj',
					'</p>',
					'<p>',
					'<br>',
					'</p>',
				],
			],
			[
				'<p>List:</p><ul><li>A)</li><li>B)</li></ul>',
				[
					'<p>',
					'List:',
					'</p>',
					'<ul>',
					'<li>',
					'A)',
					'</li>',
					'<li>',
					'B)',
					'</li>',
					'</ul>',
				],
			],
			[
				'<p class="color: blue;">List:</p><ul><li>A)</li><li>B)</li></ul>',
				[
					'<p class="color: blue;">',
					'List:',
					'</p>',
					'<ul>',
					'<li>',
					'A)',
					'</li>',
					'<li>',
					'B)',
					'</li>',
					'</ul>',
				],
			],
		];
	}
}

class Html extends Bridge\Html
{
	public function testTokenize(string $text)
	{
		return $this->tokenize($text, $this->blockTags);
	}
}
