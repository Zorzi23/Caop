<?php
namespace RegexFlow\Strategies;
use RegexFlow\RegexExpression;

/**
 * Class RegexStrategyMatchAll
 *
 * Implements the strategy for matching a regex pattern multiple times.
 */
class RegexStrategyMatchAll implements RegexStrategyInterface {

    /**
     * Executes the regex match all strategy on the given expression.
     *
     * @param RegexExpression $oExpression The regex expression to execute the strategy on.
     * @return array The matches found.
     */
    public function execute(RegexExpression $oExpression) {
        preg_match_all(
            $oExpression->getPattern(),
            $oExpression->getTest(),
            $aMatches,
            $oExpression->getFlags()
        );
        return $aMatches;
    }

}