<?php
namespace RegexFlow;

/**
 * Class RegexExpression
 *
 * Represents a regular expression and its associated test string and flags.
 */
class RegexExpression {

    /**
     * @var string The regex pattern.
     */
    private $sPattern;

    /**
     * @var string The test string.
     */
    private $sTest;

    /**
     * @var int The regex flags.
     */
    private $iFlags;

    /**
     * RegexExpression constructor.
     *
     * @param string $sPattern The regex pattern.
     */
    public function __construct($sPattern) {
        $this->setPattern($sPattern);
    }

    /**
     * Creates a new instance with the given string and pattern.
     *
     * @param string $sString The test string.
     * @param string $sPattern The regex pattern.
     * @return self
     */
    public static function stringPattern($sString, $sPattern) {
        return (new self($sPattern))
            ->setTest($sString);
    }

    /**
     * Gets the regex pattern.
     *
     * @return string
     */
    public function getPattern() {
        return $this->sPattern;
    }

    /**
     * Sets the regex pattern.
     *
     * @param string $sPattern The regex pattern.
     * @return self
     */
    public function setPattern($sPattern) {
        $this->sPattern = $sPattern;
        return $this;
    }

    /**
     * Gets the test string.
     *
     * @return string
     */
    public function getTest() {
        return $this->sTest;
    }

    /**
     * Sets the test string.
     *
     * @param string $sTest The test string.
     * @return self
     */
    public function setTest($sTest){
        $this->sTest = $sTest;
        return $this;
    }

    /**
     * Gets the regex flags.
     *
     * @return int
     */
    public function getFlags() {
        return $this->iFlags;
    }

    /**
     * Sets the regex flags.
     *
     * @param int $iFlags The regex flags.
     * @return self
     */
    public function setFlags($iFlags){
        $this->iFlags = $iFlags;
        return $this;
    }

    /**
     * Checks if the regex pattern is valid.
     *
     * @return bool
     */
    public function isValid() {
        return !!$this->getPattern();
    }

}