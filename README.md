# Tyop

Set of regular expressions trying to fix common mistakes in Czech like
- Multiple spaces,
- spaces on incorrect places (befora comma/fullstop, lack of space after comma/fullstop etc.),
- proper date format,
- currency formating,
- conversion from '-' to '–' where necessary,
- and some more small, but neat fixes.

## Installation

```bash
composer require lsimul/tyop
```

## Usage

```php
use LSimul\Tyop;

$corrector = new Tyop\Corrector;

$text = 'Ale,ovšem ,tato věc , ta je od 2.12. za 300 ,- Kč .  '

$text = $corrector->normalize($text);
// Ale, ovšem, tato věc, ta je od 2.12. za 300,- Kč.

$text = $corrector->fix($text);
// Ale, ovšem, tato věc, ta je od 2. 12. za 300 Kč.
```

`Corrector` has two methods and the idea was to use them in the order _normalize -> fix (-> fixDomain)_
- `normalize(string $text): string` resolves some simple issues with spaces, like lack of them before bracket and so on.
- `fix(string $text): string` digs a little bit deeper and it is willing to format dates, currencies by removing/adding different chars than just a whitespace.

### Extending

`Corrector` has two methods which allow one to extend its behaviour:
- `addRule(string $regex, string $replacement): self`
  - Adds new rule, basically it just validates _regex_ and stores it.
- `fixDomain(string $text): string`
  - Uses all of the new rules on the text.

`fixDomain` is here to fix some issues which are specific for given text and it cannot be put into the main set of rules (for example aggressively turning _lt_ into _l_ because here it always means _liter_).

## Fixing HTML

Next to `Corrector` there is also a `Bridge\Html`, which wraps `Corrector` and can work on `HTML`; trimming whitespaces on the end of the paragraph, removing newlines.

```html
<p>
	<em><u>Interval 3.3. - 8.3. 2024   </u></em>
</p>
<p><br></p>

<!-- Will be turned into: -->
<p>
	<em><u>Interval 3. 3. – 8. 3. 2024</u></em>
</p>
```
