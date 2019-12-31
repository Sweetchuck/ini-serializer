<?php

declare(strict_types = 1);

namespace Sweetchuck\IniSerializer;

class IniSerializer
{
    // region commentChars
    /**
     * @var array
     */
    protected $commentChars = [';', '#'];

    public function getCommentChars(): array
    {
        return $this->commentChars;
    }

    /**
     * @return $this
     */
    public function setCommentChars(array $commentChars)
    {
        $this->commentChars = $commentChars;

        return $this;
    }
    // endregion

    // region valueNull
    /**
     * @var array
     */
    protected $valueNull = ['null', 'nil'];

    public function getValueNull(): array
    {
        return $this->valueNull;
    }

    /**
     * @return $this
     */
    public function setValueNull(array $valueNull)
    {
        $this->valueNull = $valueNull;

        return $this;
    }
    // endregion

    // region valueBoolTrue
    /**
     * @var array
     */
    protected $valueBoolTrue = ['true', 'on', 'yes'];

    public function getValueBoolTrue(): array
    {
        return $this->valueBoolTrue;
    }

    /**
     * @return $this
     */
    public function setValueBoolTrue(array $valueBoolTrue)
    {
        $this->valueBoolTrue = $valueBoolTrue;

        return $this;
    }
    // endregion

    // region valueBoolFalse
    /**
     * @var array
     */
    protected $valueBoolFalse = ['false', 'off', 'no'];

    public function getValueBoolFalse(): array
    {
        return $this->valueBoolFalse;
    }

    /**
     * @return $this
     */
    public function setValueBoolFalse(array $valueBoolFalse)
    {
        $this->valueBoolFalse = $valueBoolFalse;

        return $this;
    }
    // endregion

    // region quoteStrings
    /**
     * @var bool
     */
    protected $quoteStrings = false;

    public function getQuoteStrings(): bool
    {
        return $this->quoteStrings;
    }

    /**
     * @return $this
     */
    public function setQuoteStrings(bool $quoteStrings)
    {
        $this->quoteStrings = $quoteStrings;

        return $this;
    }
    // endregion

    // region spaceAroundEqualSign
    /**
     * @var bool
     */
    protected $spaceAroundEqualSign = false;

    public function getSpaceAroundEqualSign(): bool
    {
        return $this->spaceAroundEqualSign;
    }

    /**
     * @return $this
     */
    public function setSpaceAroundEqualSign(bool $spaceAroundEqualSign)
    {
        $this->spaceAroundEqualSign = $spaceAroundEqualSign;

        return $this;
    }
    // endregion

    /**
     * @var string[]
     */
    protected $ini = [];

    /**
     * @return $this
     */
    public function setOptions(array $options)
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

    public function parse(string $ini): array
    {
        $data = [];
        $dataGroup =& $data;

        $lines = preg_split('/[\r\n]+/', $ini);
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

            list($key, $value) = preg_split('/=/', $line, 2) + [1 => ''];
            $dataGroup[trim($key)] = $this->decodeValue(trim($value));
        }

        return $data;
    }

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

    protected function emitAddGroup(string $groupName, iterable $keyValuePairs)
    {
        $this->emitEnsureEmptyLine();
        $this->ini[] = sprintf('[%s]', $this->encodeGroupName($groupName));

        foreach ($keyValuePairs as $key => $value) {
            $this->emitAddKeyValue($key, $value);
        }

        $this->ini[] = '';

        return $this;
    }

    protected function emitAddKeyValue(string $key, $value)
    {
        $pattern = $this->getSpaceAroundEqualSign() ? '%s = %s' : '%s=%s';
        $this->ini[] = sprintf($pattern, $key, $this->encodeValue($value));

        return $this;
    }

    protected function emitEnsureEmptyLine()
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

    protected function encodeValue($value): string
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

    /**
     * @return mixed
     */
    protected function decodeValue(string $input)
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
     * @return string[]
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
