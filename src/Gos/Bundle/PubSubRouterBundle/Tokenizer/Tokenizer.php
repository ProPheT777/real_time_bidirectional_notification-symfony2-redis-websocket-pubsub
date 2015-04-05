<?php

namespace Gos\Bundle\PubSubRouterBundle\Tokenizer;

use Gos\Bundle\PubSubRouterBundle\Exception\InvalidArgumentException;
use Gos\Bundle\PubSubRouterBundle\Router\RouteInterface;

class Tokenizer implements TokenizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function tokenize($stringOrRoute, $separator)
    {
        if ($stringOrRoute instanceof RouteInterface) {
            $pattern = $stringOrRoute->getPattern();
            $requirements = $stringOrRoute->getRequirements();
        } else {
            $pattern = $stringOrRoute;
        }

        if (false === strpos($pattern, $separator)) {
            return false;
        }

        $rawTokens = explode($separator, $pattern);
        $tokens = [];
        $requirementsSeen = [];
        $parametersSeen = [];

        foreach ($rawTokens as $i => $rawToken) {
            $token = new Token();
            $split = str_split($rawToken);
            reset($split);

            if (current($split) === '{' && end($split) === '}') {
                $token->setParameter();
                unset($split[0], $split[count($split)]);
            }

            $token->setExpression(implode($split));

            if ($token->isParameter()) {
                $parametersSeen[] = $token->getExpression();
            }

            if ($stringOrRoute instanceof RouteInterface) {
                if (!empty($stringOrRoute->getRequirements())) {
                    if (isset($requirements[$token->getExpression()])) {
                        $requirementsSeen[] = $token->getExpression();
                        $token->setRequirements($requirements[$token->getExpression()]);
                    }
                }
            }

            $tokens[$i] = $token;
        }

        if ($stringOrRoute instanceof RouteInterface) {
            if ($requirementsSeen !== $parametersSeen) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown parameter %s in [ %s ]',
                    implode(', ', array_diff($parametersSeen, $requirementsSeen)),
                    implode(', ', $parametersSeen)
                ));
            }
        }

        return $tokens;
    }
}