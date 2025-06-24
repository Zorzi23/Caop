<?php
namespace RegexFlow;
use RegexFlow\Strategies\RegexStrategyInterface;
use RegexFlow\Strategies\RegexStrategyMatch;
use RegexFlow\Strategies\RegexStrategyMatchAll;
use RegexFlow\RegexExpression;

/**
 * Class Regex
 *
 * Provides an interface for executing regex strategies.
 */
class Regex {
    
    /**
     * @var RegexStrategyInterface The strategy to be used for regex operations.
     */
    private $oStrategy;
    
    /**
     * Regex constructor.
     *
     * @param RegexStrategyInterface $oStrategy The strategy to be used.
     */
    public function __construct(RegexStrategyInterface $oStrategy) {
        $this->setStrategy($oStrategy);
    }
    
    /**
     * Creates a new instance with the match strategy.
     *
     * @return self
     */
    public static function match() {
        return new self(new RegexStrategyMatch());
    }

    /**
     * Creates a new instance with the match all strategy.
     *
     * @return self
     */
    public static function matchAll() {
        return new self(new RegexStrategyMatchAll());
    }

    /**
     * Gets the current strategy.
     *
     * @return RegexStrategyInterface
     */
    public function getStrategy() {
        return $this->oStrategy;
    }

    /**
     * Sets the strategy to be used.
     *
     * @param RegexStrategyInterface $oStrategy The strategy to be set.
     * @return self
     */
    public function setStrategy($oStrategy) {
        $this->oStrategy = $oStrategy;
        return $this;
    }

    /**
     * Executes the current strategy on the given expression.
     *
     * @param RegexExpression $oExpression The regex expression to execute the strategy on.
     * @return mixed The result of the execution.
     */
    public function execute(RegexExpression $oExpression) {
        return $this->getStrategy()->execute($oExpression);
    }

}