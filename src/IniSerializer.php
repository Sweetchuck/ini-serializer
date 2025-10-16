<?php

declare(strict_types = 1);

namespace Sweetchuck\IniSerializer;

/**
 * @phpstan-import-type SweetchuckIniSerializerOptions from \Sweetchuck\IniSerializer\Phpstan
 */
class IniSerializer
{
    // region commentChars
    /**
     * @var array<string>
     */
    protected array $commentChars = [';', '#'];

    /**
     * @return array<string>
     */
    public function getCommentChars(): array
    {
        return $this->commentChars;
    }

    /**
     * @param array<string> $commentChars
     */
    public function setCommentChars(array $commentChars): static
    {
        $this->commentChars = $commentChars;

        return $this;
    }
    // endregion

    // region valueNull
    /**
     * @var array<string>
     */
    protected array $valueNull = ['null', 'nil'];

    /**
     * @return array<string>
     */
    public function getValueNull(): array
    {
        return $this->valueNull;
    }

    /**
     * @param array<string> $valueNull
     */
    public function setValueNull(array $valueNull): static
    {
        $this->valueNull = $valueNull;

        return $this;
    }
    // endregion

    // region valueBoolTrue
    /**
     * @var array<string>
     */
    protected array $valueBoolTrue = ['true', 'on', 'yes'];

    /**
     * @return array<string>
     */
    public function getValueBoolTrue(): array
    {
        return $this->valueBoolTrue;
    }

    /**
     * @param array<string> $valueBoolTrue
     */
    public function setValueBoolTrue(array $valueBoolTrue): static
    {
        $this->valueBoolTrue = $valueBoolTrue;

        return $this;
    }
    // endregion

    // region valueBoolFalse
    /**
     * @var array<string>
     */
    protected array $valueBoolFalse = ['false', 'off', 'no'];

    /**
     * @return array<string>
     */
    public function getValueBoolFalse(): array
    {
        return $this->valueBoolFalse;
    }

    /**
     * @param array<string> $valueBoolFalse
     */
    public function setValueBoolFalse(array $valueBoolFalse): static
    {
        $this->valueBoolFalse = $valueBoolFalse;

        return $this;
    }
    // endregion

    // region quoteStrings
    protected bool $quoteStrings = false;

    public function getQuoteStrings(): bool
    {
        return $this->quoteStrings;
    }

    public function setQuoteStrings(bool $quoteStrings): static
    {
        $this->quoteStrings = $quoteStrings;

        return $this;
    }
    // endregion

    // region spaceAroundEqualSign
    protected bool $spaceAroundEqualSign = false;

    public function getSpaceAroundEqualSign(): bool
    {
        return $this->spaceAroundEqualSign;
    }

    public function setSpaceAroundEqualSign(bool $spaceAroundEqualSign): static
    {
        $this->spaceAroundEqualSign = $spaceAroundEqualSign;

        return $this;
    }
    // endregion

    /**
     * @var array<string>
     */
    protected array $ini = [];

