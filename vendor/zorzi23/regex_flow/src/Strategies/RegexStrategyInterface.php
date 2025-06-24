<?php
namespace RegexFlow\Strategies;
use RegexFlow\RegexExpression;

/**
 * Interface RegexStrategyInterface
 *
 * Defines the contract for regex strategy implementations.
 */
interface RegexStrategyInterface {

    /**
     * Executes the regex strategy on the given expression.
     *
     * @param RegexExpression $oExpression The regex expression to execute the strategy on.
     * @return mixed The result of the execution.
     */
    public function execute(RegexExpression $oExpression);

}