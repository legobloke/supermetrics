<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

/**
 * Calculates average posts per user on a monthly basis
 */
class MonthlyPostsPerUser extends AbstractCalculator
{
    protected const UNITS = 'avg posts per user';
    private array $postsAccumulated;
    /**
     * @inheritDoc
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $month = $postTo->getDate()->format('Ym');

        if (!isset($this->postsAccumulated[$month])) {
            $monthText = $postTo->getDate()->format('F Y');
            $this->postsAccumulated[$month] = [
                'posts' => 0,
                'authorIds' => [],
                'monthText' => $monthText
            ];
        }

        if (!in_array($postTo->getAuthorId(), $this->postsAccumulated[$month]['authorIds'])) {
            $this->postsAccumulated[$month]['authorIds'][] = $postTo->getAuthorId();
        }

        $this->postsAccumulated[$month]['posts']++;

    }

    /**
     * @inheritDoc
     */
    protected function doCalculate(): StatisticsTo
    {
        krsort($this->postsAccumulated, SORT_NUMERIC);
        $stats = new StatisticsTo();
        foreach ($this->postsAccumulated as $month => $values) {
            $avgPosts = (count($values['authorIds']) > 0) ?
                round($values['posts'] / count($values['authorIds']), 2) : 0;
             $child = (new StatisticsTo())
                ->setName($this->parameters->getStatName())
                ->setSplitPeriod($values['monthText'])
                ->setValue($avgPosts)
                ->setUnits(self::UNITS);

            $stats->addChild($child);
        }
        return $stats;
    }
}