    /**
     * @phpstan-param SweetchuckIniSerializerOptions $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('commentChars', $options)) {
            $this->setCommentChars($options['commentChars']);
        }

        if (array_key_exists('valueNull', $options)) {
            $this->setValueNull($options['valueNull']);
        }

        if (array_key_exists('valueBoolTrue', $options)) {
            $this->setValueBoolTrue($options['valueBoolTrue']);
        }

        if (array_key_exists('valueBoolFalse', $options)) {
            $this->setValueBoolFalse($options['valueBoolFalse']);
        }

        if (array_key_exists('quoteStrings', $options)) {
            $this->setQuoteStrings($options['quoteStrings']);
        }

        if (array_key_exists('spaceAroundEqualSign', $options)) {
            $this->setSpaceAroundEqualSign($options['spaceAroundEqualSign']);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $ini): array
    {
        $data = [];
        $dataGroup =& $data;

        $lines = preg_split('/[\r\n]+/', $ini);
        if ($lines === false) {
            return $data;
        }

        // @todo Support for multiline values.
        foreach ($lines as $line) {
            $line = trim($line);
            if ($this->isCommentLine($line)) {
                continue;
            }

            if ($this->isGroupHeader($line)) {
                $groupName = $this->decodeGroupName(mb_substr($line, 1, -1));
                if (!array_key_exists($groupName, $data)) {
                    $data[$groupName] = [];
                }

                $dataGroup =& $data[$groupName];

                continue;
            }

            [$key, $value] = explode('=', $line, 2) + [1 => ''];
            $dataGroup[trim($key)] = $this->decodeValue(trim($value));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function emit(array $data): string
    {
        $this->ini = [];
        foreach ($data as $groupName => $keyValuePairs) {
            if (is_iterable($keyValuePairs)) {
                $this->emitAddGroup($groupName, $keyValuePairs);

                continue;
            }

            $this->emitAddKeyValue($groupName, $keyValuePairs);
        }

        $this->emitEnsureEmptyLine();

        $string = implode(PHP_EOL, $this->ini);
        $this->ini = [];

        return $string;
    }

    /**
     * @param iterable<string, mixed> $keyValuePairs
     */
    protected function emitAddGroup(string $groupName, iterable $keyValuePairs): static
    {
        $this->emitEnsureEmptyLine();
        $this->ini[] = sprintf('[%s]', $this->encodeGroupName($groupName));

        foreach ($keyValuePairs as $key => $value) {
            $this->emitAddKeyValue($key, $value);
        }

        $this->ini[] = '';

        return $this;
    }

    protected function emitAddKeyValue(string $key, mixed $value): static
    {
        $pattern = $this->getSpaceAroundEqualSign() ? '%s = %s' : '%s=%s';
        $this->ini[] = sprintf($pattern, $key, $this->encodeValue($value));

        return $this;
    }

    protected function emitEnsureEmptyLine(): static
    {
        if ($this->ini && end($this->ini) !== '') {
            $this->ini[] = '';
        }

        return $this;
    }

    protected function encodeGroupName(string $groupName): string
    {
        return strtr($groupName, ['[' => '\\x5b', ']' => '\\x5d']);
    }

    protected function decodeGroupName(string $groupName): string
    {
        return strtr($groupName, ['\\x5b' => '[', '\\x5d' => ']']);
    }

    protected function encodeValue(mixed $value): string
    {
        if ($value === null) {
            return $this->getValueNull()[0];
        }

        if (is_bool($value)) {
            return $value ?
                $this->getValueBoolTrue()[0]
                : $this->getValueBoolFalse()[0];
        }

        if (is_numeric($value)) {
            return (string)$value;
        }

        if ($this->getQuoteStrings()) {
            return sprintf('"%s"', $value);
        }

        $protectedValues = $this->getProtectedValues();
        if (array_search(mb_strtolower($value), $protectedValues)) {
            return sprintf('"%s"', $value);
        }

        return $value;
    }

    protected function decodeValue(string $input): mixed
    {
        // @todo Support for octal and hexadecimal numbers.
        if (is_numeric($input)) {
            settype($input, (mb_strpos($input, '.') ? 'float' : 'int'));

            return $input;
        }

        $inputLower = mb_strtolower($input);
        if ($input === '' || in_array($inputLower, $this->getValueNull())) {
            return null;
        }

        if (in_array($inputLower, $this->getValueBoolTrue())) {
            return true;
        }

        if (in_array($inputLower, $this->getValueBoolFalse())) {
            return false;
        }

        if (preg_match('/^".*"$/', $input)) {
            // @todo Strip slashes.
            return mb_substr($input, 1, -1);
        }

        return $input;
    }

    /**
     * @return array<string>
     */
    protected function getProtectedValues(): array
    {
        $values = [];
        $raw = array_merge(
            $this->getValueNull(),
            $this->getValueBoolTrue(),
            $this->getValueBoolFalse()
        );
        foreach ($raw as $value) {
            $values[] = mb_strtolower($value);
        }

        return array_unique($values);
    }

    protected function isCommentLine(string $line): bool
    {
        return !$line || in_array(mb_substr($line, 0, 1), $this->getCommentChars());
    }

    protected function isGroupHeader(string $line): bool
    {
        return preg_match('/^\[.*]$/', $line) === 1;
    }
}
