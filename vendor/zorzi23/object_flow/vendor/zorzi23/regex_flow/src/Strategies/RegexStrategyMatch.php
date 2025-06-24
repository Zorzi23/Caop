<?php
namespace RegexFlow\Strategies;
use RegexFlow\RegexExpression;

/**
 * Class RegexStrategyMatch
 *
 * Implements the strategy for matching a regex pattern once.
 */
class RegexStrategyMatch implements RegexStrategyInterface {

    /**
     * Executes the regex match strategy on the given expression.
     *
     * @param RegexExpression $oExpression The regex expression to execute the strategy on.
     * @return array|null The matches found, or null if the expression is invalid.
     */
    public function execute(RegexExpression $oExpression) {
        if(!$oExpression->isValid()) {
            return null;
        }
        preg_match(
            $oExpression->getPattern(),
            $oExpression->getTest(),
            $aMatches,
            $oExpression->getFlags() ?: 0
        );
        return $aMatches;
    }

}