<?php declare(strict_types=1);

namespace Simul\Tyop\Bridge;

use Simul\Tyop;

class Html
{
	/** @var Tyop\Corrector */
	protected $corrector;

	/**
	 * Tags, which lead to newlines.
	 * Only text inside of the block is checked.
	 *
	 * @var string[]
	 */
	protected $blockTags;

	/** @var string[] */
	protected $tokens = [];

	public function __construct()
	{
		$this->corrector = new Tyop\Corrector;
		$this->blockTags = [
			'p' => 'p',
			'/p' => '/p',
			'br' => 'br',
			'ul' => 'ul',
			'/ul' => '/ul',
			'ol' => 'ol',
			'/ol' => '/ol',
			'li' => 'li',
			'/li' => '/li',
		];
	}

	/**
	 * @param  string $regex
	 * @param  string $replacement
	 * @return self
	 * @throws Tyop\IllFormedRegex
	 */
	public function addRule(string $regex, string $replacement): self
	{
		$this->corrector->addRule($regex, $replacement);
		return $this;
	}

	public function addBlockTag(string $tag): self
	{
		$tag = trim($tag, '<>');
		$this->blockTags[$tag] = $tag;
		return $this;
	}

	public function with(string $text): self
	{
		$this->tokens = self::tokenize($text, $this->blockTags);
		return $this;
	}

	public function normalize(): self
	{
		foreach ($this->tokens as $k => $t) {
			$t = trim($t);
			$t = preg_replace('/\n\t+/', ' ', $t);
			$t = preg_replace('/&nbsp;/', ' ', $t);
			$t = trim($t, ' ');

			$this->tokens[$k] = $t = self::sanitizeAndRun($t, [$this->corrector, 'normalize']);
		}

		$this->tokens = array_filter($this->tokens);
		while (true) {
			if (count($this->tokens) < 3) {
				break;
			}
			$cnt = count($this->tokens) - 1;
			if ($this->tokens[$cnt] === '</p>' &&
				preg_match('/<br ?\/?>/', $this->tokens[$cnt - 1]) &&
				$this->tokens[$cnt - 2] === '<p>'
			) {
				$this->tokens = array_slice($this->tokens, 0, -3);
			} else {
				break;
			}
		}

		return $this;
	}

	public function fix(): self
	{
		$this->map([$this->corrector, 'fix']);
		return $this;
	}

	public function fixDomain(): self
	{
		$this->map([$this->corrector, 'fixDomain']);
		return $this;
	}

	/**
	 * @param  callable $callback
	 * @return void
	 */
	protected function map($callback)
	{
		foreach ($this->tokens as $k => $t) {
			$this->tokens[$k] = self::sanitizeAndRun($t, $callback);
		}
	}

	/**
	 * @param  string   $token
	 * @param  callable $callback
	 * @return string
	 */
	protected static function sanitizeAndRun(string $token, $callback): string
	{
		/**
		 * TODO:
		 * Allow user to define this, too?
		 * Probably the best method: use more generic regex, like /<\/?[a-zA-Z1-6]+\s*\/?>/.
		 * (How often you'll define own tags?)
		 */
		$splitters = [
			'strong' => 'strong',
			'/strong' => '/strong',
			'u' => 'u',
			'/u' => '/u',
			'em' => 'em',
			'/em' => '/em',
			'a' => 'a',
			'/a' => '/a',
		];

		$prefixStart = false;
		$suffixStart = false;

		$tokens = self::tokenize($token, $splitters);
		$cnt = count($tokens);
		for ($i = 0; $i < $cnt; $i++) {
			if (!self::knownSplitter($tokens[$i], $splitters)) {
				$prefixStart = $i;
				break;
			}
		}
		for ($i = $cnt - 1; $i > 0; $i--) {
			if (!self::knownSplitter($tokens[$i], $splitters)) {
				$suffixStart = $i;
				break;
			}
		}

		foreach ($tokens as $k => $t) {
			// Introduce some kind of the "first and last whitespace"
			if (self::knownSplitter($t, $splitters)) {
				continue;
			}

			$prefix = '';
			$suffix = '';

			preg_match('/^\s+/', $t, $m);
			$prefix = $m[0] ?? '';

			preg_match('/\s+$/', $t, $m);
			$suffix = $m[0] ?? '';

			$t = $callback($t);
			if ($k >= $prefixStart) {
				$t = $prefix . $t;
			}
			if ($k < $suffixStart) {
				$t .= $suffix;
			}
			$tokens[$k] = $t;
		}
		return implode('', $tokens);
	}

	protected static function knownSplitter($needle, $haystack): bool
	{
		foreach ($haystack as $h) {
			$h = preg_quote($h, '/');
			if (preg_match("/^<$h/", $needle) > 0) {
				return true;
			}
		}
		return false;
	}

	public function fetch(): string
	{
		foreach ($this->tokens as &$t) {
			$t = str_replace(' ', '&nbsp;', $t);
		}
		$res = implode("\n", $this->tokens);
		$this->tokens = [];
		return $res;
	}

	/**
	 * @param  string   $text
	 * @param  string[] $splitters
	 * @return string[]
	 */
	protected static function tokenize(string $text, array $splitters)
	{
		$start = 0;
		$tokens = [];

		$length = mb_strlen($text);
		while ($start <= $length) {
			$found = false;
			$tokenStart = $length;
			foreach ($splitters as $s) {
				$content = mb_strpos($text, '<' . $s, $start);
				if ($content === false) {
					continue;
				}
				if ($content < $tokenStart) {
					$found = true;
					$tokenStart = $content;
				}
			}
			if ($found === false) {
				break;
			}

			$tokenEnd = mb_strpos($text, '>', $tokenStart);
			if ($tokenEnd === false) {
				break;
			}
			$tokenEnd += mb_strlen('>');

			$t = mb_substr($text, $start, $tokenStart - $start);
			if ($t !== '') {
				$tokens[] = $t;
			}
			$t = mb_substr($text, $tokenStart, $tokenEnd - $tokenStart);
			if ($t !== '') {
				$tokens[] = $t;
			}
			$start = $tokenEnd;
		}

		if ($start < $length) {
			$tokens[] = mb_substr($text, $start);
		}

		return $tokens;
	}
}
