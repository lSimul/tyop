<?php declare(strict_types=1);

namespace Simul\Tyop;

class Corrector
{
	public static $betterW = '[0-9a-zA-ZěščřžýáíéĚŠČŘŽÝÁÍ]';
	public static $noDW = '[a-zA-ZěščřžýáíéĚŠČŘŽÝÁÍ]';

	/** @var array{regex: string[], replacement: string[]} */
	protected $domainSpecific = [];

	public function __construct()
	{
		$this->domainSpecific = [
			'regex' => [],
			'replacement' => [],
		];
	}

	/**
	 * @param  string $regex
	 * @param  string $replacement
	 * @return self
	 * @throws IllFormedRegex
	 */
	public function addRule(string $regex, string $replacement): self
	{
		if (preg_match($regex, '') === false) {
			throw new IllFormedRegex;
		}

		$this->domainSpecific['regex'][] = $regex;
		$this->domainSpecific['replacement'][] = $replacement;

		return $this;
	}

	public function normalize(string $text): string
	{
		$w = self::$betterW;
		$dw = self::$noDW;

		$text = self::prepareText($text);
		$text = preg_replace([
			'/\s+/',

			'/\s+([\.\!,\?])/',

			// TODO:
			// Should these things really be here?
			// This might be considered already a real fix,
			// despite the fact it is just "brackets".

			// Spaces inside of the brackets:
			'/\(\s+/',
			'/\s+\)/',
			// Spaces before/after brackets:
			"/($w)\(/",
			"/\)($w)/",

			// Spaces around commas:
			'/\s+,\s+/',
			'/\s+,([^\s\d\-])/',
			'/,([^\s\d\-])/',

			// §24
			'/§(\d)/',
		], [
			' ',

			'$1',

			'(',
			')',
			'$1 (',
			') $1',

			// Spaces around commas:
			', ',
			', $1',
			', $1',

			'§ $1',
		], $text, -1, $count);

		// Dashes
		$text = preg_replace([
			// Speculatives typos:
			"/($w)- /",
			"/ -($w)/",

			// Clear failure:
			'/ - /',
		], [
			'$1 – ',
			' – $1',

			' – ',
		], $text);

		// Dots:
		$text = preg_replace('/\.\.\.+/', '…', $text);
		// Speculative fix, elipsis without spaces around? Add space behind.
		$text = preg_replace("/($w)…($w)/", '$1… $2', $text);

		// Some emojis:
		$text = preg_replace("/($w)(:-)/", '$1 $2', $text);

		// Dot after a word, avoid URLs.
		do {
			$change = false;
			$cnt = preg_match_all('/([^ ]+)\.([^ ]+)/', $text, $matches);
			if ($cnt === 0) {
				if (preg_match("/($dw)(\d)/", $text, $m) !== 1 || ord($m[1]) === 160) {
					// Non-breakable space.
					break;
				}
				$text = preg_replace("/($dw)(\d)/", '$1 $2', $text);
				break;
			}
			foreach ($matches[0] as $id => $tip) {
				if (!self::looksLikeURL($tip)) {
					// Dot after a word.
					if (preg_match("/($dw)\.($dw)/", $tip) > 0) {
						$l = $matches[1][$id];
						$r = $matches[2][$id];
						$text = preg_replace("/($l)\.($r)/", '$1. $2', $text, count: $count);
						if ($count > 0) {
							$change = true;
						}
					}
					if (preg_match("/($dw)(\d)/", $tip, $m) > 0) {
						$l = $m[1];
						if (ord($l) === 160) {
							// Non-breakable space.
							continue;
						}
						$r = $m[2];
						$text = preg_replace("/$l$r/", '$1 $2', $text, count: $count);
						if ($count > 0) {
							$change = true;
						}
					}
				}
			}
		} while ($change);

		// And fix titles afterwards.
		$text = self::fixTitles($text);

		return trim($text);
	}

	public function fix(string $text): string
	{
		// Do one check at a time.
		// Remember order of fixes, or atlast a type of fixes.
		// If possible, make these fixes stable.
		//
		// Known issues: spaces are filled everywhere
		// Checks are overlapping.
		$w = self::$betterW;
		$dw = self::$noDW;
		$text = self::prepareText($text);
		// Dates:
		$text = preg_replace([
			'/([^\d]\d\.)(\d{2}\.)/', // 1.10.
			'/(\d+\.)(\d\.)/', // 10.9.
			// "/($w+\.)($dw+)/", // 10.ledna
		], '$1 $2', $text);

		// Dot after a word, avoid URLs.
		do {
			$change = false;

			$cnt = preg_match_all('/[^ ]+\.[^ ]+/', $text, $matches);
			if ($cnt === 0) {
				$text = preg_replace("/($w+\.)($dw+)/", '$1 $2', $text);
				break;
			}
			foreach ($matches[0] as $tip) {
				if (!self::looksLikeURL($tip)) {
					if (preg_match("/($w+\.)($dw+)/", $tip, $m) > 0) {
						$l = $m[1];
						$r = $m[2];
						$text = preg_replace("/($l)($r)/", '$1 $2', $text, count: $count);
						if ($count > 0) {
							$change = true;
						}
					}
				}
			}
		} while ($change);

		// Fix titles once again.
		$text = self::fixTitles($text);

		// Time.
		// Strictly tight to 15. 30 hod.
		// Order here matters, first dates, then this
		// ridiculous case.
		$text = preg_replace([
			'/(\d+)\s*([\.:])\s*(\d+)(\s*hod\.)/',
			'/(\d+)\s*([\.:])\s*(\d+)(\s*h\.)/',
		], '$1$2$3$4', $text);

		// č.1
		$text = preg_replace('/č\.(\d+)/', 'č. $1', $text);

		// Currency:
		$text = preg_replace([
			'/(\d+),-\sKč/', // 100,- Kč
			'/(\d+),-/', // 100,-
		], '$1 Kč', $text);

		// Terrible job with numbers (5tém etc.).
		$text = preg_replace([
			'/(\d+)tím?/',
			'/(\d+)tém?/',
		], '$1.', $text);

		// Czech quotes.
		$cnt = substr_count($text, '"');
		if ($cnt === 2) {
			$text = preg_replace('/"(.*)"/', '„$1“', $text);
		}

		return trim($text);
	}

	protected static function looksLikeURL(string $text): bool
	{
		// ^ is intentionally omitted, fix only well known issues.
		// Typos like
		// End of sentence.www.google.com will be ignored.
		// (Prepare a ground for a human element.)
		if (preg_match('/https?:\/\//', $text) > 0) {
			return true;
		}
		if (preg_match('/www/', $text) > 0) {
			return true;
		}

		foreach ([
			'de', 'pl', 'com', 'cz', 'sk', 'pl', 'tv', 'to', 'hu',
		] as $domain) {
			if (preg_match("/\.$domain/", $text) > 0) {
				return true;
			}
		}

		return false;
	}

	public function fixDomain(string $text): string
	{
		$text = self::prepareText($text);
		$text = preg_replace($this->domainSpecific['regex'], $this->domainSpecific['replacement'], $text);
		return trim($text);
	}

	private static function prepareText(string $text): string
	{
		return " $text ";
	}

	protected static function fixTitles(string $text): string
	{
		$text = preg_replace([
			'/Ph\. D\./',
			'/Th\. D\./',
			// TODO: More titles.
		], [
			'Ph.D.',
			'Th.D.',
		], $text);
		return $text;
	}
}

class IllFormedRegex extends \Exception
{
}
